<?php

namespace App\Http\Controllers;

use App\Support\Hisab;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function index(Request $request): View
    {
        $business = Hisab::requireBusiness();
        $query = $business->purchases()->with('supplier')->latest('purchase_date')->latest('id');

        if ($request->filled('from')) {
            $query->whereDate('purchase_date', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('purchase_date', '<=', $request->string('to'));
        }

        $purchases = $query->paginate(20)->withQueryString();

        return view('purchases.index', compact('purchases', 'business'));
    }

    public function create(): View
    {
        $business = Hisab::requireBusiness();

        return view('purchases.create', compact('business'));
    }

    public function show(int $id): View
    {
        $business = Hisab::requireBusiness();
        $purchase = $business->purchases()->with(['items.product', 'supplier'])->findOrFail($id);

        return view('purchases.show', compact('purchase', 'business'));
    }
}
