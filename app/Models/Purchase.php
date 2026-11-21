<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'supplier_id', 'purchase_date',
        'total', 'paid', 'note',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'total' => 'decimal:2',
            'paid' => 'decimal:2',
        ];
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function items(): HasMany { return $this->hasMany(PurchaseItem::class); }

    public function dueAmount(): float
    {
        return round((float) $this->total - (float) $this->paid, 2);
    }
}
