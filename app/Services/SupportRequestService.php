<?php

namespace App\Services;

use App\Models\Mosque;
use App\Models\SupportAttachment;
use App\Models\SupportRequest;
use App\Models\User;
use App\Support\AllowedFormats;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class SupportRequestService
{
    public function create(array $data, ?User $user, ?Mosque $mosque, string $panel, ?UploadedFile $attachment = null): SupportRequest
    {
        if (! config('diwan.guidance.support_enabled')) {
            throw ValidationException::withMessages(['supportSubject' => 'Laporan masalah ditutup sementara.']);
        }
        if ($mosque && (! $user || ! $user->isMemberOf($mosque))) {
            throw ValidationException::withMessages(['supportSubject' => 'Konteks tenant tidak sah.']);
        }
        if (! in_array($data['category'] ?? null, [...array_keys(HelpDiagnosisService::CATEGORIES), 'lain'], true)) {
            throw ValidationException::withMessages(['supportCategory' => 'Kategori laporan masalah tidak sah.']);
        }

        $contents = null;
        $scan = null;
        if ($attachment) {
            $contents = (string) file_get_contents($attachment->getRealPath());
            $this->validateAttachment($attachment, $contents);
            $scan = app(AntivirusScanner::class)->scan($contents);
            if ($scan['status'] === 'infected') {
                throw ValidationException::withMessages(['supportAttachment' => 'Lampiran ditolak kerana mengandungi ancaman.']);
            }
            if (config('diwan.clamav.enabled') && config('diwan.clamav.fail_closed') && $scan['status'] !== 'clean') {
                throw ValidationException::withMessages(['supportAttachment' => 'Lampiran ditolak kerana imbasan antivirus tidak dapat disahkan.']);
            }
        }

        $storedPath = null;

        try {
            return DB::transaction(function () use ($data, $user, $mosque, $panel, $attachment, $contents, $scan, &$storedPath): SupportRequest {
                $request = SupportRequest::query()->create([
                    'reference' => 'SUP-'.now()->format('ymd').'-'.Str::upper(Str::random(8)),
                    'mosque_id' => $mosque?->id,
                    'user_id' => $user?->id,
                    'reporter_session_hash' => $user ? null : hash_hmac('sha256', session()->getId(), (string) config('app.key')),
                    'panel' => $panel,
                    'role' => $panel === 'admin' ? 'superadmin' : ($user && $mosque ? $user->roleIn($mosque) : 'public'),
                    'category' => (string) $data['category'],
                    'subject' => Str::limit(strip_tags((string) $data['subject']), 180, ''),
                    'expected' => Str::limit(strip_tags((string) $data['expected']), 5000, ''),
                    'actual' => Str::limit(strip_tags((string) $data['actual']), 5000, ''),
                    'route_template' => $this->sanitiseRoute($data['route_template'] ?? null, $mosque),
                    'request_id' => Str::isUuid((string) ($data['request_id'] ?? '')) ? (string) $data['request_id'] : null,
                    'browser_context' => $this->sanitiseBrowserContext($data['browser_context'] ?? []),
                    'unmatched_query' => ($data['query_consent'] ?? false) ? Str::limit(strip_tags((string) ($data['unmatched_query'] ?? '')), 500, '') : null,
                    'query_consent' => (bool) ($data['query_consent'] ?? false),
                    'status' => 'baharu',
                ]);

                if ($attachment && $contents !== null && $scan) {
                    $extension = strtolower($attachment->getClientOriginalExtension());
                    $storedPath = 'support/'.($mosque?->id ?? 'public').'/'.$request->id.'/'.Str::uuid().'.'.$extension;
                    if (! Storage::disk('local')->put($storedPath, $contents)) {
                        throw new RuntimeException('Lampiran sokongan gagal disimpan.');
                    }
                    SupportAttachment::query()->create([
                        'support_request_id' => $request->id,
                        'mosque_id' => $mosque?->id,
                        'disk' => 'local',
                        'path' => $storedPath,
                        'original_name' => Str::limit(basename($attachment->getClientOriginalName()), 255, ''),
                        'mime' => $attachment->getMimeType() ?: 'application/octet-stream',
                        'size_bytes' => strlen($contents),
                        'sha256' => hash('sha256', $contents),
                        'scan_status' => $scan['status'],
                        'scan_signature' => $scan['signature'],
                    ]);
                }

                return $request->load('attachments');
            });
        } catch (Throwable $exception) {
            if ($storedPath) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    protected function validateAttachment(UploadedFile $attachment, string $contents): void
    {
        $maxBytes = (int) config('diwan.guidance.support_attachment_max_kb', 5120) * 1024;
        $extension = strtolower($attachment->getClientOriginalExtension());
        $mime = $attachment->getMimeType();

        if (strlen($contents) > $maxBytes) {
            throw ValidationException::withMessages(['supportAttachment' => 'Lampiran melebihi had 5 MB.']);
        }
        if (! AllowedFormats::allowsExtension($extension) || ! AllowedFormats::allowsMime($mime)) {
            throw ValidationException::withMessages(['supportAttachment' => AllowedFormats::rejectionMessage()]);
        }
    }

    protected function sanitiseRoute(?string $route, ?Mosque $mosque): ?string
    {
        if (! filled($route)) {
            return null;
        }

        $path = parse_url((string) $route, PHP_URL_PATH) ?: '/';
        if ($mosque) {
            $path = preg_replace('#^/app/'.preg_quote($mosque->slug, '#').'(?=/|$)#', '/app/{tenant}', $path);
        }
        $path = preg_replace('/\/[0-9]+(?=\/|$)/', '/{id}', $path);
        $path = preg_replace('/\/[0-9A-HJKMNP-TV-Z]{20,30}(?=\/|$)/i', '/{record}', (string) $path);

        return Str::limit((string) $path, 255, '');
    }

    protected function sanitiseBrowserContext(array $context): array
    {
        return collect($context)->only(['browser', 'platform', 'language', 'viewport', 'user_agent'])
            ->map(fn ($value) => Str::limit(strip_tags((string) $value), 500, ''))
            ->all();
    }
}
