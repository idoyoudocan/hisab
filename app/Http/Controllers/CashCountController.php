<?php

namespace App\Http\Controllers;

use App\Support\Hisab;
use Illuminate\View\View;

class CashCountController extends Controller
{
    public function index(): View
    {
        $business = Hisab::requireBusiness();
        $systemCash = Hisab::cashBalance($business);

        return view('cash-count.index', compact('business', 'systemCash'));
    }
}
