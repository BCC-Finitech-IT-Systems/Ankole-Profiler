<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncProjectPersonsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage-project-persons');
    }

    public function rules(): array
    {
        return [
            'affiliations' => ['required', 'array', 'min:1'],
            'affiliations.*.person_id' => ['required', 'exists:persons,id'],
            'affiliations.*.affiliation_type' => ['required', Rule::in(['staff', 'person', 'associate'])],
            'affiliations.*.role_title' => ['nullable', 'string', 'max:255'],
            'affiliations.*.occupation' => ['nullable', 'string', 'max:255'],
            'affiliations.*.start_date' => ['nullable', 'date'],
            'affiliations.*.end_date' => ['nullable', 'date', 'after_or_equal:affiliations.*.start_date'],
            'affiliations.*.status' => ['nullable', Rule::in(['active', 'inactive', 'suspended', 'terminated'])],
        ];
    }
}
