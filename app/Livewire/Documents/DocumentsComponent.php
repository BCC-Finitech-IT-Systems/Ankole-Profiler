<?php

namespace App\Livewire\Documents;

use Livewire\Component;
use App\Models\Document;

class DocumentsComponent extends Component
{
    public $documents;

    public function mount()
    {
        $this->documents = Document::all();
    }

    public function render()
    {
        return view('livewire.documents.documents-component');
    }
}
