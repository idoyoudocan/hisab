<?php

namespace App\Http\Controllers;

use App\Support\Hisab;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function show(int $id): View
    {
        $business = Hisab::requireBusiness();
        $supplier = $business->suppliers()->findOrFail($id);
        $purchases = $supplier->purchases()->latest('purchase_date')->latest('id')->paginate(15, ['*'], 'pur_page');
        $payments = $supplier->payments()->latest('date')->latest('id')->paginate(15, ['*'], 'pay_page');

        return view('suppliers.show', compact('supplier', 'purchases', 'payments', 'business'));
    }
}
