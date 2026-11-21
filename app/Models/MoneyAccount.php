<?php

namespace App\Models;

use App\Support\Hisab;
use Illuminate\Database\Eloquent\Model;

class MoneyAccount extends Model
{
    use BelongsToBusiness;

    public const TYPE_CASH_REGISTER = 'cash_register';
    public const TYPE_BANK = 'bank';
    public const TYPE_MOBILE_BANKING = 'mobile_banking';
    public const TYPE_SIM = 'sim';
    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'business_id', 'type', 'provider', 'label',
        'identifier', 'balance', 'note',
    ];

    protected function casts(): array
    {
        return ['balance' => 'decimal:2'];
    }

    public static function types(): array
    {
        return [
            self::TYPE_CASH_REGISTER => 'ক্যাশ রেজিস্টার',
            self::TYPE_BANK => 'ব্যাংক',
            self::TYPE_MOBILE_BANKING => 'মোবাইল ব্যাংকিং',
            self::TYPE_SIM => 'সিম ব্যালেন্স',
            self::TYPE_OTHER => 'অন্যান্য',
        ];
    }

    /** Provider choices per type (Bangla labels). */
    public static function providersFor(string $type): array
    {
        return match ($type) {
            self::TYPE_BANK => [
                'ডাচ-বাংলা ব্যাংক', 'সোনালী ব্যাংক', 'ইসলামী ব্যাংক', 'ব্র্যাক ব্যাংক',
                'অগ্রণী ব্যাংক', 'জনতা ব্যাংক', 'রূপালী ব্যাংক', 'পূবালী ব্যাংক', 'অন্যান্য',
            ],
            self::TYPE_MOBILE_BANKING => [
                'বিকাশ', 'নগদ', 'রকেট', 'উপায়', 'শিওরক্যাশ', 'ট্যাপ', 'অন্যান্য',
            ],
            self::TYPE_SIM => [
                'গ্রামীণফোন', 'রবি', 'এয়ারটেল', 'বাংলালিংক', 'টেলিটক', 'স্কিটো', 'অন্যান্য',
            ],
            default => ['অন্যান্য'],
        };
    }

    /** The single cash-register account of a business (created if missing). */
    public static function ensureRegister(Business $business): self
    {
        return static::firstOrCreate(
            ['business_id' => $business->id, 'type' => self::TYPE_CASH_REGISTER],
            ['label' => 'ক্যাশ রেজিস্টার', 'balance' => 0]
        );
    }

    public function isRegister(): bool
    {
        return $this->type === self::TYPE_CASH_REGISTER;
    }

    /**
     * Balance to show: the register always shows the derived cash
     * (never the stored 0); other accounts show their entered balance.
     */
    public function displayBalance(Business $business): float
    {
        return $this->isRegister()
            ? Hisab::cashBalance($business)
            : (float) $this->balance;
    }
}
