<?php

namespace App\Http\Controllers;

use App\Support\Hisab;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $business = Hisab::requireBusiness();
        $month = $request->string('month', now()->format('Y-m'))->toString();

        $query = $business->transactions()->latest('date')->latest('id');
        if ($request->filled('month')) {
            $query->where('date', 'like', $month.'%');
        }
        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }
        if ($request->filled('scope')) {
            $query->where('scope', $request->string('scope'));
        }

        $transactions = $query->paginate(20)->withQueryString();

        $monthIncome = (float) $business->transactions()
            ->where('type', 'income')->where('date', 'like', $month.'%')->sum('amount');
        $monthExpense = (float) $business->transactions()
            ->where('type', 'expense')->where('date', 'like', $month.'%')->sum('amount');
        $personalExpense = (float) $business->transactions()
            ->where('type', 'expense')->where('scope', 'personal')
            ->where('date', 'like', $month.'%')->sum('amount');

        return view('transactions.index', compact(
            'transactions', 'business', 'month', 'monthIncome', 'monthExpense', 'personalExpense'
        ));
    }
}
