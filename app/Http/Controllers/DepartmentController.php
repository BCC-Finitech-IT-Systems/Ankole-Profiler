<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    private function hasPermissionOrSuperAdmin($user, string $permission): bool
    {
        return (bool) $user && ($user->hasRole('Super Admin') || $user->can($permission));
    }

    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless($this->hasPermissionOrSuperAdmin($user, 'view-departments'), 403);

        $query = Department::query()->with(['organization', 'admin']);

        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->integer('organization_id'));
        }

        if (!$request->boolean('include_inactive', false)) {
            $query->where('is_active', true);
        }

        if (!$user->hasRole('Super Admin')) {
            $query->where(function ($departmentQuery) use ($user) {
                $departmentQuery
                    ->whereIn('organization_id', $this->allowedOrganizationIds($user))
                    ->orWhere('admin_user_id', $user->id);
            });
        }

        $departments = $query->latest()->paginate(20)->withQueryString();

        return view('departments.index', [
            'departments' => $departments,
        ]);
    }

    public function store(StoreDepartmentRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        $subCategories = $data['sub_categories'] ?? [];
        unset($data['sub_categories']);

        abort_unless(
            $user->hasRole('Super Admin') || in_array($data['organization_id'], $this->allowedOrganizationIds($user), true),
            403,
            'You cannot create departments for this organization.'
        );

        $department = Department::create($data);

        $this->syncDepartmentSubCategories($department, $subCategories);

        return response()->json($department->load(['organization', 'admin', 'subCategories']), 201);
    }

    public function show(Department $department, Request $request)
    {
        $this->authorizeDepartmentView($request->user(), $department);

        return response()->json(
            $department->load([
                'organization',
                'admin',
                'projects.admin',
            ])
        );
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $user = $request->user();
        $data = $request->validated();
        $subCategories = $data['sub_categories'] ?? null;
        unset($data['sub_categories']);

        $this->authorizeDepartmentAdminAction($user, $department);

        abort_unless(
            $user->hasRole('Super Admin') || in_array($data['organization_id'], $this->allowedOrganizationIds($user), true),
            403,
            'You cannot move this department to that organization.'
        );

        $department->update($data);

        if (is_array($subCategories)) {
            $this->syncDepartmentSubCategories($department, $subCategories);
        }

        return response()->json($department->load(['organization', 'admin', 'subCategories']));
    }

    public function destroy(Department $department, Request $request)
    {
        $user = $request->user();

        abort_unless($this->hasPermissionOrSuperAdmin($user, 'delete-departments'), 403);
        $this->authorizeDepartmentAdminAction($user, $department);

        $department->forceDelete();

        return response()->json([
            'message' => 'Department deleted successfully.',
        ]);
    }

    private function authorizeDepartmentView($user, Department $department): void
    {
        abort_unless($this->hasPermissionOrSuperAdmin($user, 'view-departments'), 403);

        if ($user->hasRole('Super Admin')) {
            return;
        }

        abort_unless(
            in_array($department->organization_id, $this->allowedOrganizationIds($user), true) || $department->admin_user_id === $user->id,
            403
        );
    }

    private function authorizeDepartmentAdminAction($user, Department $department): void
    {
        abort_unless($this->hasPermissionOrSuperAdmin($user, 'edit-departments'), 403);

        if ($user->hasRole('Super Admin')) {
            return;
        }

        abort_unless(
            in_array($department->organization_id, $this->allowedOrganizationIds($user), true) || $department->admin_user_id === $user->id,
            403
        );
    }

    private function allowedOrganizationIds($user): array
    {
        if ($user->hasRole('Super Admin')) {
            return \App\Models\Organization::query()->pluck('id')->all();
        }

        if (!$user->person) {
            return [];
        }

        return $user->person
            ->affiliations()
            ->where('status', 'active')
            ->pluck('organization_id')
            ->all();
    }

    private function syncDepartmentSubCategories(Department $department, array $subCategories): void
    {
        $normalized = collect($subCategories)
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn (string $item) => $item !== '')
            ->unique()
            ->values();

        $department->subCategories()->delete();

        if ($normalized->isNotEmpty()) {
            $department->subCategories()->createMany(
                $normalized->map(fn (string $name) => ['name' => $name, 'is_active' => true])->all()
            );
        }
    }
}
