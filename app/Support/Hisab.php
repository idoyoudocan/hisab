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

    /** Cash balance just before a date (opening cash + movements before it). */
    public static function cashBalanceBefore(Business $business, string $date): float
    {
        $bid = $business->id;
        $balance = (float) $business->opening_cash;
        $balance += (float) Sale::where('business_id', $bid)->where('sale_date', '<', $date)->sum('paid');
        $balance += (float) Payment::where('business_id', $bid)->where('direction', 'in')->where('date', '<', $date)->sum('amount');
        $balance += (float) Transaction::where('business_id', $bid)->where('type', 'income')->where('date', '<', $date)->sum('amount');
        $balance -= (float) Purchase::where('business_id', $bid)->where('purchase_date', '<', $date)->sum('paid');
        $balance -= (float) Payment::where('business_id', $bid)->where('direction', 'out')->where('date', '<', $date)->sum('amount');
        $balance -= (float) Transaction::where('business_id', $bid)->where('type', 'expense')->where('date', '<', $date)->sum('amount');

        return round($balance, 2);
    }

    /** Normalize a Bangladeshi phone number to wa.me format (8801XXXXXXXXX). */
    public static function whatsappNumber(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }
        if (str_starts_with($digits, '880')) {
            return $digits;
        }
        if (str_starts_with($digits, '01')) {
            return '880'.substr($digits, 1);
        }
        if (str_starts_with($digits, '1') && strlen($digits) === 10) {
            return '880'.$digits;
        }

        return $digits;
    }

    /** wa.me link with a prefilled message, or null when no usable number. */
    public static function whatsappUrl(?string $phone, string $message): ?string
    {
        $number = self::whatsappNumber($phone);

        return $number
            ? 'https://wa.me/'.$number.'?text='.rawurlencode($message)
            : null;
    }

    /** Polite Bangla due-reminder text for a customer. */
    public static function dueReminderText(Business $business, string $customerName, float $due): string
    {
        return 'আসসালামু আলাইকুম '.$customerName.', '.$business->name.' থেকে বলছি। আপনার বাকি '.self::money($due).' রয়েছে। অনুগ্রহ করে পরিশোধ করবেন। ধন্যবাদ।';
    }

    /** Shareable receipt text for a sale (WhatsApp, no fixed recipient). */
    public static function receiptShareUrl(Sale $sale, Business $business): string
    {
        $lines = [
            $business->name.' — রসিদ #'.$sale->id,
            'তারিখ: '.$sale->sale_date->format('d M Y'),
            'কাস্টমার: '.($sale->customer?->name ?? 'খুচরা কাস্টমার'),
        ];
        foreach ($sale->items as $item) {
            $lines[] = $item->product->name.' × '.(float) $item->qty.' — '.self::money($item->total);
        }
        if ((float) $sale->discount_amount > 0) {
            $lines[] = 'ছাড়: − '.self::money($sale->discount_amount);
        }
        $lines[] = 'মোট: '.self::money($sale->total);
        $lines[] = 'পরিশোধ: '.self::money($sale->paid);
        $lines[] = 'বাকি: '.self::money($sale->dueAmount());

        return 'https://wa.me/?text='.rawurlencode(implode("\n", $lines));
    }

    public static function units(): array
    {
        return ['পিস', 'কেজি', 'গ্রাম', 'লিটার', 'প্যাকেট', 'বস্তা', 'ডজন'];
    }
}
