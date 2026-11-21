<?php

namespace App\Http\Controllers;

use App\Support\Hisab;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $business = Hisab::requireBusiness();
        $query = $business->products()->orderBy('name');

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%");
            });
        }
        if ($request->boolean('low')) {
            $query->whereColumn('stock', '<=', 'low_stock_at');
        }

        $products = $query->paginate(20)->withQueryString();

        return view('products.index', compact('products', 'business'));
    }
}
