<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['nik' => '12345678'], // Dummy NIK for Test User
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        $this->call([
            OrderTypeSeeder::class,
            FalloutStatusSeeder::class,
            HdDamanRoleSeeder::class,
            HdDamanUserSeeder::class,
            SuperAdminSeeder::class,
            FalloutReportSeeder::class,
            PelurusanReportSeeder::class,
        ]);
    }
}