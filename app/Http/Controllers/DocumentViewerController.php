<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Services\SecureDownloadUrl;
use App\Services\SensitiveAccessLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DocumentViewerController extends Controller
{
    public function __invoke(Request $request, int $media, SecureDownloadUrl $urls, SensitiveAccessLogger $logger): View
    {
        $mediaModel = Media::query()->findOrFail($media);
        $record = $mediaModel->model;

        if (! $record instanceof Record
            || ! in_array($mediaModel->collection_name, ['original', 'derived', 'attachments'], true)
            || Gate::forUser($request->user())->denies('view', $record)
            || ! ($mediaModel->mime_type === 'application/pdf' || str_starts_with((string) $mediaModel->mime_type, 'image/'))) {
            abort(404);
        }

        $logger->log($record, $request->user(), 'view', $request);

        return view('document-viewer', [
            'record' => $record->loadMissing('registryFile'),
            'media' => $mediaModel,
            'mediaUrl' => $urls->media($mediaModel, 'inline'),
            'downloadUrl' => $urls->media($mediaModel, 'attachment'),
        ]);
    }
}
