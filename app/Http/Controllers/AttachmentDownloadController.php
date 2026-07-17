<?php

namespace App\Http\Controllers;

use App\Modules\Content\Models\LessonAttachment;
use App\Modules\Content\Services\AttachmentAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        LessonAttachment $attachment,
        AttachmentAccessService $access,
    ): StreamedResponse {
        abort_unless($request->hasValidSignature(), 403);

        $access->assertCanDownload($request->user(), $attachment);

        $disk = Storage::disk($attachment->disk);

        abort_unless($disk->exists($attachment->path), 404);

        return $disk->download($attachment->path, $attachment->name);
    }
}
