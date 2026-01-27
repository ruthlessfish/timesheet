<?php

namespace App\Models\Traits;

trait HasStatus
{
    public function getStatusCssAttribute(): string
    {
        return match ($this->status) {
            'active' => 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400',
            'completed' => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400',
            'on-hold' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400',
            default => 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300',
        };
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isOnHold(): bool
    {
        return $this->status === 'on_hold';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function activate(): void
    {
        $this->status = 'active';
        $this->save();
    }

    public function deactivate(): void
    {
        $this->status = 'inactive';
        $this->save();
    }
}