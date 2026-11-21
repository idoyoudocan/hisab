<?php

namespace App\Http\Controllers;

use App\Models\MoneyAccount;
use App\Support\Hisab;
use Illuminate\View\View;

class MoneyAccountController extends Controller
{
    public function index(): View
    {
        $business = Hisab::requireBusiness();
        MoneyAccount::ensureRegister($business);

        $accounts = $business->moneyAccounts()->orderBy('type')->orderBy('label')->get();
        $register = $accounts->firstWhere('type', MoneyAccount::TYPE_CASH_REGISTER);
        $others = $accounts->where('type', '!=', MoneyAccount::TYPE_CASH_REGISTER);
        $totals = [];
        foreach (MoneyAccount::types() as $type => $label) {
            if ($type === MoneyAccount::TYPE_CASH_REGISTER) {
                continue;
            }
            $totals[$type] = Hisab::accountTotal($business, $type);
        }
        $grandTotal = Hisab::cashBalance($business) + array_sum($totals);

        return view('money.index', compact('business', 'accounts', 'register', 'others', 'totals', 'grandTotal'));
    }
}
