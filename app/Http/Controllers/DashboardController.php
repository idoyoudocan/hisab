<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Support\Hisab;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $business = Hisab::requireBusiness();
        $today = now()->toDateString();

        $todaySales = $business->sales()
            ->whereDate('sale_date', $today)
            ->with('items.product')
            ->get();
        $todaySaleTotal = (float) $todaySales->sum('total');
        $todayProfit = round($todaySales->sum(fn (Sale $s) => $s->profitAmount()), 2);
        $todayExpense = (float) $business->transactions()
            ->where('type', 'expense')
            ->whereDate('date', $today)
            ->sum('amount');
        $todayPurchase = (float) $business->purchases()
            ->whereDate('purchase_date', $today)
            ->sum('total');

        $cash = Hisab::cashBreakdown($business);
        $customerDue = Hisab::totalCustomerDue($business);
        $supplierDue = Hisab::totalSupplierDue($business);
        $lowStock = $business->products()
            ->whereColumn('stock', '<=', 'low_stock_at')
            ->orderBy('stock')
            ->take(8)
            ->get();
        $recentSales = $business->sales()
            ->with('customer')
            ->latest('sale_date')->latest('id')
            ->take(8)
            ->get();

        // One-line insight for today.
        $todayCollections = (float) $business->payments()
            ->where('direction', 'in')
            ->whereDate('date', $today)
            ->sum('amount');
        $topProduct = $todaySales->flatMap->items
            ->groupBy('product_id')
            ->map(fn ($items) => ['qty' => (float) $items->sum('qty'), 'name' => $items->first()->product->name ?? ''])
            ->sortByDesc('qty')
            ->first();
        $biggestDueCustomer = $business->customers()->get()
            ->map(fn ($c) => ['name' => $c->name, 'due' => $c->dueAmount()])
            ->filter(fn ($c) => $c['due'] > 0)
            ->sortByDesc('due')
            ->first();
        $insight = 'আজ '.Hisab::money($todaySaleTotal).' বিক্রি, '.Hisab::money($todayCollections).' আদায়'
            .($topProduct ? ', সবচেয়ে বেশি বিক্রি '.$topProduct['name'].' ('.(float) $topProduct['qty'].'টি)' : '')
            .($biggestDueCustomer ? '। সবচেয়ে বেশি বাকি '.$biggestDueCustomer['name'].'-এর — '.Hisab::money($biggestDueCustomer['due']) : '');

        // First-run checklist: disappears once all three are done.
        $checklist = [
            'products' => $business->products()->exists(),
            'sales' => $business->sales()->exists(),
            'cashCount' => $business->cashCounts()->exists(),
        ];
        $checklistDone = $checklist['products'] && $checklist['sales'] && $checklist['cashCount'];

        return view('dashboard', compact(
            'business', 'todaySales', 'todaySaleTotal', 'todayProfit',
            'todayExpense', 'todayPurchase', 'cash', 'customerDue',
            'supplierDue', 'lowStock', 'recentSales', 'todayCollections',
            'insight', 'checklist', 'checklistDone'
        ));
    }
}
