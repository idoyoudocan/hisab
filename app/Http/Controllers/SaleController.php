<?php

namespace App\Http\Controllers;

use App\Support\Hisab;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(Request $request): View
    {
        $business = Hisab::requireBusiness();
        $query = $business->sales()->with('customer')->latest('sale_date')->latest('id');

        if ($request->filled('from')) {
            $query->whereDate('sale_date', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('sale_date', '<=', $request->string('to'));
        }

        $sales = $query->paginate(20)->withQueryString();

        return view('sales.index', compact('sales', 'business'));
    }

    public function create(): View
    {
        $business = Hisab::requireBusiness();

        return view('sales.create', compact('business'));
    }

    public function show(int $id): View
    {
        $business = Hisab::requireBusiness();
        $sale = $business->sales()->with(['items.product', 'customer'])->findOrFail($id);

        return view('sales.show', compact('sale', 'business'));
    }
}
