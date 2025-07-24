<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // --- JADWAL UNTUK PRODUKSI NANTI ---
        // Aktifkan ini setelah testing selesai.
        $schedule->command(\App\Console\Commands\CheckUnassignedFalloutReports::class)
            ->hourly() // Dijalankan setiap jam
            ->weekdays()
            ->between('8:00', '18:00') // Jam kerja normal
            ->timezone('Asia/Jakarta');

        $schedule->command(\App\Console\Commands\CheckUncompletedFalloutReports::class)
            ->hourly() // Dijalankan setiap jam
            ->weekdays()
            ->between('8:00', '18:00') // Jam kerja normal
            ->timezone('Asia/Jakarta');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
