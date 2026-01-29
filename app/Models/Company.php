<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'phone',
        'email',
        'website',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the company.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted company information for invoices.
     */
    public function getFormattedInfoAttribute(): string
    {
        $info = [$this->name];

        if ($this->address) {
            $info[] = $this->address;
        }

        if ($this->phone) {
            $info[] = 'Phone: '.$this->phone;
        }

        if ($this->email) {
            $info[] = 'Email: '.$this->email;
        }

        if ($this->website) {
            $info[] = 'Web: '.$this->website;
        }

        return implode("\n", $info);
    }

    /**
     * Set this company as the default for the user.
     */
    public function setAsDefault(): void
    {
        // Remove default flag from all user's companies
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this company as default
        $this->update(['is_default' => true]);
    }
}
