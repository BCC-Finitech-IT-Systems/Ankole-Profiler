<?php

namespace App\Livewire\Person;

use Livewire\Component;
use App\Models\Organization;
use App\Services\PersonExportService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExportPersons extends Component
{
    // Organization context
    public $currentOrganization;
    public $selectedOrganizationId;
    public $availableOrganizations = [];
    public $isSuperAdmin = false;

    // Export options
    public $exportFormat = 'xlsx';
    public $includeFields = [];
    public $filters = [];

    // UI state
    public $isProcessing = false;
    public $showAdvancedFilters = false;
    public $exportStats = [];
    public $availableFilterOptions = [];
    public $availableFieldOptions = [];

    protected $rules = [
        'selectedOrganizationId' => 'required_if:isSuperAdmin,true|exists:Organizations,id',
        'exportFormat' => 'required|in:xlsx,csv',
        'includeFields' => 'required|array|min:1',
        'filters.role_type' => 'nullable|string',
        'filters.gender' => 'nullable|in:male,female,other,prefer_not_to_say',
        'filters.status' => 'nullable|in:active,inactive,suspended',
        'filters.age_from' => 'nullable|numeric|min:0|max:120',
        'filters.age_to' => 'nullable|numeric|min:0|max:120|gte:filters.age_from',
        'filters.city' => 'nullable|string|max:255',
        'filters.district' => 'nullable|string|max:255',
    ];

    protected $messages = [
        'selectedOrganizationId.required_if' => 'Please select an organization.',
        'includeFields.required' => 'Please select at least one field to export.',
        'includeFields.min' => 'Please select at least one field to export.',
        'filters.age_to.gte' => 'Age To must be greater than or equal to Age From.',
    ];

    public function mount()
    {
        $this->initializeOrganizationContext();
        $this->setDefaultFields();
        $this->loadExportOptions();
        $this->loadStats();
    }

    public function updatedSelectedOrganizationId()
    {
        if ($this->selectedOrganizationId) {
            $this->currentOrganization = Organization::find($this->selectedOrganizationId);
            $this->loadExportOptions();
            $this->setDefaultFields();
            $this->loadStats();
        }
    }

    public function updatedFilters()
    {
        $this->loadStats();
    }

    public function exportPersons()
    {
        $this->validate();

        try {
            $this->isProcessing = true;

            $service = new PersonExportService();

            $organizationId = $this->isSuperAdmin ? $this->selectedOrganizationId : $this->currentOrganization?->id;

            if ($this->exportFormat === 'xlsx') {
                $filePath = $service->exportToExcel($organizationId, $this->filters, $this->includeFields);
            } else {
                $filePath = $service->exportToCsv($organizationId, $this->filters, $this->includeFields);
            }

            // Log the export activity
            $orgName = $this->currentOrganization?->legal_name ?? 'All Organizations';
            Log::info('Person export completed', [
                'user_id' => Auth::id(),
                'organization' => $orgName,
                'format' => $this->exportFormat,
                'fields' => $this->includeFields,
                'filters' => $this->filters
            ]);

            session()->flash('message', 'Export completed successfully! Download will start automatically.');

            return response()->download($filePath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            $this->addError('export', 'Export failed: ' . $e->getMessage());
            Log::error('PersonExport: Export error - ' . $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }

    public function toggleAdvancedFilters()
    {
        $this->showAdvancedFilters = !$this->showAdvancedFilters;
    }

    public function clearFilters()
    {
        $this->filters = [];
        $this->loadStats();
    }

    public function selectAllFields()
    {
        $this->includeFields = array_keys($this->availableFieldOptions);
    }

    public function selectDefaultFields()
    {
        $this->setDefaultFields();
    }

    public function render()
    {
        return view('livewire.person.export-persons');
    }

    private function initializeOrganizationContext()
    {
        $user = Auth::user();

        // Check if the user has the required role
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            $this->isSuperAdmin = true;
        }

        if ($this->isSuperAdmin) {
            // Load all organizations for Super Admin
            $this->availableOrganizations = Organization::orderBy('legal_name')->get()->toArray();

            // Set current organization from session or default to first
            $currentOrgId = current_organization_id();
            if ($currentOrgId) {
                $this->selectedOrganizationId = $currentOrgId;
                $this->currentOrganization = Organization::find($currentOrgId);
            } elseif (!empty($this->availableOrganizations)) {
                $this->selectedOrganizationId = $this->availableOrganizations[0]['id'];
                $this->currentOrganization = Organization::find($this->selectedOrganizationId);
            }
        } else {
            // Regular users use current organization context
            $this->currentOrganization = current_Organization();
            if ($this->currentOrganization) {
                $this->selectedOrganizationId = $this->currentOrganization->id;
            }
        }
    }

    private function getCurrentUserOrganization()
    {
        // Use helper function
        return current_Organization();
    }

    private function loadExportOptions()
    {
        $service = new PersonExportService();

        $organizationId = $this->isSuperAdmin ? $this->selectedOrganizationId : $this->currentOrganization?->id;

        $this->availableFieldOptions = $service->getAvailableFields($organizationId);
        $this->availableFilterOptions = $service->getAvailableFilters($organizationId);
    }

    private function setDefaultFields()
    {
        $this->includeFields = [];
        foreach ($this->availableFieldOptions as $key => $field) {
            if ($field['default']) {
                $this->includeFields[] = $key;
            }
        }
    }

    private function loadStats()
    {
        try {
            $service = new PersonExportService();
            $organizationId = $this->isSuperAdmin ? $this->selectedOrganizationId : $this->currentOrganization?->id;

            $this->exportStats = $service->getExportStats($organizationId, $this->filters);
        } catch (\Exception $e) {
            Log::error('Error loading export stats: ' . $e->getMessage());
            $this->exportStats = ['total_persons' => 0];
        }
    }
}
