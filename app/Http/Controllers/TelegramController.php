<?php

namespace App\Http\Controllers;

use App\Models\FalloutReport;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TelegramController extends Controller
{
    public function handle()
    {
        Log::info('Webhook received!');
        $update = Telegram::getWebhookUpdate();
        Log::info($update);

        if (!$update->has('message')) {
            Log::warning('Update does not contain a message.');
            return;
        }

        $chat_id = $update->getChat()->getId();
        $text = $update->getMessage()->getText();
        $user = $update->getMessage()->getFrom();

        $state = Cache::get($chat_id, ['step' => 'idle']);

        if ($text === '/start' || $text === '/newreport') {
            $this->startNewReport($chat_id, $user->getFirstName());
        } else {
            $this->continueConversation($chat_id, $text, $state);
        }
    }

    private function startNewReport($chat_id, $userName)
    {
        $this->sendMessage($chat_id, "Halo {$userName}! Mari kita mulai input laporan fallout.");

        $state = [
            'step' => 'tipe_order',
            'report_data' => [],
        ];

        Cache::put($chat_id, $state, now()->addMinutes(30));
        $this->sendMessage($chat_id, "Masukkan Tipe Order (AO/MO/DO/RO/SO):");
    }

    private function continueConversation($chat_id, $text, &$state)
    {
        switch ($state['step']) {
            case 'tipe_order':
                $state['report_data']['tipe_order'] = $text;
                $state['step'] = 'order_id';
                $this->sendMessage($chat_id, "Masukkan Order ID:");
                break;

            case 'order_id':
                $state['report_data']['order_id'] = $text;
                $state['step'] = 'nomer_layanan';
                $this->sendMessage($chat_id, "Masukkan Nomor Layanan:");
                break;

            case 'nomer_layanan':
                $state['report_data']['nomer_layanan'] = $text;
                $state['step'] = 'sn_ont';
                $this->sendMessage($chat_id, "Masukkan SN ONT:");
                break;

            case 'sn_ont':
                $state['report_data']['sn_ont'] = $text;
                $state['step'] = 'datek_odp';
                $this->sendMessage($chat_id, "Masukkan Datek ODP (contoh: ODP-GDS-FAT/75):");
                break;

            case 'datek_odp':
                $state['report_data']['datek_odp'] = $text;
                $state['step'] = 'port_odp';
                $this->sendMessage($chat_id, "Masukkan Port ODP (contoh: 3):");
                break;

            case 'port_odp':
                $state['report_data']['port_odp'] = (int) $text;
                $state['step'] = 'status_fallout';
                $this->sendMessage($chat_id, "Masukkan Status Fallout (contoh: act/data):");
                break;

            case 'status_fallout':
                $state['report_data']['status_fallout'] = $text;
                $state['step'] = 'respon_fallout';
                $this->sendMessage($chat_id, "Masukkan Respon Fallout:");
                break;

            case 'respon_fallout':
                $state['report_data']['respon_fallout'] = $text;
                $this->saveReport($chat_id, $state);
                break;

            default:
                $this->sendMessage($chat_id, "Gunakan perintah /newreport untuk memulai laporan baru.");
                break;
        }

        if (($state['step'] ?? null) !== 'idle') {
            Cache::put($chat_id, $state, now()->addMinutes(30));
        }
    }

    private function saveReport($chat_id, $state)
    {
        FalloutReport::create($state['report_data']);
        $this->sendMessage($chat_id, "✅ Laporan berhasil disimpan. Terima kasih!");
        Cache::forget($chat_id);
        $state['step'] = 'idle';
    }

    private function sendMessage($chat_id, $message)
    {
        Telegram::sendMessage([
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown',
        ]);
    }
}
