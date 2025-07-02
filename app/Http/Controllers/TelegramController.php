<?php

namespace App\Http\Controllers;

use App\Models\FalloutReport;
// Model-model tidak lagi digunakan karena pilihan dibuat secara hardcoded.
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Exceptions\TelegramSDKException;

class TelegramController extends Controller
{
    /**
     * Handles incoming Telegram updates.
     */
    public function handle()
    {
        Log::info('Webhook received!');
        $update = Telegram::getWebhookUpdate();
        Log::info($update);

        // Handle button clicks (Callback Queries)
        if ($update->isType('callback_query')) {
            $this->handleCallbackQuery($update);
            return;
        }

        // Handle regular messages
        if ($update->has('message') && $update->getMessage()->has('text')) {
            $this->handleMessage($update);
            return;
        }

        Log::warning('Update does not contain a message with text or a callback query.');
    }

    /**
     * Handle incoming messages from users.
     */
    private function handleMessage($update)
    {
        $chat_id = $update->getChat()->getId();
        $text = $update->getMessage()->getText();
        $user = $update->getMessage()->getFrom();

        // Command handling
        if ($text === '/start') {
            $this->resetStateAndShowMenu($chat_id, "Halo " . $user->getFirstName() . "! Selamat datang. Silakan pilih jenis laporan yang ingin Anda buat.");
            return;
        }

        if ($text === '/cancel') {
            $this->resetStateAndShowMenu($chat_id, "Proses dibatalkan. Anda bisa memulai lagi kapan saja.");
            return;
        }

        // Continue conversation based on state
        $state = Cache::get($chat_id, ['step' => 'idle']);
        if ($state['step'] !== 'idle') {
            $this->continueConversation($chat_id, $text, $state);
        } else {
            $this->showMainMenu($chat_id, "Gunakan perintah /start untuk memulai atau pilih menu di bawah.");
        }
    }

    /**
     * Handle button clicks from inline keyboards.
     */
    private function handleCallbackQuery($update)
    {
        $callbackQuery = $update->getCallbackQuery();
        $chat_id = $callbackQuery->getMessage()->getChat()->getId();
        $data = $callbackQuery->getData();

        // Acknowledge the button click first
        try {
            Telegram::answerCallbackQuery(['callback_query_id' => $callbackQuery->getId()]);
        } catch (TelegramSDKException $e) {
            Log::error("Failed to answer callback query: " . $e->getMessage());
        }

        // Handle fallout type selection from main menu
        if (str_starts_with($data, 'start_fallout_')) {
            $orderType = substr($data, strlen('start_fallout_'));
            $this->startFalloutReport($chat_id, $orderType);
            return;
        }

        // Handle other selections within the form
        if (str_starts_with($data, 'select_')) {
            $this->handleSelectionCallback($chat_id, $data);
            return;
        }

        // Handle other main menu options
        switch ($data) {
            case 'start_pelurusan':
                $this->sendMessage($chat_id, "Fitur 'Pelurusan Data' sedang dalam pengembangan. Silakan pilih menu lain.");
                $this->showMainMenu($chat_id);
                break;
        }
    }

    /**
     * Handles callbacks from selection keyboards within the form.
     */
    private function handleSelectionCallback($chat_id, $data)
    {
        $state = Cache::get($chat_id);
        if (!$state || $state['step'] === 'idle') {
            $this->sendMessage($chat_id, "Sesi Anda telah berakhir atau tidak valid. Silakan mulai lagi dengan /start.");
            return;
        }

        $parts = explode('_', $data, 3);
        $selectedValue = $parts[2];

        $stepName = str_replace('_id', '', $state['step']);
        $state['report_data'][$stepName] = $selectedValue;

        $this->advanceStep($chat_id, $state);
        Cache::put($chat_id, $state, now()->addMinutes(30));
    }


    /**
     * Guides the user through the conversation steps (handles text input).
     */
    private function continueConversation($chat_id, $text, &$state)
    {
        $stepName = str_replace('_id', '', $state['step']);
        $state['report_data'][$stepName] = ($text === 'skip' || $text === 'SKIP') ? null : $text;

        $this->advanceStep($chat_id, $state);
        Cache::put($chat_id, $state, now()->addMinutes(30));
    }

    /**
     * Advances the conversation to the next step.
     */
    private function advanceStep($chat_id, &$state)
    {
        // 'order_type_id' is removed from steps as it's selected upfront
        $steps = [
            'hd_daman_id', 'order_id', 'nomer_layanan', 'sn_ont',
            'datek_odp', 'port_odp', 'fallout_status_id', 'respon_fallout'
        ];

        $currentStepIndex = array_search($state['step'], $steps);
        $nextStepIndex = $currentStepIndex + 1;

        if ($nextStepIndex < count($steps)) {
            $state['step'] = $steps[$nextStepIndex];
            $this->askQuestionForStep($chat_id, $state);
        } else {
            $this->saveFalloutReport($chat_id, $state);
        }
    }


