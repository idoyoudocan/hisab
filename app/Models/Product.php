<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'name', 'sku', 'unit',
        'purchase_price', 'sale_price', 'stock', 'low_stock_at',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'stock' => 'decimal:2',
            'low_stock_at' => 'decimal:2',
        ];
    }

    public function saleItems(): HasMany { return $this->hasMany(SaleItem::class); }
    public function purchaseItems(): HasMany { return $this->hasMany(PurchaseItem::class); }

    public function isLowStock(): bool
    {
        return (float) $this->stock <= (float) $this->low_stock_at;
    }
}
