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
        'total', 'discount_amount', 'paid', 'payment_method', 'note',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'total' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'paid' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function items(): HasMany { return $this->hasMany(SaleItem::class); }

    /** Sum of line totals before discount. total = subtotal − discount. */
    public function subtotalAmount(): float
    {
        return round((float) $this->items->sum('total'), 2);
    }

    /** Due on this sale itself (collections are tracked at customer level). */
    public function dueAmount(): float
    {
        return round((float) $this->total - (float) $this->paid, 2);
    }

    /** Cost of goods sold: purchase price × qty, using current costs. */
    public function cogsAmount(): float
    {
        $cost = 0.0;
        foreach ($this->items as $item) {
            $cost += (float) ($item->product->purchase_price ?? 0) * (float) $item->qty;
        }

        return round($cost, 2);
    }

    /** Profit after discount: total (subtotal − discount) − cost of goods. */
    public function profitAmount(): float
    {
        return round((float) $this->total - $this->cogsAmount(), 2);
    }
}
