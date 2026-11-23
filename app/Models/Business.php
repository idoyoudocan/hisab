<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    /** Business types offered at registration and in settings. */
    public const TYPES = [
        'মুদি দোকান',
        'ফার্মেসি',
        'মোবাইল রিচার্জ ও লোড',
        'কাপড়ের দোকান',
        'ইলেকট্রনিক্স',
        'হার্ডওয়্যার',
        'রেস্টুরেন্ট / খাবার',
        'কসমেটিকস',
        'বই ও স্টেশনারি',
        'অন্যান্য',
    ];

    public const TYPE_RECHARGE = 'মোবাইল রিচার্জ ও লোড';

    /**
     * Optional modules a shopkeeper can hide from Settings.
     * Key => Bangla label. Defaults: every module is ON (empty settings = all on).
     */
    public const MODULES = [
        'cash_count' => 'ক্যাশ মেলান',
        'money_accounts' => 'টাকার হিসাব',
        'sim_balance' => 'সিম ব্যালেন্স',
        'capital' => 'আমার মূলধন',
        'ledger' => 'বাকি খাতা',
        'purchases' => 'কেনাকাটা ও সাপ্লায়ার',
        'day_close' => 'দিন শেষ',
        'profit_loss' => 'লাভ-ক্ষতি',
        'reports' => 'রিপোর্ট',
    ];

    /** Account status set by the admin: active (normal) or disabled (login blocked). */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';

    protected $fillable = [
        'user_id', 'name', 'type', 'phone', 'address',
        'opening_cash', 'plan', 'trial_ends_at', 'settings', 'paid_until',
        'status', 'disabled_reason', 'disabled_at',
    ];

    protected function casts(): array
    {
        return [
            'opening_cash' => 'decimal:2',
            'trial_ends_at' => 'datetime',
            'paid_until' => 'date',
            'settings' => 'array',
            'disabled_at' => 'datetime',
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
    public function moneyAccounts(): HasMany { return $this->hasMany(MoneyAccount::class); }
    public function cashCounts(): HasMany { return $this->hasMany(CashCount::class); }
    public function subscriptionPayments(): HasMany { return $this->hasMany(SubscriptionPayment::class); }
    public function planRequests(): HasMany { return $this->hasMany(PlanRequest::class); }
    public function adminNotices(): HasMany { return $this->hasMany(AdminNotice::class); }

    /** Has the admin disabled this account? Disabled = login blocked, data kept. */
    public function isDisabled(): bool
    {
        return $this->status === self::STATUS_DISABLED;
    }

    public function isActive(): bool
    {
        return ! $this->isDisabled();
    }

    /**
     * The name of the business's current paid plan, e.g. "স্ট্যান্ডার্ড".
     * Derived from the latest subscription payment's plan slug; null when
     * unknown (e.g. paid before plans existed) — callers then show just
     * the paid-until date.
     */
    public function currentPlanName(): ?string
    {
        $latest = $this->subscriptionPayments()
            ->whereNotNull('plan_slug')
            ->latest('paid_at')->latest('id')
            ->first();

        if ($latest !== null) {
            return \App\Support\Plans::find($latest->plan_slug)['name'] ?? null;
        }

        return \App\Support\Plans::find($this->plan ?? '')['name'] ?? null;
    }

    public function isTrialActive(): bool
    {
        return $this->plan === 'trial'
            && $this->trial_ends_at !== null
            && $this->trial_ends_at->isFuture();
    }

    /** Is an optional module visible for this business? Default: yes. */
    public function moduleEnabled(string $key): bool
    {
        $settings = $this->settings ?? [];

        return ($settings[$key] ?? true) !== false;
    }

    /** Subscription status: paid | trial | expired. */
    public function subscriptionStatus(): string
    {
        if ($this->paid_until !== null && $this->paid_until->isFuture()) {
            return 'paid';
        }
        if ($this->isTrialActive()) {
            return 'trial';
        }

        return 'expired';
    }

    /** Whole days left in the trial, null when not on trial. */
    public function trialDaysLeft(): ?int
    {
        if (! $this->isTrialActive()) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->trial_ends_at->copy()->startOfDay(), false);
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

        if ($business->type === self::TYPE_RECHARGE) {
            self::seedRechargeCategories($business);
        }
    }

    /** Extra categories for recharge/load businesses. */
    public static function seedRechargeCategories(self $business): void
    {
        $extras = [
            ['রিচার্জ কমিশন', 'income', 'shop'],
            ['সিম ব্যালেন্স কেনা', 'expense', 'shop'],
        ];

        foreach ($extras as [$name, $type, $scope]) {
            Category::firstOrCreate(
                ['business_id' => $business->id, 'name' => $name, 'type' => $type],
                ['scope' => $scope]
            );
        }
    }
}
