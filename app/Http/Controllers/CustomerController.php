<?php

namespace App\Http\Controllers;

use App\Support\Hisab;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(Request $request): View
    {
        $business = Hisab::requireBusiness();
        $query = $business->customers()->orderBy('name');

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")->orWhere('phone', 'like', "%{$q}%");
            });
        }

        $customers = $query->paginate(20)->withQueryString();

        return view('customers.index', compact('customers', 'business'));
    }

    public function show(int $id): View
    {
        $business = Hisab::requireBusiness();
        $customer = $business->customers()->findOrFail($id);
        $sales = $customer->sales()->latest('sale_date')->latest('id')->paginate(15, ['*'], 'sales_page');
        $collections = $customer->payments()->latest('date')->latest('id')->paginate(15, ['*'], 'pay_page');

        return view('customers.show', compact('customer', 'sales', 'collections', 'business'));
    }
}
