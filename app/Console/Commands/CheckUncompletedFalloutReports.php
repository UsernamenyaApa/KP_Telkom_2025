<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckUncompletedFalloutReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-uncompleted-fallout-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for uncompleted fallout reports and sends notifications.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentHour = now()->hour;

        // Check if current time is within working hours (8 AM to 6 PM) and is a weekday
        if ($currentHour < 8 || $currentHour >= 24 || ! in_array(now()->dayOfWeekIso, [1, 2, 3, 4, 5])) {
            $this->info('Outside working hours (8 AM - 6 PM) or not a weekday. Skipping uncompleted report check.');

            return;
        }

        $this->info('Checking for uncompleted fallout reports...');

        $uncompletedReports = \App\Models\FalloutReport::whereNotNull('assigned_to_user_id')
            ->whereNull('completed_at')
            ->where(function ($query) {
                $query->whereNull('notified_uncompleted_at')
                    ->orWhere('notified_uncompleted_at', '<=', now()->subMinutes(5));
            })
            ->where('assigned_at', '<=', now()->subMinutes(5))
            ->get();

        if ($uncompletedReports->isEmpty()) {
            $this->info('No uncompleted reports found.');

            return;
        }

        foreach ($uncompletedReports as $report) {
            $message = "🔔 *Peringatan: Laporan Fallout Belum Selesai!* 🔔\n\n"
                       .'Tipe Order: '.($report->orderType ? $report->orderType->name : 'N/A')."\n"
                       .'OrderID: '.$report->order_id."\n"

                       .'SN ONT: '.$report->sn_ont."\n"
                       .'Datek ODP: '.$report->datek_odp."\n"
                       .'Port ODP: '.$report->port_odp."\n\n"
                       .'Diambil oleh: @'.($report->assignedToUser ? $report->assignedToUser->telegram_username : 'N/A')."\n"
                       .'Diambil pada: '.$report->assigned_at->format('d M Y H:i:s')."\n".'Mohon segera diselesaikan.';

            $groupChat = \App\Models\TelegramGroup::first();
            if ($groupChat) {
                \App\Jobs\SendTelegramNotificationJob::dispatch($groupChat->chat_id, $message);
            }

            $report->notified_uncompleted_at = now();
            $report->save();

            $this->info('Notified about uncompleted report: '.$report->order_id);
        }

        $this->info('Finished checking uncompleted fallout reports.');
    }
}
