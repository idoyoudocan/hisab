<?php

namespace App\Http\Controllers;

use App\Support\Hisab;
use Illuminate\View\View;

class CapitalController extends Controller
{
    public function index(): View
    {
        $business = Hisab::requireBusiness();
        $capital = Hisab::capitalBreakdown($business);

        return view('capital.index', compact('business', 'capital'));
    }
}
