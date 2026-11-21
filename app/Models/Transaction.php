<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'type', 'category', 'amount',
        'date', 'method', 'scope', 'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }
}
