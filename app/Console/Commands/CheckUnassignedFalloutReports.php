<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckUnassignedFalloutReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-unassigned-fallout-reports';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for unassigned fallout reports and sends notifications.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentHour = now()->hour;

        // Check if current time is within working hours (8 AM to 6 PM) and is a weekday
        if ($currentHour < 8 || $currentHour >= 24 || ! in_array(now()->dayOfWeekIso, [1, 2, 3, 4, 5])) {
            $this->info('Outside working hours (8 AM - 6 PM) or not a weekday. Skipping unassigned report check.');

            return;
        }

        $this->info('Checking for unassigned fallout reports...');

        $unassignedReports = \App\Models\FalloutReport::whereNull('assigned_to_user_id')
            ->where(function ($query) {
                $query->whereNull('notified_unassigned_at')
                    ->orWhere('notified_unassigned_at', '<=', now()->subMinutes(5));
            })
            ->where('created_at', '<=', now()->subMinutes(5))
            ->get();

        if ($unassignedReports->isEmpty()) {
            $this->info('No unassigned reports found.');

            return;
        }

        foreach ($unassignedReports as $report) {
            $message = "🔔 *Peringatan: Laporan Fallout Belum Diambil!* 🔔\n\n"
                       .'Tipe Order: '.($report->orderType ? $report->orderType->name : 'N/A')."\n"
                       .'OrderID: '.$report->order_id."\n"

                       .'SN ONT: '.$report->sn_ont."\n"
                       .'Datek ODP: '.$report->datek_odp."\n"
                       .'Port ODP: '.$report->port_odp."\n\n"
                       .'Dibuat pada: '.$report->created_at->format('d M Y H:i:s')."\n"
                       .'Mohon segera ditindaklanjuti.';

            $groupChat = \App\Models\TelegramGroup::first();
            if ($groupChat) {
                \App\Jobs\SendTelegramNotificationJob::dispatch($groupChat->chat_id, $message);
            }

            $report->notified_unassigned_at = now();
            $report->save();

            $this->info('Notified about unassigned report: '.$report->order_id);
        }

        $this->info('Finished checking unassigned fallout reports.');
    }
}
