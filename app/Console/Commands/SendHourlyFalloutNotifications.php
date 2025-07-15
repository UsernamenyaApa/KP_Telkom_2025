<?php

namespace App\Console\Commands;

use App\Models\FalloutReport;
use App\Jobs\SendTelegramNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendHourlyFalloutNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fallout:notify-hourly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send hourly notifications for unassigned or uncompleted fallout reports.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Sending hourly fallout notifications...');
        Log::info('Hourly fallout notification process started.');

        $unassignedReports = FalloutReport::where('fallout_status_id', 1) // Assuming 1 is 'Open'
            ->whereNull('assigned_to_user_id')
            ->get();

        $inProgressReports = FalloutReport::where('fallout_status_id', 2) // Assuming 2 is 'In Progress'
            ->get();

        if ($unassignedReports->isEmpty() && $inProgressReports->isEmpty()) {
            $this->info('No reports to notify.');
            Log::info('No pending fallout reports to notify.');
            return 0;
        }

        $notificationMessage = "📢 *Laporan Fallout Tertunda* 📢\n\n";

        if ($unassignedReports->isNotEmpty()) {
            $notificationMessage .= "*Laporan Belum Di-assign:*\n";
            foreach ($unassignedReports as $report) {
                $notificationMessage .= "- ID: `{$report->id}` (`{$report->fallout_code}`)\n";
            }
            $notificationMessage .= "\n";
        }

        if ($inProgressReports->isNotEmpty()) {
            $notificationMessage .= "*Laporan Belum Selesai:*\n";
            foreach ($inProgressReports as $report) {
                $handler = $report->handler ? $report->handler->name : 'N/A';
                $notificationMessage .= "- ID: `{$report->id}` (`{$report->fallout_code}`) - Ditangani oleh: {$handler}\n";
            }
            $notificationMessage .= "\n";
        }

        $notificationMessage .= "Mohon untuk segera ditindaklanjuti.";

        $destination = env('TELEGRAM_GROUP_ID') ?? env('TELEGRAM_CHANNEL_ID');

        if ($destination) {
            try {
                SendTelegramNotificationJob::dispatch($destination, $notificationMessage);
                $this->info('Notification sent successfully.');
                Log::info('Hourly fallout notification sent successfully.');
            } catch (\Exception $e) {
                $this->error('Failed to send notification: ' . $e->getMessage());
                Log::error('Failed to send hourly fallout notification: ' . $e->getMessage());
            }
        } else {
            $this->warn('TELEGRAM_GROUP_ID or TELEGRAM_CHANNEL_ID not configured.');
            Log::warning('Cannot send hourly notification, no destination configured.');
        }

        return 0;
    }
}