<?php

namespace App\Livewire;

use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Telegram\Bot\Laravel\Facades\Telegram;

class FalloutReportDashboard extends Component
{
    use WithPagination;

    public $date;

    public function mount()
    {
        $this->date = Carbon::today()->format('Y-m-d');
    }

    public function takeOrder($reportId)
    {
        $report = FalloutReport::with(['orderType', 'reporter'])->find($reportId);

        if ($report) {
            $onProgressStatus = FalloutStatus::where('name', 'OnProgress')->first();
            if ($onProgressStatus) {
                $report->fallout_status_id = $onProgressStatus->id;
                $report->assigned_to_user_id = Auth::id(); // Store user ID
                $report->save();

                $user = Auth::user();
                \Illuminate\Support\Facades\Log::info('Authenticated user telegram_user_id: ' . $user->telegram_user_id);
                \Illuminate\Support\Facades\Log::info('TELEGRAM_GROUP_ID from env: ' . env('TELEGRAM_GROUP_ID'));
                \Illuminate\Support\Facades\Log::info('Fallout Report order_type_id: ' . $report->order_type_id);
                \Illuminate\Support\Facades\Log::info('Fallout Report orderType object: ' . ($report->orderType ? 'Loaded' : 'NULL'));

                $message = "Laporan Fallout On Progress\n\n" .
                           "Tipe Order: " . ($report->orderType ? $report->orderType->name : 'N/A') . "\n" .
                           "OrderID: " . $report->order_id . "\n" .
                           "Nomor Layanan: " . $report->nomer_layanan . "\n" .
                           "SN ONT: " . $report->sn_ont . "\n" .
                           "Datek ODP: " . $report->datek_odp . "\n" .
                           "Port ODP: " . $report->port_odp . "\n\n" .
                           "Keterangan:\n" . $report->keterangan . "\n\n" .
                           "----------------------------------------\n" .
                           "Created By: @" . ($report->reporter ? $report->reporter->telegram_username : 'N/A') . "\n" .
                           "Create Order: " . $report->created_at->format('Y-m-d H:i:s') . "\n\n" .
                           "Take by: @" . $user->telegram_username;

                // Send to personal chat (taker)
                try {
                    if ($user->telegram_user_id) {
                        Telegram::sendMessage([
                            'chat_id' => $user->telegram_user_id,
                            'text' => $message,
                        ]);
                        \Illuminate\Support\Facades\Log::info('Telegram personal message sent to taker ID: ' . $user->telegram_user_id);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error sending Telegram personal message to taker: ' . $e->getMessage());
                }

                // Send to personal chat (reporter)
                try {
                    if ($report->reporter && $report->reporter->telegram_user_id) {
                        Telegram::sendMessage([
                            'chat_id' => $report->reporter->telegram_user_id,
                            'text' => $message,
                        ]);
                        \Illuminate\Support\Facades\Log::info('Telegram personal message sent to reporter ID: ' . $report->reporter->telegram_user_id);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error sending Telegram personal message to reporter: ' . $e->getMessage());
                }

                // Send to group chat
                try {
                    $groupChatId = env('TELEGRAM_GROUP_ID');
                    if ($groupChatId) {
                        Telegram::sendMessage([
                            'chat_id' => $groupChatId,
                            'text' => $message,
                        ]);
                        \Illuminate\Support\Facades\Log::info('Telegram group message sent to chat ID: ' . $groupChatId);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error sending Telegram group message: ' . $e->getMessage());
                }
            }
        }
    }

    public function render()
    {
        $reports = FalloutReport::with(['reporter', 'orderType', 'falloutStatus', 'assignedToUser'])
            ->whereDate('created_at', $this->date)
            ->orderBy('created_at', 'asc')
            ->paginate(10);

        return view('livewire.fallout-report-dashboard', [
            'reports' => $reports,
        ]);
    }
}
