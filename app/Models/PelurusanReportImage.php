<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PelurusanReportImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'pelurusan_report_id',
        'image_path',
    ];

    public function pelurusanReport(): BelongsTo
    {
        return $this->belongsTo(PelurusanReport::class);
    }
}
