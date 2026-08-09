<?php

namespace App\Http\Controllers;

use App\Models\WorkplanActivity;
use App\Models\WorkplanDocument;
use App\Models\WorkplanProgressUpdate;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class WorkplanDocumentController extends Controller
{
    public function download(WorkplanDocument $document)
    {
        $documentable = $document->documentable;

        $activity = $documentable instanceof WorkplanActivity
            ? $documentable
            : ($documentable instanceof WorkplanProgressUpdate ? $documentable->activity : null);

        abort_unless($activity, 404);

        Gate::authorize('download', $activity);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        AuditLogger::record($documentable, 'document.downloaded', [
            'document' => $document->original_name,
        ], $activity->workplan->organization_id);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }
}
