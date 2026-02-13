<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;

class SearchFilterService
{
    protected $model;
    protected $query;
    protected $searchable = [];
    protected $filters = [];

    /**
     * @param string|\Illuminate\Database\Eloquent\Model $modelClass
     * @param array $searchable (fields to search on)
     */
    public function __construct($modelClass, array $searchable = [])
    {
        $this->model = $modelClass;
        $this->query = $modelClass::query();
        $this->searchable = $searchable;
    }

    /**
     * Apply a search term to the query.
     *
     * @param string $term
     * @return $this
     */
    public function applySearch($term)
    {
        if ($term && count($this->searchable)) {
            $this->query->where(function($q) use ($term) {
                foreach ($this->searchable as $field) {
                    $q->orWhere($field, 'like', "%$term%");
                }
            });
        }
        return $this;
    }

    /**
     * Apply additional filters to the query.
     *
     * @param array $filters
     * @return $this
     */
    public function applyFilters(array $filters)
    {
        foreach ($filters as $field => $value) {
            if ($value === null || $value === '' || $value === 'all') continue;

            // Special handling for known fields used in PersonList
            switch ($field) {
                case 'organization_id':
                    $this->applyOrganizationFilter($value);
                    break;

                case 'status':
                    // affiliation status
                    $this->applyAffiliationStatusFilter($value);
                    break;

                case 'classification':
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            $this->query->whereJsonContains('classification', $v);
                        }
                    } else {
                        $this->query->whereJsonContains('classification', $value);
                    }
                    break;

                case 'age_range':
                    $this->applyAgeRangeFilter($value);
                    break;

                case 'date_range':
                    // expect ['start' => ..., 'end' => ...] -> map to created_at
                    if (is_array($value) && (isset($value['start']) || isset($value['end']))) {
                        $start = $value['start'] ?? null;
                        $end = $value['end'] ?? null;
                        if ($start && $end) {
                            $this->query->whereBetween('created_at', [$start, $end]);
                        } elseif ($start) {
                            $this->query->where('created_at', '>=', $start);
                        } elseif ($end) {
                            $this->query->where('created_at', '<=', $end);
                        }
                    }
                    break;

                default:
                    // Handle array values -> JSON contains
                    if (is_array($value)) {
                        foreach ($value as $v) {
                            $this->query->whereJsonContains($field, $v);
                        }
                        break;
                    }

                    // If field ends with _id but not person_id, attempt relation via affiliations fallback
                    if (str_ends_with($field, '_id') && $field !== 'person_id') {
                        // Common pattern: organization_id -> affiliations->organization_id
                        if ($field === 'organization_id') {
                            $this->applyOrganizationFilter($value);
                        } else {
                            $relation = str_replace('_id', '', $field);
                            // try whereHas relation, but guard against missing relation
                            try {
                                $this->query->whereHas($relation, function($q) use ($value) {
                                    $q->where('id', $value);
                                });
                            } catch (\BadMethodCallException $ex) {
                                // fallback to direct where
                                $this->query->where($field, $value);
                            }
                        }
                        break;
                    }

                    // default simple where
                    $this->query->where($field, $value);
                    break;
            }
        }

        return $this;
    }

    protected function applyOrganizationFilter($organizationId)
    {
        // Persons are affiliated via 'affiliations' relationship
        $this->query->whereHas('affiliations', function($q) use ($organizationId) {
            $q->where('organization_id', $organizationId)
              ->where('status', 'active');
        });
    }

    protected function applyAffiliationStatusFilter($status)
    {
        $this->query->whereHas('affiliations', function($q) use ($status) {
            $q->where('status', $status);
        });
    }

    protected function applyAgeRangeFilter($ageRange)
    {
        // Expect format 'min-max' in years
        if (!is_string($ageRange)) return;
        if (strpos($ageRange, '-') === false) return;

        [$minAge, $maxAge] = explode('-', $ageRange);
        $minAge = (int) trim($minAge);
        $maxAge = (int) trim($maxAge);
        if ($minAge <= 0 && $maxAge <= 0) return;

        $maxDate = now()->subYears($minAge)->format('Y-m-d');
        $minDate = now()->subYears($maxAge + 1)->format('Y-m-d');
        $this->query->whereBetween('date_of_birth', [$minDate, $maxDate]);
    }

    /**
     * Get the query builder instance.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Get paginated results.
     *
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage = 15)
    {
        return $this->query->paginate($perPage);
    }

    /**
     * Get all results.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function get()
    {
        return $this->query->get();
    }
}
