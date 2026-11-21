<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\SubscriptionPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    private const MONTHLY_PRICE = 300;

    public function dashboard(): View
    {
        $businesses = Business::with('owner')->latest()->get();
        $now = now();

        $statusOf = fn (Business $b) => $b->subscriptionStatus();
        $paid = $businesses->filter(fn (Business $b) => $statusOf($b) === 'paid');
        $trial = $businesses->filter(fn (Business $b) => $statusOf($b) === 'trial');
        $expired = $businesses->filter(fn (Business $b) => $statusOf($b) === 'expired');

        $expiring = $businesses->filter(function (Business $b) use ($now) {
            $end = $b->subscriptionStatus() === 'paid'
                ? $b->paid_until
                : $b->trial_ends_at;
            return $end !== null && $end->isFuture() && $end->lte($now->copy()->addDays(7));
        })->sortBy(fn (Business $b) => $b->subscriptionStatus() === 'paid' ? $b->paid_until : $b->trial_ends_at);

        return view('admin.dashboard', [
            'total' => $businesses->count(),
            'paidCount' => $paid->count(),
            'trialCount' => $trial->count(),
            'expiredCount' => $expired->count(),
            'newThisMonth' => $businesses->filter(fn (Business $b) => $b->created_at->isCurrentMonth())->count(),
            'monthlyRevenue' => $paid->count() * self::MONTHLY_PRICE,
            'expiring' => $expiring->take(10),
            'recent' => $businesses->take(10),
        ]);
    }

    public function businesses(Request $request): View
    {
        $query = Business::with('owner')->latest();
        if ($request->filled('q')) {
            $q = '%'.$request->string('q').'%';
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', $q)
                    ->orWhere('phone', 'like', $q)
                    ->orWhereHas('owner', fn ($o) => $o->where('name', 'like', $q));
            });
        }
        $businesses = $query->get();

        $filter = $request->input('status', 'all');
        if (in_array($filter, ['trial', 'paid', 'expired'], true)) {
            $businesses = $businesses->filter(fn (Business $b) => $b->subscriptionStatus() === $filter)->values();
        }

        return view('admin.businesses', [
            'businesses' => $businesses->take(200),
            'q' => (string) $request->input('q', ''),
            'status' => $filter,
        ]);
    }

    public function show(Business $business): View
    {
        $business->load('owner');
        $payments = $business->subscriptionPayments()->latest('paid_at')->latest('id')->take(20)->get();

        return view('admin.business-show', [
            'business' => $business,
            'payments' => $payments,
            'salesCount' => $business->sales()->count(),
            'salesTotal' => (float) $business->sales()->sum('total'),
            'productCount' => $business->products()->count(),
            'customerCount' => $business->customers()->count(),
        ]);
    }

    /** Extend a subscription by N months and record the payment (৳300/month). */
    public function extend(Request $request, Business $business): RedirectResponse
    {
        $data = $request->validate([
            'months' => ['required', 'integer', 'in:1,3,6,12'],
            'method' => ['required', 'string', 'in:cash,bkash,nagad,bank'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->applyExtension($business, (int) $data['months'], self::MONTHLY_PRICE * (int) $data['months'], $data['method'], $data['note'] ?? null);

        return back()->with('status', 'মেয়াদ '.(int) $data['months'].' মাস বাড়ানো হয়েছে — পেইড পর্যন্ত '.$business->fresh()->paid_until->format('d M Y').'।');
    }

    /** Record a payment; extends the subscription when months > 0. */
    public function recordPayment(Request $request, Business $business): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'months' => ['required', 'integer', 'min:0', 'max:12'],
            'method' => ['required', 'string', 'in:cash,bkash,nagad,bank'],
            'paid_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->applyExtension($business, (int) $data['months'], (float) $data['amount'], $data['method'], $data['note'] ?? null, $data['paid_at']);

        return back()->with('status', 'পেমেন্ট সংরক্ষিত হয়েছে।');
    }

    private function applyExtension(Business $business, int $months, float $amount, string $method, ?string $note, ?string $paidAt = null): void
    {
        $base = ($business->paid_until !== null && $business->paid_until->isFuture())
            ? $business->paid_until->copy()
            : now()->toDateString();

        $business->subscriptionPayments()->create([
            'amount' => $amount,
            'method' => $method,
            'months' => $months,
            'paid_at' => $paidAt ?? now()->toDateString(),
            'note' => $note,
            'recorded_by' => auth()->user()?->email,
        ]);

        if ($months > 0) {
            $business->update([
                'paid_until' => \Carbon\Carbon::parse($base)->addMonths($months)->toDateString(),
                'plan' => 'pro',
            ]);
        }
    }
}
