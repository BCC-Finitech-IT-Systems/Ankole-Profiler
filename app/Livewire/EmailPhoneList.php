<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Person;
use App\Models\Organization;

class EmailPhoneList extends Component
{
    use WithPagination;

    public $search = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $persons = Person::with(['emailAddresses', 'phones'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('given_name', 'like', '%' . $this->search . '%')
                      ->orWhere('family_name', 'like', '%' . $this->search . '%')
                      ->orWhereHas('emailAddresses', fn ($q) => $q->where('email', 'like', '%' . $this->search . '%'))
                      ->orWhereHas('phones', fn ($q) => $q->where('number', 'like', '%' . $this->search . '%'));
                });
            })
            ->orderBy('family_name')
            ->paginate(15);

        $organizations = Organization::select('legal_name', 'contact_email', 'contact_phone')
            ->where(function ($q) {
                $q->whereNotNull('contact_email')->orWhereNotNull('contact_phone');
            })
            ->orderBy('legal_name')
            ->get();

        return view('livewire.email-phone-list', [
            'persons' => $persons,
            'organizations' => $organizations,
        ]);
    }
}
