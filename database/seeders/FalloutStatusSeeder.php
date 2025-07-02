<?php

namespace Database\Seeders;

use App\Models\FalloutStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FalloutStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FalloutStatus::firstOrCreate(['name' => 'Open']);
        FalloutStatus::firstOrCreate(['name' => 'OnProgress']);
        FalloutStatus::firstOrCreate(['name' => 'input ulang']);
        FalloutStatus::firstOrCreate(['name' => 'eskalasi']);
        FalloutStatus::firstOrCreate(['name' => 'PI']);
        FalloutStatus::firstOrCreate(['name' => 'FA']);
    }
}