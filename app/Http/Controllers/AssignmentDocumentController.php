<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentDocument;
use App\Models\AssignmentProgressUpdate;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AssignmentDocumentController extends Controller
{
    public function download(AssignmentDocument $document)
    {
        $documentable = $document->documentable;

        $assignment = $documentable instanceof Assignment
            ? $documentable
            : ($documentable instanceof AssignmentProgressUpdate ? $documentable->assignment : null);

        abort_unless($assignment, 404);

        Gate::authorize('view', $assignment);
        abort_unless(auth()->user()->can('download-assignment-documents'), 403);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        AuditLogger::record($documentable, 'document.downloaded', [
            'document' => $document->original_name,
        ], $assignment->organization_id);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }
}
