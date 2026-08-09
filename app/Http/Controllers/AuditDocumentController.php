<?php

namespace App\Http\Controllers;

use App\Models\AuditDocument;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class AuditDocumentController extends Controller
{
    public function download(AuditDocument $document)
    {
        Gate::authorize('download', $document);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        AuditLogger::record($document->report, 'audit_document.downloaded', [
            'document' => $document->original_name,
        ], $document->report->organization_id);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }
}
