<?php

namespace Database\Seeders;

use App\Models\AllowedEmailDomain;
use App\Models\Organization;
use Illuminate\Database\Seeder;

class AllowedEmailDomainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organization = Organization::where('is_super', true)->first();

        $domains = [
            'bcc.co.ug',
            'gmail.com',
        ];

        foreach ($domains as $domain) {
            AllowedEmailDomain::firstOrCreate(
                ['domain' => $domain],
                [
                    'organization_id' => $organization?->id,
                    'is_active' => true,
                ]
            );
        }
    }
}
