<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Support\Hisab;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Monthly profit & loss statement.
 * Revenue = sales total after discount. COGS = purchase cost of the
 * items sold that month (current purchase prices, same basis as the
 * per-sale profit figure). Net business profit excludes personal
 * expenses — those are shown separately as the owner's drawings.
 */
class ProfitLossController extends Controller
{
    public function index(Request $request): View
    {
        $business = Hisab::requireBusiness();
        $month = $request->string('month', now()->format('Y-m'))->toString();
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = now()->format('Y-m');
        }

        $sales = $business->sales()
            ->where('sale_date', 'like', $month.'%')
            ->with('items.product')
            ->get();
        $revenue = (float) $sales->sum('total');
        $discounts = (float) $sales->sum('discount_amount');
        $cogs = round($sales->sum(fn (Sale $s) => $s->cogsAmount()), 2);
        $grossProfit = round($revenue - $cogs, 2);

        $otherIncome = (float) $business->transactions()->where('type', 'income')
            ->where('date', 'like', $month.'%')->sum('amount');
        $shopExpense = (float) $business->transactions()->where('type', 'expense')
            ->where('scope', 'shop')->where('date', 'like', $month.'%')->sum('amount');
        $personalExpense = (float) $business->transactions()->where('type', 'expense')
            ->where('scope', 'personal')->where('date', 'like', $month.'%')->sum('amount');

        $netProfit = round($grossProfit + $otherIncome - $shopExpense, 2);
        $margin = $revenue > 0 ? round($grossProfit / $revenue * 100, 1) : 0.0;

        return view('profit-loss.index', compact(
            'business', 'month', 'revenue', 'discounts', 'cogs', 'grossProfit',
            'otherIncome', 'shopExpense', 'personalExpense', 'netProfit', 'margin'
        ));
    }
}
