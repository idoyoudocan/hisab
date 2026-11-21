<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Support\Hisab;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
            'owner_name' => ['nullable', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['nullable', 'string', Rule::in(Business::TYPES)],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ]);

        // Module toggles: unchecked boxes send nothing, so build the full set.
        // When the form sends no modules at all, keep the existing settings.
        $settings = $business->settings ?? [];
        if ($request->has('modules')) {
            $checked = $request->input('modules', []);
            foreach (array_keys(Business::MODULES) as $key) {
                $settings[$key] = isset($checked[$key]) && $checked[$key] === '1';
            }
        }

        $wasRecharge = $business->type === Business::TYPE_RECHARGE;
        $business->update([
            'name' => $data['name'],
            'type' => $data['type'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'opening_cash' => $data['opening_cash'],
            'settings' => $settings,
        ]);
        Auth::user()->update(array_filter(['name' => $data['owner_name'] ?? null]));
        if (! $wasRecharge && $business->type === Business::TYPE_RECHARGE) {
            Business::seedRechargeCategories($business);
        }

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
