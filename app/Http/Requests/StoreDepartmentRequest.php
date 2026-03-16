<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) $user && ($user->hasRole('Super Admin') || $user->can('create-departments'));
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->where(function ($query) {
                    return $query->where('organization_id', $this->input('organization_id'));
                }),
            ],
            'sub_categories' => ['nullable', 'array'],
            'sub_categories.*' => ['string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('departments', 'code')->where(function ($query) {
                    return $query
                        ->where('organization_id', $this->input('organization_id'))
                        ->whereNotNull('code');
                }),
            ],
            'description' => ['nullable', 'string'],
            'admin_user_id' => ['nullable', 'exists:users,id'],
            'legacy_organization_unit_id' => ['nullable', 'exists:organization_units,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
