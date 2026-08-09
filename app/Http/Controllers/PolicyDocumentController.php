<?php

namespace App\Http\Controllers;

use App\Models\PolicyDocument;
use App\Models\PolicyVersion;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class PolicyDocumentController extends Controller
{
    /**
     * Download the approved policy document for a version. Never served via
     * asset()/Storage::url() — files live on the private 'local' disk and
     * must stay unreachable without this gate, which is what makes
     * restricted-audience visibility and download auditing possible.
     */
    public function version(PolicyVersion $version)
    {
        Gate::authorize('download', $version);

        abort_unless($version->document_path && Storage::disk('local')->exists($version->document_path), 404);

        AuditLogger::record($version, 'document.downloaded', [
            'document' => $version->document_original_name,
        ], $version->policy->organization_id);

        return Storage::disk('local')->download($version->document_path, $version->document_original_name);
    }

    public function attachment(PolicyDocument $document)
    {
        $documentable = $document->documentable;

        // Attachments hang off either a PolicyVersion (supporting docs) or a
        // PolicyPublication (adoption evidence); resolve the version to
        // authorize against for both cases.
        $version = $documentable instanceof PolicyVersion ? $documentable : $documentable->policyVersion;

        Gate::authorize('download', $version);

        abort_unless(Storage::disk('local')->exists($document->path), 404);

        AuditLogger::record($documentable, 'document.downloaded', [
            'document' => $document->original_name,
        ], $version->policy->organization_id);

        return Storage::disk('local')->download($document->path, $document->original_name);
    }
}
