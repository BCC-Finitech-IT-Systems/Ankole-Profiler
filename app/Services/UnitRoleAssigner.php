<?php

namespace App\Services;

use App\Models\OrganizationUnit;
use App\Models\RoleType;

class UnitRoleAssigner
{
    /**
     * Resolve (or create) the MEMBER RoleType for the unit's department.
     *
     * If the unit has no department_id the globally-scoped MEMBER code is used
     * as a fallback. Returns null only if neither exists and creation fails.
     */
    public function memberRoleTypeFor(OrganizationUnit $unit): ?RoleType
    {
        if ($unit->department_id) {
            $roleType = RoleType::active()
                ->forDepartment($unit->department_id)
                ->where('code', 'MEMBER')
                ->first();

            if ($roleType) {
                return $roleType;
            }

            // Auto-create a department-scoped MEMBER type so the workflow
            // never blocks on missing seed data.
            return RoleType::create([
                'department_id' => $unit->department_id,
                'code'          => 'MEMBER',
                'name'          => 'Member',
                'active'        => true,
            ]);
        }

        // No department: use/create a global MEMBER code.
        return RoleType::firstOrCreate(
            ['code' => 'MEMBER', 'department_id' => null],
            ['name' => 'Member', 'active' => true]
        );
    }

    /**
     * Verify a RoleType is valid for the given unit (department-scoped or global).
     */
    public function isValidForUnit(RoleType $roleType, OrganizationUnit $unit): bool
    {
        if ($roleType->department_id === null) {
            return true; // global codes are always valid
        }

        return $unit->department_id !== null
            && $roleType->department_id === $unit->department_id;
    }
}
