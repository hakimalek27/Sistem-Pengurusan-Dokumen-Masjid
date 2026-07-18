<?php

namespace App\Services;

use App\Enums\SourceChannel;
use App\Models\Mosque;
use App\Support\AllowedFormats;

/**
 * §11.3 — Ingest e-mel pengimbas via plus-addressing (scan.diwan+{slug}@…).
 * Penghalaan tenant ikut slug; dedup sha256 BERSKOP masjid.
 */
class MailIngestService
{
    public function __construct(protected InboxIngestService $inbox) {}

    /**
     * Alamat asas intake. Utamakan alamat rasmi (cth scan@bakwim.my) dari
     * config diwan.mail_intake.address; jatuh balik ke IMAP username supaya
     * alias yang dipaparkan/dipadan bebas daripada log masuk peti mel sebenar.
     */
    protected function intakeBaseAddress(): ?string
    {
        $address = strtolower(trim((string) config('diwan.mail_intake.address')));
        if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
            $address = strtolower(trim((string) config('imap.accounts.default.username')));
        }

        return filter_var($address, FILTER_VALIDATE_EMAIL) ? $address : null;
    }

    /** Ekstrak slug daripada alamat plus-addressing. */
    public function slugFromAddress(string $address): ?string
    {
        $configured = $this->intakeBaseAddress();
        if ($configured === null) {
            return null;
        }

        [$local, $domain] = explode('@', $configured, 2);
        $pattern = '/^'.preg_quote($local, '/').'\+([a-z0-9-]+)@'.preg_quote($domain, '/').'$/i';
        if (preg_match($pattern, strtolower(trim($address)), $m)) {
            return strtolower($m[1]);
        }

        return null;
    }

    public function intakeAddress(Mosque $mosque): ?string
    {
        $configured = $this->intakeBaseAddress();
        if ($configured === null) {
            return null;
        }

        [$local, $domain] = explode('@', $configured, 2);

        return $local.'+'.$mosque->slug.'@'.$domain;
    }

    /**
     * Proses satu mesej e-mel yang telah dihurai.
     * $recipients = senarai alamat To/Delivered-To. $attachments = [['content','filename','mime'], ...].
     */
    public function ingestMessage(array $recipients, string $from, string $subject, string $messageId, array $attachments, string $body = ''): array
    {
        $slug = null;
        foreach ($recipients as $address) {
            if ($slug = $this->slugFromAddress($address)) {
                break;
            }
        }

        if (! $slug) {
            return ['status' => 'no_slug'];
        }

        $mosque = Mosque::query()->where('slug', $slug)->first();
        if (! $mosque || ! $mosque->isActive()) {
            return ['status' => 'unknown_or_inactive', 'slug' => $slug];
        }

        if (! $mosque->mailIntakeEnabled()) {
            return ['status' => 'disabled', 'mosque' => $mosque];
        }

        $from = strtolower(trim($from));
        if (! in_array($from, $mosque->mailIntakeSenders(), true)) {
            return ['status' => 'sender_not_allowed', 'mosque' => $mosque];
        }

        $keyword = mb_strtolower(trim($mosque->mailIntakeKeyword()));
        $haystack = mb_strtolower($subject."\n".$body);
        if ($keyword === '' || ! str_contains($haystack, $keyword)) {
            return ['status' => 'keyword_missing', 'mosque' => $mosque];
        }

        if ($mosque->storage_used_bytes >= $mosque->effectiveQuotaBytes()) {
            return ['status' => 'quota', 'mosque' => $mosque];
        }

        $created = [];
        $skipped = 0;
        $rejectedFormat = [];

        foreach ($attachments as $attachment) {
            $ext = strtolower(pathinfo($attachment['filename'], PATHINFO_EXTENSION));
            if (! AllowedFormats::allowsExtension($ext)) {
                $rejectedFormat[] = $attachment['filename'];

                continue;
            }

            // MIME kanonik daripada extension (header e-mel selalunya octet-stream).
            $mime = AllowedFormats::mimeForExtension($ext)
                ?? ($attachment['mime'] ?? 'application/octet-stream');

            $record = $this->inbox->ingest(
                $mosque,
                $attachment['content'],
                $attachment['filename'],
                $mime,
                null,
                SourceChannel::Emel,
                ['from' => $from, 'subject' => $subject, 'message_id' => $messageId, 'keyword' => $keyword],
                skipIfDuplicate: true,
            );

            $record ? $created[] = $record : $skipped++;
        }

        // Semua lampiran ditolak kerana format (tiada dicipta, tiada duplikat) =
        // isyarat khusus untuk notifikasi admin (F2). E-mel tanpa lampiran kekal 'ok'.
        $status = ($rejectedFormat !== [] && $created === [] && $skipped === 0)
            ? 'all_rejected'
            : 'ok';

        return [
            'status' => $status,
            'mosque' => $mosque,
            'records' => $created,
            'skipped_duplicate' => $skipped,
            'rejected_format' => $rejectedFormat,
        ];
    }
}
