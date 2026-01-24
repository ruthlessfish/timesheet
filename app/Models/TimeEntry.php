<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class TimeEntry extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'description',
        'start_time',
        'end_time',
        'duration',
        'hourly_rate',
        'is_billable',
        'is_invoiced',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'hourly_rate' => 'decimal:2',
        'is_billable' => 'boolean',
        'is_invoiced' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function calculateDuration(): void
    {
        if ($this->start_time && $this->end_time) {
            $this->duration = $this->start_time->diffInMinutes($this->end_time);
        }
    }

    public function stop(): void
    {
        $this->end_time = now();
        $this->calculateDuration();
        $this->save();
    }

    public function getAmountAttribute(): float
    {
        $rate = $this->hourly_rate ?? $this->project->hourly_rate ?? $this->project->client->hourly_rate ?? 0;
        return ($this->duration / 60) * $rate;
    }
}
