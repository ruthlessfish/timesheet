<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Models\Traits\HasStatus;

class Project extends Model
{
    use HasFactory, HasStatus;

    protected $fillable = [
        'client_id',
        'user_id',
        'name',
        'description',
        'hourly_rate',
        'budget',
        'status',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'budget' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function getTotalHoursAttribute(): float
    {
        return $this->timeEntries()->sum('duration') / 60;
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->timeEntries()
            ->where('is_billable', true)
            ->get()
            ->sum(function ($entry) {
                return ($entry->duration / 60) * ($entry->hourly_rate ?? $this->hourly_rate ?? $this->client->hourly_rate ?? 0);
            });
    }

    public function getIsOverBudgetAttribute(): bool
    {
        if (is_null($this->budget)) {
            return false;
        }
        return $this->total_amount > $this->budget;
    }

}
