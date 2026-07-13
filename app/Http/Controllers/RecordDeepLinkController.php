<?php

namespace App\Http\Controllers;

use App\Enums\RecordStatus;
use App\Filament\App\Resources\Inbox\InboxResource;
use App\Filament\App\Resources\Records\RecordResource;
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

        // Policy merangkumi keahlian + sensitiviti; gagal → 404 (jangan dedah kewujudan).
        if (! $user->can('view', $record)) {
            abort(404);
        }

        $resource = $record->status === RecordStatus::PetiMasuk ? InboxResource::class : RecordResource::class;

        return redirect($resource::getUrl('view', ['record' => $record], panel: 'app', tenant: $mosque));
    }
}
