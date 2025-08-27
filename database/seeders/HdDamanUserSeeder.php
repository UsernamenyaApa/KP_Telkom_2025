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
            ['nik' => '24000030', 'name' => 'FEBRINA', 'telegram_user_id' => '603027826', 'telegram_username' => 'febrina2402'],
            ['nik' => '24010032', 'name' => 'JULIYANI SANTIKA', 'telegram_user_id' => '806089902', 'telegram_username' => 'Tika23'],
            ['nik' => '24870005', 'name' => 'IMAM GOZALI ASAAT', 'telegram_user_id' => '112882812', 'telegram_username' => 'gozaliasaat'],
            ['nik' => '24880003', 'name' => 'KEMAL BAZIAD', 'telegram_user_id' => '1229593449', 'telegram_username' => 'baziad_24'],
            ['nik' => '24900024', 'name' => 'HADI WISNU FEBRIANA', 'telegram_user_id' => '1366716110', 'telegram_username' => 'ogut_ea'],
            ['nik' => '24970071', 'name' => 'PEKIK YUGO KINASIH', 'telegram_user_id' => '811285367', 'telegram_username' => 'Siyugo'],
            ['nik' => '24980057', 'name' => 'RAMADHANTY AVESYA IMAN', 'telegram_user_id' => '815787501', 'telegram_username' => 'dhantyavesya'],
        ];

        foreach ($users as $userData) {
            $password = $userData['nik'] === '24870005' ? Hash::make('password') : Hash::make($userData['nik']);

            $user = User::updateOrCreate(
                ['nik' => $userData['nik']],
                [
                    'name' => $userData['name'],
                    'password' => $password, // Set conditional password
                    'telegram_user_id' => $userData['telegram_user_id'] ?? null,
                    'telegram_username' => $userData['telegram_username'] ?? null,
                ]
            );

            $user->assignRole($hdDamanRole);
        }
    }
}
