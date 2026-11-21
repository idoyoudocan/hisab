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

        $sort = $request->string('sort', 'name')->toString();
        $customers = $query->paginate(20)->withQueryString();

        // Due aging: oldest unpaid sale per customer (computed once per page).
        $aging = [];
        foreach ($customers as $c) {
            $aging[$c->id] = $c->oldestDueDays();
        }
        if ($sort === 'age') {
            $sorted = $customers->getCollection()->sortByDesc(fn ($c) => $aging[$c->id] ?? -1)->values();
            $customers->setCollection($sorted);
        } elseif ($sort === 'due') {
            $sorted = $customers->getCollection()->sortByDesc(fn ($c) => $c->dueAmount())->values();
            $customers->setCollection($sorted);
        }

        return view('customers.index', compact('customers', 'business', 'aging', 'sort'));
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
