<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Providers\Models\Media;
use App\Modules\Verification\Models\Credential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves a private credential document through a short-lived signed link, to
 * the provider who uploaded it or to staff allowed to view documents.
 */
class DocumentController extends Controller
{
    public function __invoke(Request $request, Media $media): StreamedResponse
    {
        abort_unless($media->collection === Media::COLLECTION_DOCUMENT && $media->mediable instanceof Credential, 404);

        $provider = $media->mediable->provider;
        Gate::authorize('viewDocuments', $provider);

        $disk = Storage::disk($media->original_disk);
        abort_unless($disk->exists($media->original_path), 404);

        activity('verification')->performedOn($media->mediable)->causedBy($request->user())->event('document_viewed')
            ->withProperties(['media_id' => $media->id])->log('Viewed credential document');

        return $disk->response($media->original_path, $media->original_filename, [
            'Content-Type' => $media->mime_type,
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
        ], 'inline');
    }
}
