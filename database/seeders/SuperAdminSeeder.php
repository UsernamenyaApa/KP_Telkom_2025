<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdminNiks = ['24870005', '12345678'];

        User::whereIn('nik', $superAdminNiks)->get()->each(function ($user) use ($role) {
            $user->assignRole($role);
        });
    }
}
