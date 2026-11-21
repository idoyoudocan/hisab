<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    protected $fillable = [
        'user_id', 'name', 'type', 'phone', 'address',
        'opening_cash', 'plan', 'trial_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash' => 'decimal:2',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function customers(): HasMany { return $this->hasMany(Customer::class); }
    public function suppliers(): HasMany { return $this->hasMany(Supplier::class); }
    public function sales(): HasMany { return $this->hasMany(Sale::class); }
    public function purchases(): HasMany { return $this->hasMany(Purchase::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function transactions(): HasMany { return $this->hasMany(Transaction::class); }
    public function categories(): HasMany { return $this->hasMany(Category::class); }

    public function isTrialActive(): bool
    {
        return $this->plan === 'trial'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    /** Seed the default income/expense categories for a new business. */
    public static function seedCategories(self $business): void
    {
        $defaults = [
            ['বিক্রয়', 'income', 'shop'],
            ['কেনাকাটা', 'expense', 'shop'],
            ['দোকান ভাড়া', 'expense', 'shop'],
            ['বেতন', 'expense', 'shop'],
            ['বিদ্যুৎ বিল', 'expense', 'shop'],
            ['যাতায়াত', 'expense', 'shop'],
            ['ব্যক্তিগত', 'expense', 'personal'],
            ['অন্যান্য', 'expense', 'shop'],
            ['অন্যান্য আয়', 'income', 'shop'],
        ];

        foreach ($defaults as [$name, $type, $scope]) {
            Category::firstOrCreate(
                ['business_id' => $business->id, 'name' => $name, 'type' => $type],
                ['scope' => $scope]
            );
        }
    }
}
