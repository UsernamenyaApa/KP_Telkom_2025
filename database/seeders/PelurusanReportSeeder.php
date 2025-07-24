<?php

namespace Database\Seeders;

use App\Models\FalloutStatus;
use App\Models\OrderType;
use App\Models\PelurusanReport;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PelurusanReportSeeder extends Seeder
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
            $this->command->info('No users with role "hd-daman" found. Skipping PelurusanReportSeeder.');

            return;
        }

        // Truncate table before seeding to avoid duplicate data on re-run
        // Schema::disableForeignKeyConstraints();
        // PelurusanReport::truncate();
        // Schema::enableForeignKeyConstraints();

        // Generate data for today and yesterday
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        $datesToSeed = [
            $today,
            $yesterday,
        ];

        foreach ($datesToSeed as $date) {
            for ($j = 0; $j < 100; $j++) { // 100 records for each specific day
                $status_id = $falloutStatuses[array_rand($falloutStatuses)];
                $assignedUserId = $hdDamanUsers[array_rand($hdDamanUsers)];
                $reporterUserId = $hdDamanUsers[array_rand($hdDamanUsers)];

                $createdAt = $date->copy()->addHours(rand(8, 17))->addMinutes(rand(0, 59));
                $updatedAt = $createdAt->copy()->addHours(rand(1, 5))->addMinutes(rand(0, 59));
                if ($updatedAt->greaterThan(Carbon::now())) {
                    $updatedAt = Carbon::now();
                }

                PelurusanReport::create([
                    'tipe_order_id' => $orderTypes[array_rand($orderTypes)],
                    'order_id' => 'ORDP'.$date->format('Ymd').str_pad($j + 1, 4, '0', STR_PAD_LEFT),
                    'nomer_layanan' => 'NLP'.rand(100000000, 999999999),
                    'sn_ont' => 'SNP'.rand(1000000000, 9999999999),
                    'datek_odp' => 'ODP-PLR-FA/'.chr(rand(65, 90)).rand(1, 20),
                    'port_odp' => rand(1, 16),
                    'fallout_status_id' => $status_id,
                    'keterangan' => 'Keterangan pelurusan contoh '.($j + 1).' for '.$date->format('Y-m-d'),
                    'resolution_notes' => 'Catatan resolusi pelurusan contoh '.($j + 1).' for '.$date->format('Y-m-d'),
                    'reporter_user_id' => $reporterUserId,
                    'assigned_to_user_id' => $assignedUserId,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                    'pelurusan_code' => 'PLR'.$date->format('Ymd').str_pad($j + 1, 4, '0', STR_PAD_LEFT),
                    'image' => 'pelurusan-images/example.jpg',
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

            PelurusanReport::create([
                'tipe_order_id' => $orderTypes[array_rand($orderTypes)],
                'order_id' => 'ORDP'.Carbon::parse($createdAt)->format('Ymd').str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'nomer_layanan' => 'NLP'.rand(100000000, 999999999),
                'sn_ont' => 'SNP'.rand(1000000000, 9999999999),
                'datek_odp' => 'ODP-PLR-FA/'.chr(rand(65, 90)).rand(1, 20),
                'port_odp' => rand(1, 16),
                'fallout_status_id' => $status_id,
                'keterangan' => 'Keterangan pelurusan contoh '.($i + 1),
                'resolution_notes' => 'Catatan resolusi pelurusan contoh '.($i + 1),
                'reporter_user_id' => $reporterUserId,
                'assigned_to_user_id' => $assignedUserId,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
                'pelurusan_code' => 'PLR'.Carbon::parse($createdAt)->format('Ymd').str_pad($i + 101, 4, '0', STR_PAD_LEFT),
                'image' => 'pelurusan-images/example.jpg',
            ]);
        }
    }
}
