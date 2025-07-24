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
        $hdDamanRole = Role::firstOrCreate(['name' => 'hd-daman']);

        $users = [
            ['nik' => '24000030', 'name' => 'FEBRINA'],
            ['nik' => '24010032', 'name' => 'JULIYANI SANTIKA'],
            ['nik' => '24870005', 'name' => 'IMAM GOZALI ASAAT'],
            ['nik' => '24880003', 'name' => 'KEMAL BAZIAD'],
            ['nik' => '24900024', 'name' => 'HADI WISNU FEBRIANA'],
            ['nik' => '24970071', 'name' => 'PEKIK YUGO KINASIH'],
            ['nik' => '24980057', 'name' => 'RAMADHANTY AVESYA IMAN'],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['nik' => $userData['nik']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'), // Default password
                ]
            );

            $user->assignRole($hdDamanRole);
        }
    }
}
