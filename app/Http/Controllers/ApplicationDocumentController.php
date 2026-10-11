<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ServesPrivateFiles;
use App\Models\ApplicationDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ApplicationDocumentController extends Controller
{
    use ServesPrivateFiles;

    /**
     * Serve a private application document. Never exposed via a public URL —
     * every download is authorized against the current user first:
     * the applicant themself, the property owner/manager, or a super admin.
     */
    public function download(ApplicationDocument $document): Response
    {
        $application = $document->rentalApplication;
        $this->authorize('view', $application);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return Storage::disk('local')->download($document->path, $document->original_filename);
    }

    /** Same authorization as download(), but shown inline so the document viewer can display it. */
    public function show(ApplicationDocument $document): Response
    {
        $this->authorize('view', $document->rentalApplication);

        return $this->showPrivateFile($document->path, $document->original_filename);
    }
}
