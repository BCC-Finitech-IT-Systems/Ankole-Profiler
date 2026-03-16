<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(
            ['legal_name' => 'Ankole Diocese'],
            [
                'display_name' => 'Ankole Diocese',
                'code' => $this->generateUniqueOrganizationCode('ANKDIO'),
                'category' => 'other',
                'organization_type' => 'branch',
                'country' => 'UGA',
                'country_of_registration' => 'UGA',
                'is_active' => true,
                'is_super' => true,
            ]
        );

        $departmentNames = [
            'Education',
            'Health',
            'Finance and Investment',
            'Audit and Assurance',
            'Mission and Outreach',
            'Human Resource',
            'HouseHold and Community',
        ];

        foreach ($departmentNames as $departmentName) {
            $department = Department::withTrashed()->firstOrNew([
                'organization_id' => $organization->id,
                'name' => $departmentName,
            ]);

            $department->code = Str::upper(Str::slug($departmentName, '_'));
            $department->description = $departmentName . ' department for Ankole Diocese.';
            $department->is_active = true;
            $department->save();

            if (method_exists($department, 'trashed') && $department->trashed()) {
                $department->restore();
            }
        }
    }

    private function generateUniqueOrganizationCode(string $baseCode): string
    {
        $code = $baseCode;
        $counter = 1;

        while (Organization::query()->where('code', $code)->exists()) {
            $code = $baseCode . str_pad((string) $counter, 2, '0', STR_PAD_LEFT);
            $counter++;
        }

        return $code;
    }
}
