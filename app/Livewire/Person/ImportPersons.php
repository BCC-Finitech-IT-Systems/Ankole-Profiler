<?php

namespace App\Livewire\Person;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Organization;
use App\Models\Person;
use App\Services\PersonImportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Validators\ValidationException;

class ImportPersons extends Component
{
    use WithFileUploads;

    // File upload
    public $importFile;
    public $uploadProgress = 0;

    // Import results
    public $importResults = null;
    public $isProcessing = false;
    public $showResults = false;

    // Organization context
    public $currentOrganization;
    public $selectedOrganizationId;
    public $availableOrganizations = [];
    public $isSuperAdmin = false;

    // Import options
    public $skipDuplicates = true;
    public $updateExisting = false;
    public $defaultRoleType = 'STAFF';

    // Available role types based on organization
    public $availableRoles = [];

    // Validation progress
    public $validationErrors = [];
    public $previewData = [];
    public $showPreview = false;

    protected $rules = [
        'importFile' => 'required|file|mimes:csv,xlsx,xls|max:10240', // 10MB max
        'selectedOrganizationId' => 'required_if:isSuperAdmin,true|exists:Organizations,id',
        'defaultRoleType' => 'required|string',
        'skipDuplicates' => 'boolean',
        'updateExisting' => 'boolean',
    ];

    protected $messages = [
        'importFile.required' => 'Please select a file to import.',
        'importFile.mimes' => 'File must be a CSV or Excel file (.csv, .xlsx, .xls).',
        'importFile.max' => 'File size cannot exceed 10MB.',
        'selectedOrganizationId.required_if' => 'Please select an organization.',
    ];

    public function mount()
    {
        $this->initializeOrganizationContext();
    }

    private function initializeOrganizationContext()
    {
        $user = Auth::user();

        // Check if user is Super Admin
        $this->isSuperAdmin = false;
        if ($user) {
            try {
                if (method_exists($user, 'hasRole')) {
                    $this->isSuperAdmin = $user->hasRole('Super Admin');
                } elseif (method_exists($user, 'roles')) {
                    $this->isSuperAdmin = $user->roles()->where('name', 'Super Admin')->exists();
                }
            } catch (\Exception $e) {
                Log::warning('Error checking Super Admin role: ' . $e->getMessage());
            }
        }

        if ($this->isSuperAdmin) {
            // Load all organizations for Super Admin
            $this->availableOrganizations = Organization::orderBy('legal_name')->get()->toArray();

            // Set current organization from session or default to first
            $currentOrgId = session('current_organization_id');
            if ($currentOrgId) {
                $this->selectedOrganizationId = $currentOrgId;
                $this->currentOrganization = Organization::find($currentOrgId);
            } elseif (!empty($this->availableOrganizations)) {
                $this->selectedOrganizationId = $this->availableOrganizations[0]['id'];
                $this->currentOrganization = Organization::find($this->selectedOrganizationId);
            }
        } else {
            // Regular users use current organization context
            $this->currentOrganization = $this->getCurrentUserOrganization();
            if ($this->currentOrganization) {
                $this->selectedOrganizationId = $this->currentOrganization->id;
            }
        }
    }

    private function getCurrentUserOrganization()
    {
        // Use the new helper function for better organization detection
        return user_current_organization();
    }

    public function updatedSelectedOrganizationId()
    {
        if ($this->selectedOrganizationId) {
            $this->currentOrganization = Organization::find($this->selectedOrganizationId);
            $this->resetImportState();
        }
    }

    public function updatedImportFile()
    {
        $this->resetImportState();

        if ($this->importFile) {
            $this->validate(['importFile' => $this->rules['importFile']]);
            $this->previewFile();
        }
    }

    private function resetImportState()
    {
        $this->importResults = null;
        $this->showResults = false;
        $this->validationErrors = [];
        $this->previewData = [];
        $this->showPreview = false;
    }

