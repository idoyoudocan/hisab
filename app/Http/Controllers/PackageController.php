<?php

namespace App\Http\Controllers;

use App\Models\PlanRequest;
use App\Support\Hisab;
use App\Support\Plans;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PackageController extends Controller
{
    /** Public packages page — works logged-out and logged-in. */
    public function index(): View
    {
        $business = Hisab::business();
        $pending = $business
            ? $business->planRequests()->where('status', PlanRequest::STATUS_PENDING)->latest()->first()
            : null;

        return view('packages.index', [
            'plans' => Plans::all(),
            'pending' => $pending,
        ]);
    }

    /** Confirm step before requesting a plan (login required). */
    public function choose(string $plan): View
    {
        $plan = Plans::findOrFail($plan);
        $business = Hisab::requireBusiness();
        $pending = $business->planRequests()->where('status', PlanRequest::STATUS_PENDING)->latest()->first();

        return view('packages.choose', [
            'plan' => $plan,
            'pending' => $pending,
        ]);
    }

    /** Send a plan request. One pending request per business at a time. */
    public function request(string $plan): RedirectResponse
    {
        $plan = Plans::findOrFail($plan);
        $business = Hisab::requireBusiness();

        $existing = $business->planRequests()->where('status', PlanRequest::STATUS_PENDING)->latest()->first();
        if ($existing !== null) {
            return redirect()->route('packages.index')
                ->with('status', 'আপনার '.$existing->planName().' প্ল্যানের অনুরোধটি ইতিমধ্যেই পাঠানো আছে — পেমেন্টের পর অ্যাডমিন আপনার মেয়াদ বাড়িয়ে দেবেন।');
        }

        $business->planRequests()->create([
            'plan_slug' => $plan['slug'],
            'amount' => $plan['payable'],
            'status' => PlanRequest::STATUS_PENDING,
        ]);

        return redirect()->route('packages.index')
            ->with('status', 'অনুরোধ পাঠানো হয়েছে — পেমেন্টের পর অ্যাডমিন আপনার মেয়াদ বাড়িয়ে দেবেন।');
    }
}
