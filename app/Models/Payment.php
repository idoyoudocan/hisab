<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use BelongsToBusiness;

    protected $fillable = [
        'business_id', 'party_type', 'party_id', 'amount',
        'direction', 'method', 'date', 'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }
}
