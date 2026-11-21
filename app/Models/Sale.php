<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'customer_id', 'sale_date',
        'total', 'paid', 'payment_method', 'note',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany { return $this->hasMany(SaleItem::class); }

    /** Due on this sale itself (collections are tracked at customer level). */
    public function dueAmount(): float
    {
        return round((float) $this->total - (float) $this->paid, 2);
    }

    /** Approx profit: (sale price − purchase price) × qty, using current costs. */
    public function profitAmount(): float
    {
        $profit = 0.0;
        foreach ($this->items as $item) {
            $cost = (float) ($item->product->purchase_price ?? 0);
            $profit += ((float) $item->price - $cost) * (float) $item->qty;
        }

        return round($profit, 2);
    }
}
