<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\PolicyVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

class PolicyPublicationFactory extends Factory
{
    public function definition(): array
    {
        $version = PolicyVersion::factory()->published()->create();

        return [
            'policy_version_id' => $version->id,
            'policy_id' => $version->policy_id,
            'organization_id' => Organization::factory()->institution(),
            'status' => 'sent',
            'due_date' => now()->addDays(30)->toDateString(),
            'sent_at' => now(),
        ];
    }
}
