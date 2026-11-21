<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashCount extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'counted_at', 'denominations',
        'total', 'system_cash', 'difference', 'note',
    ];

    protected function casts(): array
    {
        return [
            'counted_at' => 'datetime',
            'denominations' => 'array',
            'total' => 'decimal:2',
            'system_cash' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    public function isMatched(): bool
    {
        return abs((float) $this->difference) < 0.01;
    }
}
