<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) $user && ($user->hasRole('Super Admin') || $user->can('edit-departments'));
    }

    public function rules(): array
    {
        /** @var Department|null $department */
        $department = $this->route('department');
        $departmentId = $department?->id;

        return [
            'organization_id' => ['required', 'exists:organizations,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')
                    ->where(function ($query) {
                        return $query->where('organization_id', $this->input('organization_id'));
                    })
                    ->ignore($departmentId),
            ],
            'sub_categories' => ['nullable', 'array'],
            'sub_categories.*' => ['string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('departments', 'code')
                    ->where(function ($query) {
                        return $query
                            ->where('organization_id', $this->input('organization_id'))
                            ->whereNotNull('code');
                    })
                    ->ignore($departmentId),
            ],
            'description' => ['nullable', 'string'],
            'admin_user_id' => ['nullable', 'exists:users,id'],
            'legacy_organization_unit_id' => ['nullable', 'exists:organization_units,id'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
