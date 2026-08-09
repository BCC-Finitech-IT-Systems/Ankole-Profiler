<?php

namespace App\Http\Controllers;

use App\Models\LandDocument;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class LandDocumentController extends Controller
{
    public function download(LandDocument $document)
    {
        Gate::authorize('download', $document);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        AuditLogger::record($document, 'document.downloaded', [
            'document' => $document->original_name,
        ], $document->parcel->organization_id);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }
}
