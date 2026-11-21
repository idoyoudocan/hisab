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

        return view('dashboard', compact(
            'business', 'todaySales', 'todaySaleTotal', 'todayProfit',
            'todayExpense', 'todayPurchase', 'cash', 'customerDue',
            'supplierDue', 'lowStock', 'recentSales'
        ));
    }
}
