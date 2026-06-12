<?php

namespace App\Imports;

use App\Models\Person;
use App\Models\PersonAffiliation;
use App\Models\Phone;
use App\Models\EmailAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PersonStandardImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, SkipsEmptyRows
{
    use Importable, SkipsFailures;

    public function rules(): array
    {
        return [
            'given_name' => ['required', 'string', 'max:255'],
            'family_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'date_of_birth' => ['nullable', 'date'],
        ];
    }

    public function model(array $row)
    {
        $organization = user_current_organization();

        return DB::transaction(function () use ($row, $organization) {
            $user = User::create([
                'name' => trim(($row['given_name'] ?? '') . ' ' . ($row['family_name'] ?? '')),
                'email' => $row['email'],
                'password' => Hash::make(Str::random(10)),
            ]);
            $user->assignRole('Person');

            $person = Person::create([
                'person_id' => \App\Helpers\IdGenerator::generatePersonId(),
                'global_identifier' => \App\Helpers\IdGenerator::generateGlobalIdentifier(),
                'user_id' => $user->id,
                'given_name' => $row['given_name'] ?? '',
                'middle_name' => $row['middle_name'] ?? '',
                'family_name' => $row['family_name'] ?? '',
                'date_of_birth' => $row['date_of_birth'] ?? null,
                'gender' => $row['gender'] ?? null,
                'address' => $row['address'] ?? '',
                'country' => $row['country'] ?? '',
                'city' => $row['city'] ?? '',
                'district' => $row['district'] ?? '',
            ]);

            if ($organization) {
                PersonAffiliation::create([
                    'person_id'       => $person->id,
                    'organization_id' => $organization->id,
                    'role_type'       => 'MEMBER',
                    'start_date'      => now()->format('Y-m-d'),
                    'status'          => 'active',
                    'created_by'      => auth()->id(),
                ]);
            }

            if (!empty($row['phone_number'])) {
                Phone::create([
                    'person_id' => $person->id,
                    'number' => $row['phone_number'],
                ]);
            }

            if (!empty($row['email'])) {
                EmailAddress::create([
                    'person_id' => $person->id,
                    'email' => $row['email'],
                ]);
            }

            return $person;
        });
    }
}
