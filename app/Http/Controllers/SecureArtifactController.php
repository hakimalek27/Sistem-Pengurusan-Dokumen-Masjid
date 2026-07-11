<?php

namespace App\Http\Controllers;

use App\Models\DisposalBatch;
use App\Models\StorageOrder;
use App\Models\StoredExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureArtifactController extends Controller
{
    public function invoice(Request $request, int $order): StreamedResponse
    {
        $model = StorageOrder::query()->withoutGlobalScope('mosque')->findOrFail($order);

        if (! $model->invoice_path || Gate::forUser($request->user())->denies('download', $model)) {
            abort(404);
        }

        return $this->download($model->invoice_path, $model->invoice_no.'.pdf', 'application/pdf');
    }

    public function certificate(Request $request, int $batch): StreamedResponse
    {
        $model = DisposalBatch::query()->withoutGlobalScope('mosque')->findOrFail($batch);

        if (! $model->certificate_path || Gate::forUser($request->user())->denies('downloadCertificate', $model)) {
            abort(404);
        }

        return $this->download($model->certificate_path, 'sijil-pelupusan-'.$model->id.'.pdf', 'application/pdf');
    }

    public function export(Request $request, int $export): StreamedResponse
    {
        $model = StoredExport::query()->withoutGlobalScope('mosque')->findOrFail($export);

        if (Gate::forUser($request->user())->denies('download', $model)) {
            abort(404);
        }

        return $this->download($model->path, $model->label.'.zip', 'application/zip');
    }

    protected function download(string $path, string $name, string $mime): StreamedResponse
    {
        $disk = Storage::disk(config('diwan.storage_disk'));

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, $name, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ], 'attachment');
    }
}
