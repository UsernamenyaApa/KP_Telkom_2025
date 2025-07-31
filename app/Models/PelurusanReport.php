<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PelurusanReport extends Model
{
    use HasFactory;

    protected $table = 'pelurusan_reports';

    protected $guarded = [

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
}
