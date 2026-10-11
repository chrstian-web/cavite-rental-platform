<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

trait ServesPrivateFiles
{
    /** Types that are safe to render inside the browser. Everything else is always a download. */
    private const INLINE_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf',
    ];

    /**
     * Stream a private file inline (for the in-page document viewer) when it is an
     * image/PDF, otherwise fall back to a normal download. The MIME type comes from the
     * stored file itself, not from the uploader, and nosniff stops browsers guessing.
     * Callers MUST authorize the request before calling this.
     */
    protected function showPrivateFile(string $path, ?string $filename): Response
    {
        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        $mime = $disk->mimeType($path) ?: 'application/octet-stream';

        if (! in_array($mime, self::INLINE_MIMES, true)) {
            return $disk->download($path, $filename);
        }

        return $disk->response($path, $filename, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=0, must-revalidate',
        ], 'inline');
    }
}
