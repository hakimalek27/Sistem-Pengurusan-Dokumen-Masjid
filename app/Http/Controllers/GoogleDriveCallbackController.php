<?php

namespace App\Http\Controllers;

use App\Services\GoogleDrive\GoogleOAuthService;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * §4.6′ — Callback OAuth Google Drive (superadmin sahaja). Sahkan state CSRF,
 * tukar kod jadi refresh token, simpan sambungan, kembali ke Tetapan Platform.
 */
class GoogleDriveCallbackController extends Controller
{
    public function __invoke(Request $request, GoogleOAuthService $oauth)
    {
        abort_unless(Auth::user()?->is_superadmin ?? false, 403);

        $state = (string) $request->query('state', '');
        $expected = Cache::pull('gdrive_oauth_state:'.Auth::id());

        if ($state === '' || ! is_string($expected) || ! hash_equals($expected, $state)) {
            abort(419, 'State OAuth tidak sah atau tamat tempoh.');
        }

        if ($request->filled('error')) {
            Notification::make()->title('Sambungan Google Drive dibatalkan.')->warning()->send();

            return redirect('/admin/tetapan-platform');
        }

        try {
            $result = $oauth->exchangeCode((string) $request->query('code', ''));
            $oauth->storeConnection($result);
        } catch (\Throwable $e) {
            Notification::make()->title('Gagal sambung Google Drive: '.$e->getMessage())->danger()->send();

            return redirect('/admin/tetapan-platform');
        }

        Notification::make()->title('Google Drive disambung: '.($result['email'] ?? '—'))->success()->send();

        return redirect('/admin/tetapan-platform');
    }
}
