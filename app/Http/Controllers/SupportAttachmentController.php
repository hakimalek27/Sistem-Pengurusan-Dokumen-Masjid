<?php

namespace App\Http\Controllers;

use App\Models\SupportAttachment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SupportAttachmentController extends Controller
{
    public function __invoke(SupportAttachment $attachment): BinaryFileResponse
    {
        $request = $attachment->request;
        abort_unless($request && auth()->user()?->can('view', $request), 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return response()->download(
            Storage::disk($attachment->disk)->path($attachment->path),
            $attachment->original_name,
            ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'],
        );
    }
}
