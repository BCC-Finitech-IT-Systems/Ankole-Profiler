<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;

class PersonController extends Controller
{
    /**
     * Display the specified person.
     *
     * @param  int  $id
     * @return \Illuminate\View\View
     */
    public function show(Request $request, $id)
    {
        $person = Person::with(['phones', 'emailAddresses', 'affiliations.Organization'])->findOrFail($id);

        abort_unless($this->canViewPerson($request->user(), $person), 403);

        return view('livewire.person.show', compact('person'));
    }

    /**
     * A user may view a person if they are a Super Admin, the person is
     * their own record, or the person is affiliated with an organization
     * the user has access to.
     */
    private function canViewPerson($user, Person $person): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->hasRole('Super Admin')) {
            return true;
        }

        if ($person->user_id === $user->id || $user->person_id === $person->id) {
            return true;
        }

        return $person->affiliations
            ->filter(fn ($affiliation) => strcasecmp($affiliation->status, 'ACTIVE') === 0)
            ->pluck('organization_id')
            ->contains(fn ($orgId) => $user->canAccessOrganization($orgId));
    }
}
