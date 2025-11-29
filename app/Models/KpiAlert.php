<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'kpi_slug',
        'comparison_operator',
        'threshold_value',
        'site_id',
        'is_active',
        'cooldown_minutes',
    ];

    protected $casts = [
        'threshold_value' => 'float',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'last_value' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(KpiAlertEvent::class);
    }

    public function definition(): ?array
    {
        return config('kpi_alerts.definitions.' . $this->kpi_slug);
    }
}
