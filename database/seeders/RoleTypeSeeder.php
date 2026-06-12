<?php

namespace Database\Seeders;

use App\Models\RoleType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $roleTypes = [
            [
                'code' => 'STAFF',
                'name' => 'Staff',
                'description' => 'Employees, teachers, nurses, doctors, and other staff members',
                'active' => true,
            ],
            [
                'code' => 'STUDENT',
                'name' => 'Student',
                'description' => 'Enrolled students, learners, and trainees',
                'active' => true,
            ],
            [
                'code' => 'PATIENT',
                'name' => 'Patient',
                'description' => 'Medical patients and healthcare recipients',
                'active' => true,
            ],
            [
                'code' => 'MEMBER',
                'name' => 'Member',
                'description' => 'SACCO members, club members, and organization members',
                'active' => true,
            ],
            [
                'code' => 'PARISHIONER',
                'name' => 'Parishioner',
                'description' => 'Church members, parish members, and congregation',
                'active' => true,
            ],
            [
                'code' => 'CUSTOMER',
                'name' => 'Customer',
                'description' => 'Clients, buyers, and service recipients',
                'active' => true,
            ],
            [
                'code' => 'VENDOR',
                'name' => 'Vendor',
                'description' => 'Suppliers, contractors, and service providers',
                'active' => true,
            ],
            [
                'code' => 'VOLUNTEER',
                'name' => 'Volunteer',
                'description' => 'Unpaid volunteers and community helpers',
                'active' => true,
            ],
            [
                'code' => 'GUARDIAN',
                'name' => 'Guardian',
                'description' => 'Parents, guardians, and next of kin',
                'active' => true,
            ],
            [
                'code' => 'BOARD_MEMBER',
                'name' => 'Board Member',
                'description' => 'Board of directors and governing body members',
                'active' => true,
            ],
            [
                'code' => 'CONSULTANT',
                'name' => 'Consultant',
                'description' => 'External consultants and advisors',
                'active' => true,
            ],
            [
                'code' => 'ALUMNI',
                'name' => 'Alumni',
                'description' => 'Former students and graduates',
                'active' => true,
            ],
        ];

        foreach ($roleTypes as $roleType) {
            RoleType::updateOrCreate(
                ['code' => $roleType['code']],
                $roleType
            );
        }

        $this->command->info('✅ Role types seeded successfully!');
    }

}
