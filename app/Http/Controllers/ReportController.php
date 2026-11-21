<?php

namespace App\Http\Controllers;

use App\Support\Hisab;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $business = Hisab::requireBusiness();
        $from = $request->string('from')->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->toDateString();

        $sales = $business->sales()->whereBetween('sale_date', [$from, $to])->with('items.product')->get();
        $salesTotal = (float) $sales->sum('total');
        $salesPaid = (float) $sales->sum('paid');
        $salesDue = round($salesTotal - $salesPaid, 2);
        $profit = round($sales->sum(fn ($s) => $s->profitAmount()), 2);

        $purchaseTotal = (float) $business->purchases()->whereBetween('purchase_date', [$from, $to])->sum('total');
        $income = (float) $business->transactions()->where('type', 'income')->whereBetween('date', [$from, $to])->sum('amount');
        $expense = (float) $business->transactions()->where('type', 'expense')->whereBetween('date', [$from, $to])->sum('amount');
        $shopExpense = (float) $business->transactions()->where('type', 'expense')->where('scope', 'shop')->whereBetween('date', [$from, $to])->sum('amount');
        $personalExpense = (float) $business->transactions()->where('type', 'expense')->where('scope', 'personal')->whereBetween('date', [$from, $to])->sum('amount');
        $collections = (float) $business->payments()->where('direction', 'in')->whereBetween('date', [$from, $to])->sum('amount');
        $supplierPaid = (float) $business->payments()->where('direction', 'out')->whereBetween('date', [$from, $to])->sum('amount');

        // Daily breakdown for the period (up to 31 days shown).
        $daily = [];
        $cursor = \Carbon\Carbon::parse($from);
        $end = \Carbon\Carbon::parse($to);
        while ($cursor->lte($end)) {
            $d = $cursor->toDateString();
            $daily[] = [
                'date' => $d,
                'sales' => (float) $sales->where('sale_date', $d)->sum('total'),
                'purchases' => (float) $business->purchases()->whereDate('purchase_date', $d)->sum('total'),
                'expense' => (float) $business->transactions()->where('type', 'expense')->whereDate('date', $d)->sum('amount'),
            ];
            $cursor->addDay();
        }

        return view('reports.index', compact(
            'business', 'from', 'to', 'salesTotal', 'salesPaid', 'salesDue',
            'profit', 'purchaseTotal', 'income', 'expense', 'shopExpense',
            'personalExpense', 'collections', 'supplierPaid', 'daily'
        ));
    }
}
