<?php

namespace App\Models;

use App\Support\Hisab;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'business_id', 'amount', 'method', 'months',
        'paid_at', 'note', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public static function methods(): array
    {
        return [
            'cash' => 'নগদ',
            'bkash' => 'বিকাশ',
            'nagad' => 'নগদ',
            'bank' => 'ব্যাংক',
        ];
    }

    public function methodLabel(): string
    {
        return self::methods()[$this->method] ?? $this->method;
    }
}
