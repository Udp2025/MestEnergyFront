<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiAlertEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_alert_id',
        'user_id',
        'kpi_value',
        'context',
        'triggered_at',
        'read_at',
    ];

    protected $casts = [
        'kpi_value' => 'float',
        'context' => 'array',
        'triggered_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(KpiAlert::class, 'kpi_alert_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        if ($this->read_at) {
            return;
        }
        $this->read_at = now();
        $this->save();
    }
}
