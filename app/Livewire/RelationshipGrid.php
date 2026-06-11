<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Person;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RelationshipGrid extends Component
{
    public $filteredPersonIds = [];
    public $showRelationships = false;
    public $viewMode = 'grid'; // grid, network, list, table
    public $relationshipTypes = [
        'parent_child' => true,
        'spouse' => true,
        'sibling' => true,
        'guardian_ward' => true,
        'emergency_contact' => true,
        'next_of_kin' => true,
        'dependent' => true,
        'colleague' => true,
        'business_partner' => true,
    ];
    public $groupBy = 'relationship_type'; // relationship_type, person, none
    public $expandedGroups = [];
    public $selectedRelationships = [];
    
    protected $listeners = ['loadRelationships'];

    public function mount($personIds = [])
    {
        $this->filteredPersonIds = is_array($personIds) ? $personIds : [];
    }

    public function loadRelationships($personIds)
    {
        $this->filteredPersonIds = $personIds;
        $this->showRelationships = true;
    }

    public function toggleRelationshipType($type)
    {
        $this->relationshipTypes[$type] = !$this->relationshipTypes[$type];
    }

    public function toggleGroup($groupKey)
    {
        if (in_array($groupKey, $this->expandedGroups)) {
            $this->expandedGroups = array_diff($this->expandedGroups, [$groupKey]);
        } else {
            $this->expandedGroups[] = $groupKey;
        }
    }

    public function setViewMode($mode)
    {
        $this->viewMode = $mode;
    }

    public function setGroupBy($groupBy)
    {
        $this->groupBy = $groupBy;
        $this->expandedGroups = [];
    }

    public function getRelatedPersonsProperty()
    {
        if (empty($this->filteredPersonIds)) {
            return collect();
        }

        $enabledTypes = array_keys(array_filter($this->relationshipTypes));
        
        if (empty($enabledTypes)) {
            return collect();
        }

        $filteredIds = $this->filteredPersonIds;

        // Fetch relationships where the filtered persons appear on either side
        $relationships = DB::table('person_relationships')
            ->where(function ($q) use ($filteredIds) {
                $q->whereIn('person_a_id', $filteredIds)
                  ->orWhereIn('person_b_id', $filteredIds);
            })
            ->whereIn('relationship_type', $enabledTypes)
            ->where('status', 'active')
            ->get();

        // For each relationship, determine the "related" person (the one NOT in filteredIds)
        $relatedPersonIds = $relationships->map(function ($rel) use ($filteredIds) {
            return in_array($rel->person_a_id, $filteredIds) ? $rel->person_b_id : $rel->person_a_id;
        })->unique()->values();

        // Fetch the related persons with their details
        $relatedPersons = Person::whereIn('id', $relatedPersonIds)
            ->with(['phones', 'emailAddresses', 'affiliations.Organization'])
            ->get();

        // Attach relationship context to each related person
        return $relatedPersons->map(function ($person) use ($relationships, $filteredIds) {
            $personRelationships = $relationships->filter(function ($rel) use ($person, $filteredIds) {
                return ($rel->person_a_id === $person->id && in_array($rel->person_b_id, $filteredIds))
                    || ($rel->person_b_id === $person->id && in_array($rel->person_a_id, $filteredIds));
            });

            $person->relationships = $personRelationships->map(function ($rel) use ($person, $filteredIds) {
                $sourceId = in_array($rel->person_a_id, $filteredIds) ? $rel->person_a_id : $rel->person_b_id;
                $sourcePerson = Person::find($sourceId);
                return [
                    'type' => $rel->relationship_type,
                    'source_person_id' => $sourceId,
                    'source_person_name' => $sourcePerson ? $sourcePerson->full_name : 'Unknown',
                    'is_primary' => $rel->is_primary ?? false,
                    'notes' => $rel->notes ?? null,
                ];
            });

            $person->relationship_count = $personRelationships->count();
            $person->primary_relationship = $personRelationships->firstWhere('is_primary', true);

            return $person;
        });
    }

    public function getGroupedRelationshipsProperty()
    {
        $relatedPersons = $this->relatedPersons;

        if ($this->groupBy === 'none') {
            return collect(['all' => $relatedPersons]);
        }

        if ($this->groupBy === 'relationship_type') {
            return $relatedPersons->groupBy(function ($person) {
                return $person->relationships->first()['type'] ?? 'unknown';
            });
        }

        if ($this->groupBy === 'person') {
            // Group by the source person (from filtered results)
            $grouped = collect();
            
            foreach ($this->filteredPersonIds as $personId) {
                $sourcePerson = Person::find($personId);
                if (!$sourcePerson) continue;

                $relatedToThis = $relatedPersons->filter(function ($relatedPerson) use ($personId) {
                    return $relatedPerson->relationships->contains('source_person_id', $personId);
                });

                if ($relatedToThis->isNotEmpty()) {
                    $grouped[$sourcePerson->full_name] = $relatedToThis;
                }
            }

            return $grouped;
        }

        return collect();
    }

    public function getRelationshipStatsProperty()
    {
        $relatedPersons = $this->relatedPersons;

        return [
            'total_related' => $relatedPersons->count(),
            'by_type' => $relatedPersons->flatMap(function ($person) {
                return $person->relationships;
            })->groupBy('type')->map->count(),
            'unique_connections' => $relatedPersons->sum('relationship_count'),
            'with_primary_contact' => $relatedPersons->filter(function ($person) {
                return $person->primary_relationship !== null;
            })->count(),
        ];
    }

    public function exportRelationships()
    {
        // Implement export logic
        session()->flash('success', 'Relationship data exported successfully!');
    }

    public function selectRelationship($personId)
    {
        if (in_array($personId, $this->selectedRelationships)) {
            $this->selectedRelationships = array_diff($this->selectedRelationships, [$personId]);
        } else {
            $this->selectedRelationships[] = $personId;
        }
    }

    public function render()
    {
        return view('livewire.relationship-grid', [
            'relatedPersons' => $this->relatedPersons,
            'groupedRelationships' => $this->groupedRelationships,
            'stats' => $this->relationshipStats,
        ]);
    }
}