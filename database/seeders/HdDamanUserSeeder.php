<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class HdDamanUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hdDamanRole = Role::where('name', 'hd-daman')->first();

        if ($hdDamanRole) {
            for ($i = 1; $i <= 8; $i++) {
                $userName = 'HD Daman User ' . $i;
                $userEmail = 'hd-daman-user-' . $i . '@example.com';

                $user = User::firstOrCreate(
                    ['email' => $userEmail],
                    [
                        'name' => $userName,
                        'password' => Hash::make('password'), // Default password for HD Daman users
                        'email_verified_at' => now(),
                    ]
                );

                $user->assignRole($hdDamanRole);
            }
        }
    }
}
