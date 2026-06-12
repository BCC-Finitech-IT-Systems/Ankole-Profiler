<?php
namespace Tests\Feature;

use App\Livewire\Person\PersonSelfRegistrationComponent;
use App\Models\AllowedEmailDomain;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DebugRegTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug()
    {
        Notification::fake();
        Role::findOrCreate('Person', 'web');
        $diocese = Organization::factory()->create(['is_super' => false]);
        AllowedEmailDomain::create(['domain' => 'example.com', 'is_active' => true]);

        $c = Livewire::test(PersonSelfRegistrationComponent::class)
            ->set('form.given_name', 'Grace')
            ->set('form.family_name', 'Tumusiime')
            ->set('form.date_of_birth', '1995-03-22')
            ->set('form.gender', 'Female')
            ->set('form.phone', '+256782345678')
            ->set('form.email', 'grace@example.com')
            ->set('form.address', '45 Kamukuzi Hill')
            ->set('form.district', 'Mbarara')
            ->set('form.city', 'Mbarara')
            ->set('form.organization_id', $diocese->id)
            ->call('submit');

        fwrite(STDERR, "\nUSERS: " . User::count());
        fwrite(STDERR, "\nERRORS: " . json_encode($c->errors()->toArray()) . "\n");
        $this->assertTrue(true);
    }
}
