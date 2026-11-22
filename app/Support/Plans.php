<?php

namespace App\Support;

use App\Models\SiteSetting;

/**
 * The four subscription packages — the SINGLE source of truth for pricing.
 * Every page (packages page, admin extension, tests) reads from here;
 * no price is hardcoded anywhere else.
 *
 * Built-in defaults (regular price / discount) can be overridden by the
 * owner from /admin/settings, stored in site_settings as
 * plan.{slug}.regular and plan.{slug}.discount. Overrides take effect
 * immediately with no code deploy.
 *
 * All plans include ALL features; only the duration and the discount differ.
 * Longer packages always cost less per month (bigger discount).
 */
class Plans
{
    /**
     * @return array<int, array{slug: string, name: string, months: int, regular: int, discount: int, payable: int, per_month: int, savings: int, badge: ?string}>
     */
    public static function all(): array
    {
        return [
            self::make('basic', 'বেসিক', 1, 300, 50, null),
            self::make('standard', 'স্ট্যান্ডার্ড', 3, 900, 60, 'সবচেয়ে জনপ্রিয়'),
            self::make('premium', 'প্রিমিয়াম', 6, 1800, 65, null),
            self::make('business', 'বিজনেস', 12, 3600, 70, 'সবচেয়ে লাভজনক'),
        ];
    }

    private static function make(string $slug, string $name, int $months, int $regular, int $discount, ?string $badge): array
    {
        // Admin overrides from /admin/settings (site_settings), if set.
        $overrideRegular = SiteSetting::get("plan.{$slug}.regular");
        if ($overrideRegular !== null && $overrideRegular !== '' && (int) $overrideRegular >= 0) {
            $regular = (int) $overrideRegular;
        }
        $overrideDiscount = SiteSetting::get("plan.{$slug}.discount");
        if ($overrideDiscount !== null && $overrideDiscount !== '' && (int) $overrideDiscount >= 0 && (int) $overrideDiscount <= 90) {
            $discount = (int) $overrideDiscount;
        }

        $payable = (int) round($regular * (100 - $discount) / 100);

        return [
            'slug' => $slug,
            'name' => $name,
            'months' => $months,
            'regular' => $regular,
            'discount' => $discount,
            'payable' => $payable,
            'per_month' => (int) round($payable / $months),
            'savings' => $regular - $payable,
            'badge' => $badge,
        ];
    }

    public static function find(string $slug): ?array
    {
        foreach (self::all() as $plan) {
            if ($plan['slug'] === $slug) {
                return $plan;
            }
        }

        return null;
    }

    public static function findOrFail(string $slug): array
    {
        return self::find($slug) ?? abort(404, 'এই প্যাকেজটি নেই।');
    }

    /** The plan whose duration is N months, e.g. 3 → স্ট্যান্ডার্ড. */
    public static function forMonths(int $months): ?array
    {
        foreach (self::all() as $plan) {
            if ($plan['months'] === $months) {
                return $plan;
            }
        }

        return null;
    }
}
