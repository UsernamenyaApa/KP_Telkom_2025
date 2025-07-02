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
        FalloutStatus::firstOrCreate(['id'=> 1, 'name' => 'Open']);
        FalloutStatus::firstOrCreate(['id'=> 2, 'name' => 'OnProgress']);
        FalloutStatus::firstOrCreate(['id'=> 3, 'name' => 'input ulang']);
        FalloutStatus::firstOrCreate(['id'=> 4, 'name' => 'eskalasi']);
        FalloutStatus::firstOrCreate(['id'=> 5, 'name' => 'PI']);
        FalloutStatus::firstOrCreate(['id'=> 6, 'name' => 'FA']);
    }
}