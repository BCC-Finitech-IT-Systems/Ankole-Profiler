<?php

namespace Database\Seeders;

use App\Models\AuditReport;
use App\Models\Assignment;
use App\Models\Department;
use App\Models\LandParcel;
use App\Models\Organization;
use App\Models\Person;
use App\Models\Policy;
use App\Models\PolicyVersion;
use App\Models\User;
use App\Models\Workplan;
use App\Models\WorkplanActivity;
use Illuminate\Database\Seeder;

/**
 * Sample records for the Education department in each module the demo
 * accounts open (policies, workplans, assignments, land, audit reports),
 * so their pages aren't empty during a demo.
 *
 * Idempotent: only creates what is missing and never updates existing
 * rows, since approved policies and workplans are locked against edits.
 * Every record is recognisably demo data ("DEMO-" codes, "(Demo)" titles).
 */
class DemoModuleDataSeeder extends Seeder
{
    public function run(): void
    {
        $diocese = Organization::where('is_super', true)->first();
        $education = Department::where('name', 'Education')->first();
        $hod = User::where('email', DemoAccountsSeeder::HEAD_OF_DEPARTMENT_EMAIL)->first();

        if (!$diocese || !$education || !$hod) {
            $this->command?->warn('DemoModuleDataSeeder skipped: run DemoAccountsSeeder first.');

            return;
        }

        $mary = $this->person('Mary', 'Kansiime');
        $john = $this->person('John', 'Byaruhanga');
        $isaac = $this->person('Isaac', 'Mwesigye');
        $owner = ['organization_id' => $diocese->id, 'department_id' => $education->id];
        $year = (int) now()->year;

        $this->policies($owner, $hod);
        $this->workplan($owner, $year, $hod, $mary, $john);
        $this->assignments($owner, $hod, $mary, $john, $isaac);
        $this->landParcels($owner, $hod, $john);
        $this->auditReports($owner, $hod, $mary);
    }

    private function person(string $given, string $family): ?Person
    {
        return Person::where('given_name', $given)->where('family_name', $family)->first();
    }

    private function policies(array $owner, User $hod): void
    {
        $policies = [
            ['DEMO-EDU-001', 'Child Protection and Safeguarding Policy (Demo)', 'Safeguarding', 'active', 'published',
                'How schools in the diocese prevent, report and respond to harm to learners.'],
            ['DEMO-EDU-002', 'School Fees and Bursary Policy (Demo)', 'Finance', 'active', 'published',
                'Fee structures, payment schedules and how bursaries are awarded.'],
            ['DEMO-EDU-003', 'Teacher Recruitment Guidelines (Demo)', 'Human Resource', 'draft', 'draft',
                'Draft guidelines for advertising, shortlisting and appointing teachers.'],
        ];

        foreach ($policies as [$code, $title, $category, $status, $versionStatus, $summary]) {
            $policy = Policy::firstOrCreate(
                ['organization_id' => $owner['organization_id'], 'reference_code' => $code],
                $owner + ['title' => $title, 'policy_category' => $category, 'status' => $status, 'created_by' => $hod->id]
            );

            if (!$policy->current_version_id) {
                $published = $versionStatus === 'published';
                $version = PolicyVersion::firstOrCreate(
                    ['policy_id' => $policy->id, 'version_number' => 1],
                    [
                        'version_label' => 'v1.0',
                        'summary' => $summary,
                        'status' => $versionStatus,
                        'effective_date' => $published ? now()->subMonths(6)->toDateString() : null,
                        'review_date' => $published ? now()->addMonths(18)->toDateString() : null,
                        'issuing_authority' => 'Diocesan Education Secretary',
                        'visibility' => 'diocese_wide',
                        'created_by' => $hod->id,
                        'approved_by' => $published ? $hod->id : null,
                        'published_by' => $published ? $hod->id : null,
                        'approved_at' => $published ? now()->subMonths(6) : null,
                        'published_at' => $published ? now()->subMonths(6) : null,
                    ]
                );
                $policy->forceFill(['current_version_id' => $version->id])->save();
            }
        }
    }

    private function workplan(array $owner, int $year, User $hod, ?Person $mary, ?Person $john): void
    {
        $workplan = Workplan::firstOrCreate(
            ['department_id' => $owner['department_id'], 'year' => $year, 'version_number' => 1],
            $owner + [
                'title' => "Education Department Workplan {$year} (Demo)",
                'status' => 'in_progress',
                'submitted_at' => now()->setDate($year, 1, 15),
                'submitted_by' => $hod->id,
                'approved_at' => now()->setDate($year, 1, 31),
                'approved_by' => $hod->id,
                'created_by' => $hod->id,
            ]
        );

        if ($workplan->activities()->exists()) {
            return;
        }

        $activities = [
            ['Improve learning outcomes', 'Run termly teacher training on the new lower secondary curriculum',
                'Teachers trained', '40', 'completed', 100, 'high', 12000000, $mary, [1, 3]],
            ['Improve learning outcomes', 'Supply science laboratory equipment to two secondary schools',
                'Schools equipped', '2', 'in_progress', 45, 'high', 35000000, $john, [2, 9]],
            ['Strengthen school governance', 'Train boards of governors on financial oversight',
                'Boards trained', '6', 'not_started', 0, 'medium', 8000000, $mary, [7, 11]],
            ['Child safeguarding', 'Roll out the child protection policy to all diocesan schools',
                'Schools briefed', '12', 'in_progress', 60, 'high', 5000000, $john, [3, 6]],
        ];

        foreach ($activities as [$objective, $activity, $indicator, $target, $status, $percent, $priority, $budget, $person, [$from, $to]]) {
            WorkplanActivity::create([
                'workplan_id' => $workplan->id,
                'strategic_objective' => $objective,
                'activity' => $activity,
                'performance_indicator' => $indicator,
                'target' => $target,
                'start_date' => now()->setDate($year, $from, 1)->toDateString(),
                'end_date' => now()->setDate($year, $to, 1)->endOfMonth()->toDateString(),
                'priority' => $priority,
                'budget_estimate' => $budget,
                'funding_source' => 'Diocese budget',
                'responsible_person_id' => $person?->id,
                'status' => $status,
                'percent_complete' => $percent,
                'created_by' => $hod->id,
            ]);
        }
    }

