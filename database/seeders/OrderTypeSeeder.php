<?php

namespace Database\Seeders;

use App\Models\OrderType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OrderTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orderTypes = [
            ['id' => 1, 'name' => 'AO'],
            ['id' => 2, 'name' => 'MO'],
            ['id' => 3, 'name' => 'SO'],
            ['id' => 4, 'name' => 'DO'],
            ['id' => 5, 'name' => 'RO'],
            ['id' => 6, 'name' => 'PDA'],
            ['id' => 7, 'name' => 'MIGRASI'],
        ];

        foreach ($orderTypes as $type) {
            OrderType::updateOrCreate(['id' => $type['id']], ['name' => $type['name']]);
        }
    }
}