<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin user with basic fields only
        User::updateOrCreate(
            ['email' => 'admin@theainet.net'],
            [
                'name' => 'Super Admin',
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'admin@theainet.net',
                'password' => Hash::make('Theline@12321'),
                'mobile' => '+1234567890',
                'gender' => 'Male',
                'role_id' => 1,
                'membership_type' => 'Individual',
                'membership_plan' => 'Premium',
                'title' => 'Dr.',
                'state' => 'Maharashtra',
                'district' => 'Mumbai',
                'address' => 'Admin Office, Mumbai',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
