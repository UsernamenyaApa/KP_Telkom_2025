<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PelurusanReport extends Model
{
    use HasFactory;

    protected $table = 'pelurusan_reports';

    protected $fillable = [
        'id_harian',
        'pelurusan_code',
        'number_incident',
        'incident_fallout_description',
        'tipe_order_id',
        'order_id',
        'nomer_layanan',
        'sn_ont',
        'datek_odp',
        'port_odp',
        'fallout_status_id',
        'assigned_to_user_id',
        'assigned_at',
        'notified_unassigned_at',
        'notified_uncompleted_at',
        'keterangan',
        'resolution_notes',
        'reporter_user_id',
        'reporter_telegram_id',
        'reporter_telegram_username',
        'taken_at',
        'completed_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'completed_at' => 'datetime',
        'assigned_at' => 'datetime',
        'notified_unassigned_at' => 'datetime',
        'notified_uncompleted_at' => 'datetime',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_user_id');
    }

    public function orderType(): BelongsTo
    {
        return $this->belongsTo(OrderType::class, 'tipe_order_id');
    }

    public function falloutStatus(): BelongsTo
    {
        return $this->belongsTo(FalloutStatus::class);
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(PelurusanReportImage::class);
    }
}