    /**
     * Asks the user the correct question for the current step.
     */
    private function askQuestionForStep($chat_id, &$state)
    {
        // Note the numbering is now out of 8 instead of 9
        switch ($state['step']) {
            case 'hd_daman_id':
                $options = ['HD Daman A', 'HD Daman B', 'HD Daman C', 'HD Daman D'];
                $this->sendSelectionKeyboard($chat_id, "1/8: Siapa HD Daman yang menangani orderan ini?", $options, 'select_daman_');
                break;
            case 'order_id':
                $this->sendMessage($chat_id, "2/8: Masukkan Order ID:");
                break;
            case 'nomer_layanan':
                $this->sendMessage($chat_id, "3/8: Masukkan Nomor Layanan:");
                break;
            case 'sn_ont':
                $this->sendMessage($chat_id, "4/8: Masukkan SN ONT:");
                break;
            case 'datek_odp':
                $this->sendMessage($chat_id, "5/8: Masukkan Datek ODP (contoh: ODP-GDS-FAT/75):");
                break;
            case 'port_odp':
                $this->sendMessage($chat_id, "6/8: Masukkan Port ODP (contoh: 3):");
                break;
            case 'fallout_status_id':
                $options = ['ONU/ONT Rusak', 'Jaringan', 'Alamat Tidak Ditemukan', 'Lainnya'];
                $this->sendSelectionKeyboard($chat_id, "7/8: Silakan pilih Status Fallout:", $options, 'select_status_');
                break;
            case 'respon_fallout':
                $this->sendMessage($chat_id, "8/8: Masukkan Keterangan/Respon Fallout. (Ketik 'skip' jika tidak ada)");
                break;
        }
    }


    /**
     * Starts the process for a new fallout report with a pre-selected order type.
     */
    private function startFalloutReport($chat_id, $orderType)
    {
        $state = [
            'process' => 'fallout',
            'step' => 'hd_daman_id', // Start with the first form question
            'report_data' => [
                'order_type' => $orderType // Pre-fill the order type
            ],
        ];

        $this->sendMessage($chat_id, "Baik, mari kita mulai input laporan Fallout *{$orderType}*. (Gunakan /cancel untuk membatalkan)");
        $this->askQuestionForStep($chat_id, $state); // Ask the first question
        Cache::put($chat_id, $state, now()->addMinutes(30));
    }

    /**
     * Saves the completed report to the database.
     */
    private function saveFalloutReport($chat_id, $state)
    {
        $reportData = [];
        foreach ($state['report_data'] as $key => $value) {
            $newKey = str_replace('_id', '', $key);
            $reportData[$newKey] = $value;
        }

        $reportData['tanggal'] = now();

        try {
            $report = DB::transaction(function () use ($reportData) {
                $newReport = FalloutReport::create($reportData);
                $noReport = 'FO-' . now()->format('Ymd') . '-' . $newReport->id;
                $newReport->no_report = $noReport;
                $newReport->save();
                return $newReport;
            });

            $this->sendMessage($chat_id, "✅ Laporan berhasil disimpan dengan No Laporan: *" . $report->no_report . "*. Terima kasih!");
        } catch (\Exception $e) {
            Log::error("Failed to save report: " . $e->getMessage() . " Data: " . json_encode($reportData));
            $this->sendMessage($chat_id, "❌ Terjadi kesalahan saat menyimpan laporan. Silakan coba lagi.");
        } finally {
            $this->resetStateAndShowMenu($chat_id);
        }
    }

    /**
     * Displays the main menu with choices for each order type.
     */
    private function showMainMenu($chat_id, $messageText = "Silakan pilih menu:")
    {
        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📊 Fallout AO', 'callback_data' => 'start_fallout_AO'],
                    ['text' => '📊 Fallout MO', 'callback_data' => 'start_fallout_MO'],
                ],
                [
                    ['text' => '📊 Fallout DO', 'callback_data' => 'start_fallout_DO'],
                    ['text' => '📊 Fallout RO', 'callback_data' => 'start_fallout_RO'],
                ],
                [
                    ['text' => '📊 Fallout SO', 'callback_data' => 'start_fallout_SO'],
                    ['text' => '✏️ Pelurusan Data', 'callback_data' => 'start_pelurusan'],
                ]
            ]
        ];
        $this->sendMessage($chat_id, $messageText, $keyboard);
    }

    /**
     * Resets the user's state and shows the main menu.
     */
    private function resetStateAndShowMenu($chat_id, $messageText = null)
    {
        Cache::forget($chat_id);
        if ($messageText) {
            $this->showMainMenu($chat_id, $messageText);
        } else {
            $this->showMainMenu($chat_id);
        }
    }

    /**
     * Sends a message with an inline keyboard from a hardcoded array.
     */
    private function sendSelectionKeyboard($chat_id, $text, array $options, $callbackPrefix)
    {
        if (empty($options)) {
            $this->sendMessage($chat_id, "Tidak ada data pilihan yang tersedia. Silakan hubungi admin.");
            $this->resetStateAndShowMenu($chat_id, "Proses dibatalkan karena tidak ada data.");
            return;
        }

        $keyboard = collect($options)->map(function ($option) use ($callbackPrefix) {
            return ['text' => $option, 'callback_data' => $callbackPrefix . $option];
        })->chunk(2)->toArray();

        $this->sendMessage($chat_id, $text, ['inline_keyboard' => $keyboard]);
    }


    /**
     * A helper function to send messages.
     */
    private function sendMessage($chat_id, $text, $reply_markup = null)
    {
        $params = [
            'chat_id' => $chat_id,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ];

        if ($reply_markup) {
            $params['reply_markup'] = json_encode($reply_markup);
        }

        try {
            Telegram::sendMessage($params);
        } catch (TelegramSDKException $e) {
            Log::error("Failed to send message to chat_id {$chat_id}: " . $e->getMessage());
        }
    }
}
