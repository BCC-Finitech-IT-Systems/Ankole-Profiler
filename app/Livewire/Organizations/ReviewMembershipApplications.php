<?php

namespace App\Livewire\Organizations;

use App\Models\PersonAffiliation;
use App\Notifications\MembershipApplicationDecided;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ReviewMembershipApplications extends Component
{
    use WithPagination;

    public function approve(int $affiliationId)
    {
        $affiliation = PersonAffiliation::where('status', 'pending')->findOrFail($affiliationId);
        Gate::authorize('approveMembership', $affiliation);

        DB::transaction(function () use ($affiliation) {
            $affiliation->update([
                'status' => 'active',
                'start_date' => now(),
                'updated_by' => Auth::id(),
            ]);

            // Member capabilities are only granted at approval time.
            $affiliation->person?->user?->assignRole('Person');
        });

        $affiliation->person?->user?->notify(new MembershipApplicationDecided($affiliation->fresh()));

        session()->flash('success', 'Membership application approved.');
    }

    public function reject(int $affiliationId)
    {
        $affiliation = PersonAffiliation::where('status', 'pending')->findOrFail($affiliationId);
        Gate::authorize('rejectMembership', $affiliation);

        $affiliation->update([
            'status' => 'rejected',
            'updated_by' => Auth::id(),
        ]);

        $affiliation->person?->user?->notify(new MembershipApplicationDecided($affiliation->fresh()));

        session()->flash('success', 'Membership application rejected.');
    }

    public function render()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $applications = PersonAffiliation::with(['person', 'Organization'])
            ->where('status', 'pending')
            ->when(
                !$user->hasRole('Super Admin'),
                fn ($q) => $q->whereIn('organization_id', $user->managedOrganizationIds())
            )
            ->latest()
            ->paginate(20);

        return view('livewire.organizations.review-membership-applications', [
            'applications' => $applications,
        ]);
    }
}
