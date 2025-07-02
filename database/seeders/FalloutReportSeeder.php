<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FalloutReport;
use App\Models\OrderType;
use App\Models\FalloutStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FalloutReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orderTypes = OrderType::pluck('id')->toArray();
        $falloutStatuses = FalloutStatus::pluck('id')->toArray();

        $dates = [
            Carbon::create(2025, 7, 1),
            Carbon::create(2025, 6, 30),
        ];

        Log::info('FalloutReportSeeder: Columns in fallout_reports table:', Schema::getColumnListing('fallout_reports'));

        foreach ($dates as $date) {
            for ($i = 0; $i < 10; $i++) {
                $id_harian = FalloutReport::whereDate('created_at', $date)->count() + 1;
                $fallout_code = 'FA' . $date->format('Ymd') . str_pad($id_harian, 2, '0', STR_PAD_LEFT);
                $status_id = $falloutStatuses[array_rand($falloutStatuses)];

                $dataToInsert = [
                    'id_harian' => $id_harian,
                    'fallout_code' => $fallout_code,
                    'tipe_order_id' => $orderTypes[array_rand($orderTypes)],
                    'order_id' => 'ORD' . $date->format('Ymd') . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'reporter_user_id' => 1, // Assuming user with ID 1 exists
                    'nomer_layanan' => 'NL' . rand(100000000, 999999999),
                    'sn_ont' => 'SN' . rand(1000000000, 9999999999),
                    'datek_odp' => 'ODP-MLG-FA/' . chr(rand(65, 90)) . rand(1, 20),
                    'port_odp' => rand(1, 16),
                    'fallout_status_id' => $status_id,
                    'keterangan' => 'Keterangan ' . $i,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];

                Log::info('FalloutReportSeeder: Data to insert:', $dataToInsert);

                FalloutReport::create($dataToInsert);
            }
        }
    }
}
