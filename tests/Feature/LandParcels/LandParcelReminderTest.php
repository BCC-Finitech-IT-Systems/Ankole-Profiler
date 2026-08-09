<?php

namespace Tests\Feature\LandParcels;

use App\Models\LandParcel;
use App\Models\Organization;
use App\Models\Person;
use App\Models\User;
use App\Notifications\LandParcelDeadlineReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LandParcelReminderTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->organization = Organization::factory()->create();
    }

    private function parcelWithResponsibleUser(array $attributes): LandParcel
    {
        $user = User::factory()->create();
        $person = Person::factory()->create(['user_id' => $user->id]);

        return LandParcel::factory()->create(array_merge([
            'organization_id' => $this->organization->id,
            'responsible_person_id' => $person->id,
        ], $attributes));
    }

    public function test_stalled_application_receives_a_reminder()
    {
        Notification::fake();

        $parcel = $this->parcelWithResponsibleUser([
            'stage' => 'submitted', 'expected_completion_date' => now()->subDays(3),
        ]);

        $this->artisan('land:check-deadlines');

        Notification::assertSentTo($parcel->responsiblePerson->user, LandParcelDeadlineReminder::class);
        $this->assertNotNull($parcel->fresh()->last_reminder_sent_at);
    }

    public function test_reminder_is_not_resent_the_same_day()
    {
        Notification::fake();

        $parcel = $this->parcelWithResponsibleUser([
            'stage' => 'submitted', 'expected_completion_date' => now()->subDays(3),
        ]);

        $this->artisan('land:check-deadlines');
        Notification::assertSentToTimes($parcel->responsiblePerson->user, LandParcelDeadlineReminder::class, 1);

        Notification::fake();
        $this->artisan('land:check-deadlines');
        Notification::assertNothingSent();
    }

    public function test_title_issued_and_closed_parcels_are_skipped()
    {
        Notification::fake();

        $this->parcelWithResponsibleUser(['stage' => 'title_issued', 'expected_completion_date' => now()->subDays(3)]);
        $this->parcelWithResponsibleUser(['stage' => 'closed', 'expected_completion_date' => now()->subDays(3)]);

        $this->artisan('land:check-deadlines');

        Notification::assertNothingSent();
    }

    public function test_expiring_lease_receives_a_reminder()
    {
        Notification::fake();

        $parcel = $this->parcelWithResponsibleUser([
            'stage' => 'title_issued', 'lease_expiry_date' => now()->addDays(30),
        ]);

        $this->artisan('land:check-deadlines');

        Notification::assertSentTo($parcel->responsiblePerson->user, LandParcelDeadlineReminder::class);
    }

    public function test_stalled_query_receives_a_reminder_without_duplicate_recipients()
    {
        Notification::fake();

        $parcel = $this->parcelWithResponsibleUser(['stage' => 'queries_raised']);

        $this->artisan('land:check-deadlines');

        Notification::assertSentToTimes($parcel->responsiblePerson->user, LandParcelDeadlineReminder::class, 1);
    }
}
