<?php

namespace App\Support;

use App\Models\Business;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

/**
 * Hisab helpers: current business, money formatting and the derived
 * cash-in-hand figure. Cash is NEVER stored as a single number; it is
 * always computed as:
 *   opening_cash + sales paid + customer collections + income
 *   − purchases paid − supplier payments − expenses
 */
class Hisab
{
    public static function business(): ?Business
    {
        $user = Auth::user();

        return $user ? $user->business : null;
    }

    public static function requireBusiness(): Business
    {
        return self::business()
            ?? throw new \RuntimeException('No business found for the current user.');
    }

    /** Format a Taka amount, e.g. ৳১,২৫০ — uses Western digits for clarity. */
    public static function money(float|int|string $amount): string
    {
        return '৳'.number_format((float) $amount, 0, '.', ',');
    }

    public static function money2(float|int|string $amount): string
    {
        return '৳'.number_format((float) $amount, 2, '.', ',');
    }

    /** Breakdown of every cash movement for a business. */
    public static function cashBreakdown(Business $business): array
    {
        $bid = $business->id;

        $salesPaid = (float) Sale::where('business_id', $bid)->sum('paid');
        $collections = (float) Payment::where('business_id', $bid)
            ->where('direction', 'in')->sum('amount');
        $income = (float) Transaction::where('business_id', $bid)
            ->where('type', 'income')->sum('amount');
        $purchasesPaid = (float) Purchase::where('business_id', $bid)->sum('paid');
        $supplierPaid = (float) Payment::where('business_id', $bid)
            ->where('direction', 'out')->sum('amount');
        $expenses = (float) Transaction::where('business_id', $bid)
            ->where('type', 'expense')->sum('amount');

        $balance = (float) $business->opening_cash
            + $salesPaid + $collections + $income
            - $purchasesPaid - $supplierPaid - $expenses;

        return [
            'opening_cash' => (float) $business->opening_cash,
            'sales_paid' => $salesPaid,
            'collections' => $collections,
            'income' => $income,
            'purchases_paid' => $purchasesPaid,
            'supplier_payments' => $supplierPaid,
            'expenses' => $expenses,
            'balance' => round($balance, 2),
        ];
    }

    public static function cashBalance(Business $business): float
    {
        return self::cashBreakdown($business)['balance'];
    }

    /** Total due from all customers of the business. */
    public static function totalCustomerDue(Business $business): float
    {
        $sales = Sale::where('business_id', $business->id)
            ->selectRaw('COALESCE(SUM(total),0) as t, COALESCE(SUM(paid),0) as p')->first();
        $collected = (float) Payment::where('business_id', $business->id)
            ->where('direction', 'in')->sum('amount');

        return round(((float) $sales->t - (float) $sales->p) - $collected, 2);
    }

    /** Total due to all suppliers of the business. */
    public static function totalSupplierDue(Business $business): float
    {
        $purchases = Purchase::where('business_id', $business->id)
            ->selectRaw('COALESCE(SUM(total),0) as t, COALESCE(SUM(paid),0) as p')->first();
        $paidOut = (float) Payment::where('business_id', $business->id)
            ->where('direction', 'out')->sum('amount');

        return round(((float) $purchases->t - (float) $purchases->p) - $paidOut, 2);
    }

    public static function paymentMethods(): array
    {
        return ['cash' => 'নগদ', 'bkash' => 'বিকাশ', 'nagad' => 'নগদ (নগদ)', 'bank' => 'ব্যাংক'];
    }

    public static function units(): array
    {
        return ['পিস', 'কেজি', 'গ্রাম', 'লিটার', 'প্যাকেট', 'বস্তা', 'ডজন'];
    }
}
