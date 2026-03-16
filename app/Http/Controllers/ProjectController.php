<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\SyncProjectPersonsRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Department;
use App\Models\DepartmentSubCategory;
use App\Models\PersonAffiliation;
use App\Models\Project;
use App\Models\ProjectAffiliation;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless($user && $user->can('view-projects'), 403);

        $query = Project::query()->with(['department.organization', 'admin']);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->integer('department_id'));
        }

        if (!$request->boolean('include_inactive', false)) {
            $query->where('is_active', true);
        }

        if (!$user->hasRole('Super Admin')) {
            $query->whereIn('department_id', $this->allowedDepartmentIds($user));
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(StoreProjectRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $projectDepartments = $data['project_departments'] ?? [];
        unset($data['project_departments']);

        $this->resolveProjectSubCategory($data);

        abort_unless(
            $user->hasRole('Super Admin') || in_array($data['department_id'], $this->allowedDepartmentIds($user), true),
            403,
            'You cannot create projects for this department.'
        );

        $project = Project::create($data);
        $this->syncProjectDepartments($project, $projectDepartments);

        return response()->json($project->load(['department.organization', 'departmentSubCategory', 'admin', 'projectDepartments']), 201);
    }

    public function show(Project $project, Request $request)
    {
        $this->authorizeProjectView($request->user(), $project);

        return response()->json(
            $project->load([
                'department.organization',
                'admin',
                'affiliations.person',
            ])
        );
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $user = $request->user();
        $data = $request->validated();
        $projectDepartments = $data['project_departments'] ?? null;
        unset($data['project_departments']);

        $this->resolveProjectSubCategory($data);

        $this->authorizeProjectAdminAction($user, $project);

        abort_unless(
            $user->hasRole('Super Admin') || in_array($data['department_id'], $this->allowedDepartmentIds($user), true),
            403,
            'You cannot move this project to that department.'
        );

        $project->update($data);

        if (is_array($projectDepartments)) {
            $this->syncProjectDepartments($project, $projectDepartments);
        }

        return response()->json($project->load(['department.organization', 'departmentSubCategory', 'admin', 'projectDepartments']));
    }

    public function destroy(Project $project, Request $request)
    {
        $user = $request->user();

        abort_unless($user && $user->can('delete-projects'), 403);
        $this->authorizeProjectAdminAction($user, $project);

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }

    public function syncPersons(SyncProjectPersonsRequest $request, Project $project)
    {
        $user = $request->user();

        $this->authorizeProjectPersonsAction($user, $project);

        $payload = collect($request->validated()['affiliations'])->map(function (array $item) use ($user, $project) {
            $isStaff = $item['affiliation_type'] === 'staff';

            if ($isStaff) {
                $personOrgIds = PersonAffiliation::query()
                    ->where('person_id', $item['person_id'])
                    ->where('status', 'active')
                    ->pluck('organization_id')
                    ->all();

                if (!in_array($project->department->organization_id, $personOrgIds, true)) {
                    abort(422, 'Staff must belong to the same organization as the project department.');
                }
            }

            return [
                'project_id' => $project->id,
                'person_id' => $item['person_id'],
                'affiliation_type' => $item['affiliation_type'],
                'role_title' => $item['role_title'] ?? null,
                'occupation' => $item['occupation'] ?? null,
                'start_date' => $item['start_date'] ?? null,
                'end_date' => $item['end_date'] ?? null,
                'status' => $item['status'] ?? 'active',
                'updated_by' => $user->id,
                'created_by' => $user->id,
            ];
        });

        foreach ($payload as $item) {
            ProjectAffiliation::query()->updateOrCreate(
                [
                    'project_id' => $item['project_id'],
                    'person_id' => $item['person_id'],
                    'affiliation_type' => $item['affiliation_type'],
                ],
                $item
            );
        }

        return response()->json(
            $project->fresh()->load(['affiliations.person'])
        );
    }

    private function authorizeProjectView($user, Project $project): void
    {
        abort_unless($user && $user->can('view-projects'), 403);

        if ($user->hasRole('Super Admin')) {
            return;
        }

        abort_unless(in_array($project->department_id, $this->allowedDepartmentIds($user), true), 403);
    }

    private function authorizeProjectAdminAction($user, Project $project): void
    {
        abort_unless($user && $user->can('edit-projects'), 403);

        if ($user->hasRole('Super Admin')) {
            return;
        }

        abort_unless(in_array($project->department_id, $this->allowedDepartmentIds($user), true), 403);
    }

    private function authorizeProjectPersonsAction($user, Project $project): void
    {
        abort_unless($user && $user->can('manage-project-persons'), 403);

        if ($user->hasRole('Super Admin')) {
            return;
        }

        abort_unless(in_array($project->department_id, $this->allowedDepartmentIds($user), true), 403);
    }

    private function allowedDepartmentIds($user): array
    {
        if ($user->hasRole('Super Admin')) {
            return Department::query()->pluck('id')->all();
        }

        $organizationIds = [];

        if ($user->person) {
            $organizationIds = $user->person
                ->affiliations()
                ->where('status', 'active')
                ->pluck('organization_id')
                ->all();
        }

        $departmentIds = Department::query()
            ->where(function ($query) use ($user, $organizationIds) {
                $query->where('admin_user_id', $user->id)
                    ->orWhereHas('projects', function ($projectQuery) use ($user) {
                        $projectQuery->where('admin_user_id', $user->id);
                    });

                if (!empty($organizationIds) && $user->can('assign-project-admins')) {
                    $query->orWhereIn('organization_id', $organizationIds);
                }
            })
            ->pluck('id')
            ->all();

        return array_values(array_unique($departmentIds));
    }

    private function syncProjectDepartments(Project $project, array $projectDepartments): void
    {
        $normalized = collect($projectDepartments)
            ->map(function (array $item) {
                return [
                    'name' => trim((string) ($item['name'] ?? '')),
                    'is_active' => array_key_exists('is_active', $item) ? (bool) $item['is_active'] : true,
                ];
            })
            ->filter(fn (array $item) => $item['name'] !== '')
            ->unique('name')
            ->values();

        $project->projectDepartments()->delete();

        if ($normalized->isNotEmpty()) {
            $project->projectDepartments()->createMany($normalized->all());
        }
    }

    private function resolveProjectSubCategory(array &$data): void
    {
        $departmentId = (int) ($data['department_id'] ?? 0);
        $subCategoryId = $data['department_sub_category_id'] ?? null;
        $subCategoryText = isset($data['sub_category']) ? trim((string) $data['sub_category']) : '';

        if ($departmentId <= 0) {
            return;
        }

        if ($subCategoryId) {
            $subCategory = DepartmentSubCategory::query()
                ->where('id', $subCategoryId)
                ->where('department_id', $departmentId)
                ->first();

            if (!$subCategory) {
                throw ValidationException::withMessages([
                    'department_sub_category_id' => 'Selected category does not belong to the selected department.',
                ]);
            }

            $data['department_sub_category_id'] = $subCategory->id;
            $data['sub_category'] = $subCategory->name;

            return;
        }

        if ($subCategoryText !== '') {
            $subCategory = DepartmentSubCategory::query()->firstOrCreate(
                [
                    'department_id' => $departmentId,
                    'name' => $subCategoryText,
                ],
                [
                    'is_active' => true,
                ]
            );

            $data['department_sub_category_id'] = $subCategory->id;
            $data['sub_category'] = $subCategory->name;

            return;
        }

        $organizationCategory = Department::query()
            ->where('id', $departmentId)
            ->with('organization:id,category')
            ->first()
            ?->organization
            ?->category;

        if (is_string($organizationCategory) && trim($organizationCategory) !== '') {
            $fallbackCategory = trim($organizationCategory);

            $subCategory = DepartmentSubCategory::query()->firstOrCreate(
                [
                    'department_id' => $departmentId,
                    'name' => $fallbackCategory,
                ],
                [
                    'is_active' => true,
                ]
            );

            $data['department_sub_category_id'] = $subCategory->id;
            $data['sub_category'] = $subCategory->name;

            return;
        }

        $data['department_sub_category_id'] = null;
        $data['sub_category'] = null;
    }
}
