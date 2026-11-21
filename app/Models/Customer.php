<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'name', 'phone', 'address', 'credit_limit'];

    protected function casts(): array
    {
        return ['credit_limit' => 'decimal:2'];
    }

    public function sales(): HasMany { return $this->hasMany(Sale::class); }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'party_id')
            ->where('party_type', 'customer');
    }

    /** Total due: sales (total − paid) minus collections. */
    public function dueAmount(): float
    {
        $sales = $this->sales()->selectRaw('COALESCE(SUM(total),0) as t, COALESCE(SUM(paid),0) as p')->first();
        $collected = $this->payments()->where('direction', 'in')->sum('amount');

        return round(((float) $sales->t - (float) $sales->p) - (float) $collected, 2);
    }

    /** Is this customer's due above their credit limit? */
    public function isOverLimit(): bool
    {
        return $this->credit_limit !== null
            && (float) $this->credit_limit > 0
            && $this->dueAmount() > (float) $this->credit_limit;
    }

    /**
     * Age in days of the oldest still-unpaid sale. Collections are tracked
     * at customer level, so this is the oldest sale whose own
     * (total − paid) is still positive — a Khatabook-style aging signal.
     * Returns null when nothing is outstanding.
     */
    public function oldestDueDays(): ?int
    {
        $oldest = $this->sales()
            ->whereRaw('total > paid')
            ->orderBy('sale_date')
            ->first(['sale_date']);

        if (! $oldest) {
            return null;
        }

        return (int) $oldest->sale_date->startOfDay()->diffInDays(now()->startOfDay());
    }
}
