<?php

namespace App\Livewire;

use App\Jobs\SendTelegramNotificationJob;
use App\Models\FalloutReport;
use App\Models\FalloutStatus;
use App\Models\TelegramGroup;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;

class FalloutReportDetail extends Component
{
    #[Url]
    public $date;

    public FalloutReport $report;
    public $showStatusModal = false;
    public $newStatusId;
    public $keterangan = '';
    public $availableStatuses = [];

    private const ON_PROGRESS = 'OnProgress';
    private const COMPLETED_STATUSES = ['FA', 'input ulang', 'PI'];

    public function mount($id, $date = null)
    {
        $this->report = FalloutReport::with(['orderType', 'falloutStatus', 'reporter', 'assignedToUser'])->findOrFail($id);
        $this->date = $date ?? $this->date;
    }

    public function takeOrder()
    {
        if ($this->report->assigned_to_user_id) {
            $this->addError('error', 'Laporan ini sudah diambil.');
            return;
        }
        
        try {
            DB::beginTransaction();
            $onProgressStatus = FalloutStatus::where('name', self::ON_PROGRESS)->firstOrFail();
            $this->report->update([
                'fallout_status_id'     => $onProgressStatus->id,
                'assigned_to_user_id'   => Auth::id(),
                'taken_at'              => now(),
            ]);
            DB::commit();

            $this->report->refresh();
            
            // Logika notifikasi di sini mirip dengan PelurusanReport
            // Anda bisa membuat fungsi private reusable jika strukturnya sama

            $this->dispatch('reportAssigned');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('error', 'Gagal mengambil laporan: ' . $e->getMessage());
        }
    }

    public function openStatusModal()
    {
        $allStatuses = FalloutStatus::all();
        $currentStatusName = $this->report->falloutStatus?->name;

        $this->availableStatuses = $allStatuses->filter(function ($status) use ($currentStatusName) {
            if ($currentStatusName === 'Open') return true;
            return !in_array($status->name, ['Open', 'OnProgress']);
        });

        $this->newStatusId = $this->report->fallout_status_id;
        $this->keterangan = $this->report->resolution_notes;
        $this->showStatusModal = true;
    }

    public function closeStatusModal()
    {
        $this->showStatusModal = false;
        $this->reset(['newStatusId', 'keterangan']);
    }

    public function changeStatus()
    {
        // ... (Logika mirip dengan PelurusanReportDetail, Anda bisa mengadaptasinya)
    }

    public function render()
    {
        return view('livewire.fallout-report-detail');
    }

    private function escapeMarkdown($text): string
    {
        if (is_null($text)) {
            return 'N/A';
        }
        $chars = ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '.', '!'];
        return str_replace($chars, array_map(fn ($char) => '\\' . $char, $chars), $text);
    }
}