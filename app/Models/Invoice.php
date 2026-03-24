<?php

namespace App\Models;

use App\Models\Traits\HasStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, HasStatus, SoftDeletes;

    protected $fillable = [
        'user_id',
        'client_id',
        'invoice_number',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = 'INV-'.date('Y').'-'.str_pad((Invoice::whereYear('created_at', date('Y'))->count() + 1), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function getSubTotalAttribute(): float
    {
        return $this->items()->sum('amount');
    }

    public function getTaxAmountAttribute(): float
    {
        return $this->subtotal * ($this->tax_rate / 100);
    }

    public function getTotalAttribute(): float
    {
        return $this->subtotal + $this->tax_amount;
    }

    public function getTotalHoursAttribute(): float
    {
        return $this->items()->whereNotNull('time_entry_id')->sum('quantity');
    }

    /**
     * Get invoice items consolidated for display.
     *
     * Time entry items are grouped by project, summing hours and amounts.
     * Expense items are returned individually.
     *
     * @return \Illuminate\Support\Collection<int, object{type: string, description: string, quantity: float, rate: float, amount: float}>
     */
    public function getConsolidatedItemsAttribute(): \Illuminate\Support\Collection
    {
        $items = $this->items->loadMissing('timeEntry.project', 'expense');

        $consolidated = collect();

        $timeEntryItems = $items->filter(fn ($item) => $item->time_entry_id !== null);
        $expenseItems = $items->filter(fn ($item) => $item->expense_id !== null);

        $grouped = $timeEntryItems->groupBy(fn ($item) => $item->timeEntry?->project_id ?? 'no-project');

        foreach ($grouped as $projectItems) {
            $totalQuantity = $projectItems->sum('quantity');
            $totalAmount = $projectItems->sum('amount');
            $rate = $totalQuantity > 0 ? round($totalAmount / $totalQuantity, 2) : 0;
            $firstItem = $projectItems->first();
            $projectName = $firstItem->timeEntry?->project?->name ?? $firstItem->description;

            $consolidated->push((object) [
                'type' => 'service',
                'description' => $projectName,
                'quantity' => round($totalQuantity, 2),
                'rate' => $rate,
                'amount' => round($totalAmount, 2),
            ]);
        }

        foreach ($expenseItems as $item) {
            $consolidated->push((object) [
                'type' => 'expense',
                'description' => $item->description,
                'quantity' => $item->quantity,
                'rate' => $item->rate,
                'amount' => $item->amount,
            ]);
        }

        return $consolidated;
    }
}
