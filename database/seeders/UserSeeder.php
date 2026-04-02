<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the Super Admin role exists
        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

        // Create or update the Super Admin user
        $user = User::updateOrCreate(
            ['email' => 'ategeka.lordrick@bcc.co.ug'], // unique constraint
            [
                'name' => 'Lordrick Ategeka -BCC -ADMIN',
                'email_verified_at' => now(),
                'password' => Hash::make('qwertyui'), // use a strong password in production
            ]
        );

        // Assign the role
        $user->assignRole($superAdminRole);

        // Optional: Mark the user as super_admin = 1 (if you have this column)
        if (Schema::hasColumn('users', 'super_admin')) {
            $user->update(['super_admin' => 1]);
        }

    }
}
