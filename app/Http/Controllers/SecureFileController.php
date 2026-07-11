<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Services\SensitiveAccessLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureFileController extends Controller
{
    public function __invoke(Request $request, int $media, SensitiveAccessLogger $logger): StreamedResponse
    {
        $mediaModel = Media::query()->findOrFail($media);
        $record = $mediaModel->model;

        if (! $record instanceof Record
            || ! in_array($mediaModel->collection_name, ['original', 'derived', 'attachments'], true)
            || Gate::forUser($request->user())->denies('download', $record)) {
            abort(404);
        }

        $disposition = $request->query('disposition') === 'attachment' ? 'attachment' : 'inline';
        $isPreviewable = $mediaModel->mime_type === 'application/pdf'
            || str_starts_with((string) $mediaModel->mime_type, 'image/');

        if (! $isPreviewable) {
            $disposition = 'attachment';
        }

        $logger->log($record, $request->user(), $disposition === 'inline' ? 'view' : 'download', $request);

        return Storage::disk($mediaModel->disk)->response(
            $mediaModel->getPathRelativeToRoot(),
            $mediaModel->file_name,
            [
                'Content-Type' => $mediaModel->mime_type ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'",
                'Cache-Control' => 'private, no-store, max-age=0',
            ],
            $disposition,
        );
    }
}
