<?php

namespace App\Http\Controllers;

use App\Support\Hisab;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * দিনের হিসাব (day closing): one day in a single view — opening cash,
 * sales (cash + due split), collections, purchases paid, expenses,
 * closing cash (derived), printable summary.
 */
class DayCloseController extends Controller
{
    public function index(Request $request): View
    {
        $business = Hisab::requireBusiness();
        $date = $request->string('date')->toString() ?: now()->toDateString();

        $opening = Hisab::cashBalanceBefore($business, $date);

        $sales = $business->sales()->whereDate('sale_date', $date)->get();
        $salesTotal = (float) $sales->sum('total');
        $salesPaid = (float) $sales->sum('paid');
        $salesDue = round($salesTotal - $salesPaid, 2);
        $salesCount = $sales->count();

        $collections = (float) $business->payments()->where('direction', 'in')
            ->whereDate('date', $date)->sum('amount');
        $purchasesPaid = (float) $business->purchases()->whereDate('purchase_date', $date)->sum('paid');
        $supplierPaid = (float) $business->payments()->where('direction', 'out')
            ->whereDate('date', $date)->sum('amount');
        $income = (float) $business->transactions()->where('type', 'income')
            ->whereDate('date', $date)->sum('amount');
        $shopExpense = (float) $business->transactions()->where('type', 'expense')
            ->where('scope', 'shop')->whereDate('date', $date)->sum('amount');
        $personalExpense = (float) $business->transactions()->where('type', 'expense')
            ->where('scope', 'personal')->whereDate('date', $date)->sum('amount');
        $expense = round($shopExpense + $personalExpense, 2);

        $cashIn = round($salesPaid + $collections + $income, 2);
        $cashOut = round($purchasesPaid + $supplierPaid + $expense, 2);
        $closing = round($opening + $cashIn - $cashOut, 2);

        return view('day-close.index', compact(
            'business', 'date', 'opening', 'salesTotal', 'salesPaid', 'salesDue',
            'salesCount', 'collections', 'purchasesPaid', 'supplierPaid',
            'income', 'shopExpense', 'personalExpense', 'expense',
            'cashIn', 'cashOut', 'closing'
        ));
    }
}
