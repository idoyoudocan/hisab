<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\Plans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Owner settings (/admin/settings): payment number/methods and per-plan
 * price/discount overrides. Changes take effect immediately — the
 * packages page, plan-request approval and manual extension all read
 * prices through App\Support\Plans, which honours these overrides.
 */
class SiteSettingsController extends Controller
{
    public function index(): View
    {
        $plans = Plans::all();

        // Current effective values per plan (override or built-in default),
        // so the form always shows what the packages page actually charges.
        $planValues = [];
        foreach ($plans as $plan) {
            $planValues[$plan['slug']] = [
                'regular' => $plan['regular'],
                'discount' => $plan['discount'],
                'payable' => $plan['payable'],
                'per_month' => $plan['per_month'],
            ];
        }

        return view('admin.settings', [
            'bkashNumber' => SiteSetting::paymentBkashNumber(),
            'whatsappNumber' => SiteSetting::paymentWhatsappNumber(),
            'methods' => SiteSetting::paymentMethods(),
            'methodNames' => SiteSetting::paymentMethodNames(),
            'plans' => $plans,
            'planValues' => $planValues,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bkash_number' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'whatsapp_number' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'methods' => ['required', 'array', 'min:1'],
            'methods.*' => ['string', 'in:bkash,nagad,rocket'],
            'plans' => ['required', 'array'],
            'plans.*.regular' => ['required', 'integer', 'min:0', 'max:1000000'],
            'plans.*.discount' => ['required', 'integer', 'min:0', 'max:90'],
        ], [
            'bkash_number.regex' => 'বিকাশ নম্বরটি সঠিক নয় — 01 দিয়ে শুরু ১১ অঙ্কের নম্বর দিন।',
            'whatsapp_number.regex' => 'WhatsApp নম্বরটি সঠিক নয় — 01 দিয়ে শুরু ১১ অঙ্কের নম্বর দিন।',
            'methods.min' => 'কমপক্ষে একটি পেমেন্ট মাধ্যম বেছে নিন।',
            'plans.*.discount.max' => 'ছাড় সর্বোচ্চ ৯০% হতে পারে।',
        ]);

        SiteSetting::set('payment_bkash_number', $data['bkash_number']);
        SiteSetting::set('payment_whatsapp_number', $data['whatsapp_number']);
        SiteSetting::set('payment_methods', implode(',', $data['methods']));

        foreach (Plans::all() as $plan) {
            $slug = $plan['slug'];
            if (! isset($data['plans'][$slug])) {
                continue;
            }
            SiteSetting::set("plan.{$slug}.regular", (string) (int) $data['plans'][$slug]['regular']);
            SiteSetting::set("plan.{$slug}.discount", (string) (int) $data['plans'][$slug]['discount']);
        }

        return back()->with('status', 'সেটিংস সংরক্ষিত হয়েছে — প্যাকেজ পেজে নতুন দাম এখনই দেখা যাবে।');
    }
}
