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
                
                // Randomly assign a user for both reporter and assignee
                $assignedUserId = $hdDamanUsers[array_rand($hdDamanUsers)];

                FalloutReport::create([
                    'tipe_order_id' => $orderTypes[array_rand($orderTypes)],
                    'order_id' => 'ORD' . $date->format('Ymd') . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                    'nomer_layanan' => 'NL' . rand(100000000, 999999999),
                    'sn_ont' => 'SN' . rand(1000000000, 9999999999),
                    'datek_odp' => 'ODP-MLG-FA/' . chr(rand(65, 90)) . rand(1, 20),
                    'port_odp' => rand(1, 16),
                    'fallout_status_id' => $status_id,
                    'keterangan' => 'Keterangan contoh ' . $i,
                    'resolution_notes' => 'Catatan resolusi contoh ' . $i,
                    'reporter_user_id' => 1, // Super admin as reporter
                    'assigned_to_user_id' => $assignedUserId, // *** THIS IS THE FIX ***
                    'created_at' => $date->copy()->addHours(rand(8, 17))->addMinutes(rand(0, 59)),
                    // Simulate status updates happening later on the same day
                    'updated_at' => $date->copy()->addHours(rand(18, 22))->addMinutes(rand(0, 59)),
                ]);
            }
        }
    }
}