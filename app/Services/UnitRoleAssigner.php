<?php

namespace App\Services;

use App\Models\OrganizationUnit;
use App\Models\RoleType;

class UnitRoleAssigner
{
    /**
     * Resolve (or create) the MEMBER RoleType usable for the unit.
     *
     * role_types.code is globally unique, so a department-scoped MEMBER and
     * a global MEMBER cannot coexist. Prefer the unit's department-scoped
     * row, fall back to the global one, and create a global MEMBER only
     * when no row exists at all. Returns null when MEMBER exists but is
     * scoped to a different department.
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
        }

        $global = RoleType::active()
            ->whereNull('department_id')
            ->where('code', 'MEMBER')
            ->first();

        if ($global) {
            return $global;
        }

        if (!RoleType::where('code', 'MEMBER')->exists()) {
            return RoleType::create([
                'code'   => 'MEMBER',
                'name'   => 'Member',
                'active' => true,
            ]);
        }

        return null;
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
