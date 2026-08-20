<?php

namespace App\Livewire\Organizations;

use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrganizationTemplateExport;
use App\Imports\OrganizationsImport;
use App\Models\CustomField;
use Illuminate\Support\Facades\Storage;

class ImportOrganizations extends Component
{
    use WithFileUploads;

    public $file;
    public $message;
    public $results = null;

    protected $rules = [
        'file' => 'required|file|mimes:xlsx,csv',
    ];

    public function import()
    {
        $this->validate();

        $this->message = null;
        $this->results = null;

        $path = $this->file->store('imports');

        $import = new OrganizationsImport();
        Excel::import($import, $path);

        Storage::delete($path);

        $this->results = $import->getResults();
        $this->message = "Imported {$this->results['summary']['success']} of {$this->results['summary']['total']} organizations"
            . ($this->results['summary']['failed'] > 0 ? ", {$this->results['summary']['failed']} failed." : '.');

        $this->reset('file');
    }

    public function exportCustomTemplate($fields)
    {
        $headers = array_map('trim', $fields);

        // Save custom fields to custom_fields table
        foreach ($fields as $field) {
            CustomField::updateOrCreate([
                'model_type' => 'Organization_template',
                'model_id' => 0,
                'field_name' => $field,
            ], [
                'field_label' => ucfirst(str_replace('_', ' ', $field)),
                'field_type' => 'string',
                'field_options' => null,
                'is_required' => false,
                'validation_rules' => null,
                'group' => null,
                'order' => null,
                'description' => null,
            ]);
        }

        $export = new OrganizationTemplateExport([], $headers);
        return Excel::download($export, 'custom_Organization_template.xlsx');
    }

    public function render()
    {
        return view('livewire.organizations.import-organizations');
    }
}
