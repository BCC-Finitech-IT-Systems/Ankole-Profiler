<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentSubCategory;
use Illuminate\Database\Seeder;

class DepartmentSubCategorySeeder extends Seeder
{
    /**
     * Seed the department_sub_categories table.
     *
     * These sub-categories serve as the single source of truth
     * for organization categories. When creating an organization,
     * the category selection pulls from this table.
     */
    public function run(): void
    {
        $mapping = [
            'Education' => ['Primary', 'Secondary', 'Tertiary'],
            'Health' => ['Hospital', 'Health Centre', 'Clinic'],
            'Finance and Investment' => ['Sacco', 'Corporate'],
            'Mission and Outreach' => ['Church', 'Parish'],
            'HouseHold and Community' => ['Government', 'NGO'],
        ];

        foreach ($mapping as $departmentName => $subCategories) {
            $department = Department::where('name', $departmentName)->first();

            if (!$department) {
                $this->command->warn("Department '{$departmentName}' not found, skipping.");
                continue;
            }

            foreach ($subCategories as $subCategoryName) {
                DepartmentSubCategory::firstOrCreate(
                    [
                        'department_id' => $department->id,
                        'name' => $subCategoryName,
                    ],
                    [
                        'is_active' => true,
                    ]
                );

                $this->command->info("Created sub-category '{$subCategoryName}' under '{$departmentName}'");
            }
        }
    }
}
