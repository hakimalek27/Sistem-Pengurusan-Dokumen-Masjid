<?php

namespace App\Http\Controllers;

use App\Models\Record;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// §9.A — /r/{ulid}: deep-link rekod. Sahkan keahlian tenant (atau superadmin) → redirect; bukan ahli → 404.
class RecordDeepLinkController extends Controller
{
    public function __invoke(Request $request, string $ulid): RedirectResponse
    {
        $record = Record::query()->withoutGlobalScope('mosque')->where('ulid', $ulid)->first();

        if (! $record) {
            abort(404);
        }

        $user = Auth::user();
        $mosque = $record->mosque;

        // Bukan ahli tenant rekod (dan bukan superadmin) → 404 (jangan dedah kewujudan).
        if (! $user->is_superadmin && ! $user->isMemberOf($mosque)) {
            abort(404);
        }

        // Fasa 3 akan hala ke halaman ViewRecord; buat masa ini ke panel masjid.
        return redirect('/app/'.$mosque->slug);
    }
}
