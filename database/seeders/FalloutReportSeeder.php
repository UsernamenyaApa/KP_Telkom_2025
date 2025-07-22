<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FalloutReport;
use App\Models\OrderType;
use App\Models\FalloutStatus;
use App\Models\User;
use Carbon\Carbon;
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
        
        // Get all users with the 'hd-daman' role
        $hdDamanUsers = User::role('hd-daman')->pluck('id')->toArray();

        // Exit if no HD Daman users are found to prevent errors
        if (empty($hdDamanUsers)) {
            $this->command->info('No users with role "hd-daman" found. Skipping FalloutReportSeeder.');
            return;
        }

        $startDate = Carbon::create(2025, 7, 1);
        $endDate = Carbon::create(2025, 7, 31);
        $totalReports = 700;
        $days = $startDate->diffInDays($endDate);
        $reportsPerDay = floor($totalReports / $days);

        for ($day = 0; $day <= $days; $day++) {
            $date = $startDate->copy()->addDays($day);
            for ($i = 0; $i < $reportsPerDay; $i++) {
                $status_id = $falloutStatuses[array_rand($falloutStatuses)];
                $assignedUserId = $hdDamanUsers[array_rand($hdDamanUsers)];
                $reporterUserId = $hdDamanUsers[array_rand($hdDamanUsers)];

                $createdAt = $date->copy()->addHours(rand(8, 17))->addMinutes(rand(0, 59));
                $updatedAt = $createdAt->copy()->addHours(rand(1, 5))->addMinutes(rand(0, 59));
                if ($updatedAt->greaterThan(Carbon::now())) {
                    $updatedAt = Carbon::now();
                }

                FalloutReport::create([
                    'tipe_order_id' => $orderTypes[array_rand($orderTypes)],
                    'order_id' => 'ORD' . $date->format('Ymd') . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                    'nomer_layanan' => 'NL' . rand(100000000, 999999999),
                    'sn_ont' => 'SN' . rand(1000000000, 9999999999),
                    'datek_odp' => 'ODP-MLG-FA/' . chr(rand(65, 90)) . rand(1, 20),
                    'port_odp' => rand(1, 16),
                    'fallout_status_id' => $status_id,
                    'keterangan' => 'Keterangan contoh ' . ($i + 1) . ' for ' . $date->format('Y-m-d'),
                    'resolution_notes' => 'Catatan resolusi contoh ' . ($i + 1) . ' for ' . $date->format('Y-m-d'),
                    'reporter_user_id' => $reporterUserId,
                    'assigned_to_user_id' => $assignedUserId,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'incident_ticket' => 'INC' . rand(100000, 999999),
                    'image' => 'fallout-images/example.jpg',
                ]);
            }
        }

        // Existing loop for generating 700 random data
        for ($i = 0; $i < 700; $i++) {
            $status_id = $falloutStatuses[array_rand($falloutStatuses)];

            $assignedUserId = $hdDamanUsers[array_rand($hdDamanUsers)];
            $reporterUserId = $hdDamanUsers[array_rand($hdDamanUsers)];

            // Generate random created_at within the last 3 months
            $createdAt = Carbon::now()->subDays(rand(0, 90))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            // Generate updated_at after created_at, up to now
            $updatedAt = $createdAt->copy()->addDays(rand(0, 5))->addHours(rand(0, 23))->addMinutes(rand(0, 59));
            if ($updatedAt->greaterThan(Carbon::now())) {
                $updatedAt = Carbon::now();
            }

            FalloutReport::create([
                'tipe_order_id' => $orderTypes[array_rand($orderTypes)],
                'order_id' => 'ORD' . Carbon::parse($createdAt)->format('Ymd') . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'nomer_layanan' => 'NL' . rand(100000000, 999999999),
                'sn_ont' => 'SN' . rand(1000000000, 9999999999),
                'datek_odp' => 'ODP-MLG-FA/' . chr(rand(65, 90)) . rand(1, 20),
                'port_odp' => rand(1, 16),
                'fallout_status_id' => $status_id,
                'keterangan' => 'Keterangan contoh ' . ($i + 1),
                'resolution_notes' => 'Catatan resolusi contoh ' . ($i + 1),
                'reporter_user_id' => $reporterUserId,
                'assigned_to_user_id' => $assignedUserId,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
                'incident_ticket' => 'INC' . rand(100000, 999999),
            ]);
        }
    }
}