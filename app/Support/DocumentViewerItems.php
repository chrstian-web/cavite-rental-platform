<?php

namespace App\Support;

use Illuminate\Support\Collection;

/**
 * Turns stored document rows into the plain array the in-page document viewer
 * (partials/document-viewer.blade.php) renders. URLs are the authorized routes,
 * never public storage paths.
 */
class DocumentViewerItems
{
    public static function fromApplication(iterable $documents): array
    {
        return Collection::make($documents)->map(fn ($doc) => [
            'label' => $doc->label,
            'name' => $doc->original_filename,
            'meta' => null,
            'kind' => self::kind($doc->original_filename, $doc->mime_type),
            'src' => route('documents.view', $doc),
            'download' => route('documents.download', $doc),
        ])->values()->all();
    }

    public static function fromVerification(iterable $documents, array $types = []): array
    {
        return Collection::make($documents)->map(function ($doc) use ($types) {
            $meta = [];
            if ($doc->expiration_date) {
                $meta[] = 'Expires '.$doc->expiration_date->format('M j, Y');
            }
            if ($doc->status) {
                $meta[] = str($doc->status)->replace('_', ' ')->title()->toString();
            }

            return [
                'label' => $types[$doc->document_type]['label']
                    ?? str($doc->document_type)->replace('_', ' ')->title()->toString(),
                'name' => $doc->original_filename,
                'meta' => $meta ? implode(' · ', $meta) : null,
                'kind' => self::kind($doc->original_filename, $doc->mime_type),
                'src' => route('verification-documents.view', $doc),
                'download' => route('verification-documents.download', $doc),
            ];
        })->values()->all();
    }

    private static function kind(?string $filename, ?string $mime): string
    {
        $ext = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));

        if (str_starts_with((string) $mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'image';
        }

        if ($mime === 'application/pdf' || $ext === 'pdf') {
            return 'pdf';
        }

        return 'other';
    }
}
