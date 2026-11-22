<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Site-wide key/value settings the owner edits from /admin/settings.
 * Examples: payment_bkash_number, payment_whatsapp_number,
 * payment_methods (comma-separated: bkash,nagad,rocket),
 * plan.{slug}.regular and plan.{slug}.discount (plan price overrides).
 *
 * Reads tolerate a missing table (e.g. before the migration runs) and
 * simply fall back to the caller's default.
 */
class SiteSetting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        try {
            if (! Schema::hasTable('site_settings')) {
                return $default;
            }

            return static::where('key', $key)->value('value') ?? $default;
        } catch (\Throwable) {
            return $default;
        }
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Payment number for bKash Personal (Send Money). */
    public static function paymentBkashNumber(): string
    {
        return (string) (static::get('payment_bkash_number', '') ?? '');
    }

    /** WhatsApp number for the payment screenshot. */
    public static function paymentWhatsappNumber(): string
    {
        return (string) (static::get('payment_whatsapp_number', '') ?? '');
    }

    /** Accepted payment methods as slugs, e.g. ['bkash','nagad','rocket']. */
    public static function paymentMethods(): array
    {
        $raw = (string) (static::get('payment_methods', 'bkash') ?? 'bkash');

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /** Bangla display names for the payment method slugs. */
    public static function paymentMethodNames(): array
    {
        return [
            'bkash' => 'বিকাশ',
            'nagad' => 'নগদ',
            'rocket' => 'রকেট',
        ];
    }
}
