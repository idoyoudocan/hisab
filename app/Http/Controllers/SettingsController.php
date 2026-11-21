<?php

namespace App\Http\Controllers;

use App\Support\Hisab;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        $business = Hisab::requireBusiness();

        return view('settings.index', compact('business'));
    }

    public function update(Request $request): RedirectResponse
    {
        $business = Hisab::requireBusiness();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);
        $business->update($data);

        return back()->with('status', 'সেটিংস সংরক্ষিত হয়েছে।');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Auth::user()->update(['password' => Hash::make($request->string('password'))]);

        return back()->with('status', 'পাসওয়ার্ড বদলে গেছে।');
    }
}