    public function previewFile()
    {
        try {
            $this->isProcessing = true;

            $service = new PersonImportService();
            $preview = $service->previewImport($this->importFile->getRealPath());

            $this->previewData = $preview['data'];
            $this->validationErrors = $preview['errors'];
            $this->showPreview = true;

        } catch (\Exception $e) {
            $this->addError('importFile', 'Error reading file: ' . $e->getMessage());
            Log::error('ImportPersons: Preview error - ' . $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }

    public function importPersons()
    {
        $this->validate();

        if (empty($this->previewData)) {
            $this->addError('importFile', 'Please upload and preview a file first.');
            return;
        }

        try {
            $this->isProcessing = true;

            $service = new PersonImportService();
            $this->importResults = $service->import(
                $this->importFile->getRealPath(),
                [
                    'organization_id' => $this->selectedOrganizationId,
                    'default_role_type' => $this->defaultRoleType,
                    'skip_duplicates' => $this->skipDuplicates,
                    'update_existing' => $this->updateExisting,
                    'created_by' => Auth::id(),
                ]
            );

            $this->showResults = true;
            $this->showPreview = false;

            // Flash success message
            $successCount = $this->importResults['summary']['success'] ?? 0;
            $failedCount = $this->importResults['summary']['failed'] ?? 0;

            if ($failedCount > 0) {
                session()->flash('message', "Import completed: {$successCount} successful, {$failedCount} failed. Check details below.");
            } else {
                session()->flash('message', "Successfully imported {$successCount} persons!");
                return redirect()->route('persons.all');
            }

        } catch (ValidationException $e) {
            // Handle Laravel Excel validation errors
            $this->handleValidationException($e);
        } catch (\Exception $e) {
            $this->addError('import', 'Import failed: ' . $e->getMessage());
            Log::error('ImportPersons: Import error - ' . $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }

    private function handleValidationException(ValidationException $e)
    {
        $failures = $e->failures();
        $errorMessages = [];

        foreach ($failures as $failure) {
            $row = $failure->row();
            $attribute = $failure->attribute();
            $errors = implode(', ', $failure->errors());
            $errorMessages[] = "Row {$row}, Column '{$attribute}': {$errors}";
        }

        // Show first few errors in the main error, rest in session
        $mainErrors = array_slice($errorMessages, 0, 3);
        $remainingCount = count($errorMessages) - 3;

        $message = 'Validation errors found:' . PHP_EOL . implode(PHP_EOL, $mainErrors);
        if ($remainingCount > 0) {
            $message .= PHP_EOL . "... and {$remainingCount} more errors. Check the detailed results below.";
        }

        $this->addError('import', $message);

        // Store all validation errors for display
        $this->validationErrors = $errorMessages;
        $this->showResults = true; // Show results section to display validation errors

        Log::error('ImportPersons: Validation errors - ' . implode(' | ', $errorMessages));
    }

    public function downloadTemplate()
    {
        try {
            // For non-Super Admin users, ensure we use their current organization
            if (!$this->isSuperAdmin) {
                $this->currentOrganization = user_current_organization();
                if ($this->currentOrganization) {
                    $this->selectedOrganizationId = $this->currentOrganization->id;
                }
            }

            if (!$this->currentOrganization) {
                $message = $this->isSuperAdmin
                    ? 'Please select an organization first.'
                    : 'Unable to determine your current organization. Please contact administrator.';
                session()->flash('error', $message);
                return;
            }

            $service = new PersonImportService();

            $templatePath = $service->generateExcelTemplateFile(
                $this->currentOrganization->category,
                $this->currentOrganization->display_name ?? $this->currentOrganization->legal_name
            );

            return response()->download($templatePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            session()->flash('error', 'Error generating template: ' . $e->getMessage());
            Log::error('ImportPersons: Template generation error - ' . $e->getMessage());
        }
    }

    public function resetImport()
    {
        $this->reset([
            'importFile',
            'importResults',
            'isProcessing',
            'showResults',
            'validationErrors',
            'previewData',
            'showPreview'
        ]);

        // Clear any error bags
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.person.import-persons');
    }
}
