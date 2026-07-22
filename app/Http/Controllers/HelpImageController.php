<?php

namespace App\Http\Controllers;

use App\Models\Mosque;
use App\Services\HelpCatalog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class HelpImageController extends Controller
{
    public function __invoke(Request $request, string $guideId, HelpCatalog $catalog): BinaryFileResponse
    {
        $rawGuide = collect($catalog->raw()['guides'] ?? [])->firstWhere('id', $guideId);
        abort_unless($rawGuide, 404);
        $panel = (string) ($rawGuide['panel'] ?? 'public');
        $user = $request->user();
        $mosque = null;

        if ($panel === 'admin') {
            abort_unless($user?->is_superadmin, 404);
        }
        if ($panel === 'app') {
            $mosque = Mosque::query()->find($request->integer('tenant'));
            abort_unless($user && $mosque && $user->isMemberOf($mosque), 404);
        }

        $guide = $catalog->findVisible($guideId, $panel, $user, $mosque);
        abort_unless($guide && filled($guide['images'] ?? []), 404);

        $imageIndex = 0;
        if ($panel === 'app' && $user && $mosque) {
            $roleFolder = match ($user->roleIn($mosque)) {
                'admin_masjid' => '01-Admin-Kerani',
                'pengerusi' => '02-Pengerusi',
                'setiausaha' => '03-Setiausaha',
                'bendahari' => '04-Bendahari',
                'nazir' => '05-Nazir',
                'ketua_imam' => '06-Ketua-Imam',
                'ajk' => '07-AJK',
                'audit' => '08-Juruaudit',
                default => null,
            };
            if ($roleFolder) {
                $matchingIndex = collect($guide['images'])->search(
                    fn (string $image): bool => str_contains(str_replace('\\', '/', $image), "/{$roleFolder}/"),
                );
                if ($matchingIndex !== false) {
                    $imageIndex = $matchingIndex;
                }
            }
        }

        $base = realpath(base_path('Manual Penguna'));
        $path = realpath(base_path((string) $guide['images'][$imageIndex]));
        abort_unless($base && $path && str_starts_with(strtolower($path), strtolower($base.DIRECTORY_SEPARATOR)) && str_ends_with(strtolower($path), '.png'), 404);

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => $panel === 'public' ? 'public, max-age=86400' : 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
