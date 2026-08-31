<?php

namespace App\Http\Controllers;

use App\Models\VerificationDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class VerificationDocumentController extends Controller
{
    /**
     * Private document download — the owner who submitted it, or a Super
     * Admin, only. Never a public URL, matching the same pattern as
     * ApplicationDocumentController from Step 5.
     */
    public function download(VerificationDocument $document): Response
    {
        $verification = $document->ownerVerification;
        $user = auth()->user();

        abort_unless($user->id === $verification->user_id || $user->isSuperAdmin(), 403);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }
}