    private function assignments(array $owner, User $hod, ?Person $mary, ?Person $john, ?Person $isaac): void
    {
        $assignments = [
            ['Compile termly enrolment returns from all schools (Demo)', 'Reporting', 'high', 'in_progress', 40,
                -20, 10, $mary, 'Consolidated enrolment figures submitted to the Education Secretary.'],
            ['Inspect science laboratories at St James SS (Demo)', 'Inspection', 'medium', 'not_started', 0,
                5, 30, $john, 'Inspection report with an equipment gap list.'],
            ['Submit list of bursary beneficiaries (Demo)', 'Bursaries', 'urgent', 'completed', 100,
                -45, -15, $isaac, 'Approved list of bursary beneficiaries for the term.'],
            ['Follow up audit queries on school fees accounts (Demo)', 'Finance', 'high', 'blocked', 25,
                -30, -3, $mary, 'All audit queries answered with supporting documents.'],
        ];

        foreach ($assignments as [$title, $category, $priority, $status, $percent, $start, $due, $person, $expected]) {
            Assignment::firstOrCreate(
                ['organization_id' => $owner['organization_id'], 'title' => $title],
                $owner + [
                    'category' => $category,
                    'priority' => $priority,
                    'status' => $status,
                    'percent_complete' => $percent,
                    'start_date' => now()->addDays($start)->toDateString(),
                    'due_date' => now()->addDays($due)->toDateString(),
                    'expected_result' => $expected,
                    'responsible_person_id' => $person?->id,
                    'closed_at' => $status === 'completed' ? now()->subDays(16) : null,
                    'closed_by' => $status === 'completed' ? $hod->id : null,
                    'created_by' => $hod->id,
                ]
            );
        }
    }

    private function landParcels(array $owner, User $hod, ?Person $john): void
    {
        $parcels = [
            ['DEMO-LND-001', 'St James SS School Land (Demo)', 'Kakoba', 'surveyed', 12.5, 'Freehold', 'School', [
                'application_reference' => null,
                'next_action' => 'Prepare the title application with the surveyor\'s report.',
                'expected_completion_date' => now()->addMonths(4)->toDateString(),
            ]],
            ['DEMO-LND-002', 'Mbarara High School Playground (Demo)', 'Kamukuzi', 'title_issued', 4.2, 'Mailo', 'Playground', [
                'title_number' => 'MBR/1234',
                'title_volume_folio' => 'Vol 512 Folio 18',
                'title_issue_date' => now()->subYear()->toDateString(),
                'registered_proprietor' => 'The Registered Trustees of Ankole Diocese',
                'title_verification_status' => 'verified',
            ]],
            ['DEMO-LND-003', 'Teachers\' Housing Plot, Ruharo (Demo)', 'Ruharo', 'queries_raised', 1.8, 'Leasehold', 'Staff housing', [
                'application_reference' => 'MZO/APP/2025/0417',
                'land_office' => 'Mbarara Ministry Zonal Office',
                'submitted_at' => now()->subMonths(5)->toDateString(),
                'blockers' => 'Boundary query from a neighbouring owner; awaiting a joint site visit.',
                'lease_expiry_date' => now()->addMonths(8)->toDateString(),
            ]],
        ];

        foreach ($parcels as [$reference, $name, $location, $stage, $acreage, $tenure, $use, $extra]) {
            LandParcel::firstOrCreate(
                ['organization_id' => $owner['organization_id'], 'reference_number' => $reference],
                $owner + $extra + [
                    'property_name' => $name,
                    'location' => $location,
                    'district' => 'Mbarara',
                    'acreage' => $acreage,
                    'tenure_type' => $tenure,
                    'current_use' => $use,
                    'stage' => $stage,
                    'responsible_person_id' => $john?->id,
                    'created_by' => $hod->id,
                ]
            );
        }
    }

    private function auditReports(array $owner, User $hod, ?Person $mary): void
    {
        $reports = [
            ['Internal audit of St James SS finances (Demo)', 'St James SS', 'internal', 'Diocesan Internal Audit',
                'issued', 'Satisfactory', -4, 'Fee collections reconcile; two procurement files lacked quotations.'],
            ['External audit of the Education Department (Demo)', null, 'external', 'Mbarara Audit Partners',
                'under_review', 'Needs improvement', -2, 'Bursary disbursement records are incomplete for one term.'],
        ];

        foreach ($reports as [$title, $institution, $type, $issuer, $status, $rating, $months, $summary]) {
            AuditReport::firstOrCreate(
                ['organization_id' => $owner['organization_id'], 'title' => $title],
                $owner + [
                    'audited_institution_name' => $institution,
                    'audit_type' => $type,
                    'period_start' => now()->subYear()->startOfYear()->toDateString(),
                    'period_end' => now()->subYear()->endOfYear()->toDateString(),
                    'issuing_body' => $issuer,
                    'issue_date' => now()->addMonths($months)->toDateString(),
                    'status' => $status,
                    'overall_rating' => $rating,
                    'summary' => $summary,
                    'responsible_follow_up_owner_id' => $mary?->id,
                    'created_by' => $hod->id,
                ]
            );
        }
    }
}
