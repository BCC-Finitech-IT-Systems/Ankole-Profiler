<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('edit-projects');
    }

    public function rules(): array
    {
        /** @var Project|null $project */
        $project = $this->route('project');
        $projectId = $project?->id;

        return [
            'department_id' => ['required', 'exists:departments,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects', 'name')
                    ->where(function ($query) {
                        return $query->where('department_id', $this->input('department_id'));
                    })
                    ->ignore($projectId),
            ],
            'department_sub_category_id' => [
                'nullable',
                'exists:department_sub_categories,id',
                function ($attribute, $value, $fail) {
                    if (!$value || !$this->input('department_id')) {
                        return;
                    }

                    $belongsToDepartment = \App\Models\DepartmentSubCategory::query()
                        ->where('id', $value)
                        ->where('department_id', $this->input('department_id'))
                        ->exists();

                    if (!$belongsToDepartment) {
                        $fail('Selected category does not belong to the selected department.');
                    }
                },
            ],
            'sub_category' => ['nullable', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('projects', 'code')
                    ->where(function ($query) {
                        return $query->where('department_id', $this->input('department_id'));
                    })
                    ->ignore($projectId),
            ],
            'description' => ['nullable', 'string'],
            'admin_user_id' => ['nullable', 'exists:users,id'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'is_active' => ['nullable', 'boolean'],
            'project_departments' => ['nullable', 'array'],
            'project_departments.*.name' => ['required_with:project_departments', 'string', 'max:255'],
            'project_departments.*.is_active' => ['nullable', 'boolean'],
        ];
    }
}
