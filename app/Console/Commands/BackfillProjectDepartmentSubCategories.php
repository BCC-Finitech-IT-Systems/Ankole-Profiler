<?php

namespace App\Console\Commands;

use App\Models\DepartmentSubCategory;
use App\Models\Project;
use Illuminate\Console\Command;

class BackfillProjectDepartmentSubCategories extends Command
{
    protected $signature = 'projects:backfill-department-subcategories {--department= : Filter by department name (partial match)} {--force : Re-map even when department_sub_category_id is already set} {--dry-run : Preview changes without saving}';

    protected $description = 'Backfill project department sub-category links from project sub_category or organization category.';

    public function handle(): int
    {
        $departmentFilter = trim((string) $this->option('department'));
        $force = (bool) $this->option('force');
        $dryRun = (bool) $this->option('dry-run');

        $query = Project::query()
            ->with(['department.organization', 'departmentSubCategory']);

        if ($departmentFilter !== '') {
            $query->whereHas('department', function ($departmentQuery) use ($departmentFilter) {
                $departmentQuery->where('name', 'like', "%{$departmentFilter}%");
            });
        }

        if (!$force) {
            $query->whereNull('department_sub_category_id');
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            $this->info('No matching projects found for backfill.');
            return self::SUCCESS;
        }

        $processed = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($projects as $project) {
            $processed++;

            $department = $project->department;
            $organizationCategory = $department?->organization?->category;
            $projectSubCategory = is_string($project->sub_category) ? trim($project->sub_category) : '';

            $categoryName = $projectSubCategory !== '' ? $projectSubCategory : (is_string($organizationCategory) ? trim($organizationCategory) : '');

            if (!$department || $categoryName === '') {
                $skipped++;
                continue;
            }

            $subCategory = DepartmentSubCategory::query()->firstOrCreate(
                [
                    'department_id' => $department->id,
                    'name' => $categoryName,
                ],
                [
                    'is_active' => true,
                ]
            );

            $needsUpdate = (int) $project->department_sub_category_id !== (int) $subCategory->id
                || $project->sub_category !== $subCategory->name;

            if (!$needsUpdate) {
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                $project->update([
                    'department_sub_category_id' => $subCategory->id,
                    'sub_category' => $subCategory->name,
                ]);
            }

            $updated++;
        }

        $this->info("Processed: {$processed}");
        $this->info("Updated: {$updated}");
        $this->info("Skipped: {$skipped}");
        $this->info($dryRun ? 'Dry-run mode: no records were saved.' : 'Backfill complete.');

        return self::SUCCESS;
    }
}
