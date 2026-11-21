<?php

namespace App\Http\Controllers;

use App\Support\Hisab;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashbookController extends Controller
{
    public function index(Request $request): View
    {
        $business = Hisab::requireBusiness();
        $from = $request->string('from')->toString() ?: now()->startOfMonth()->toDateString();
        $to = $request->string('to')->toString() ?: now()->toDateString();

        $entries = collect();

        foreach ($business->sales()->where('paid', '>', 0)->whereBetween('sale_date', [$from, $to])->get() as $s) {
            $entries->push(['date' => $s->sale_date->toDateString(), 'label' => 'বিক্রি (নগদ পাওয়া)', 'ref' => 'বিক্রি #'.$s->id, 'in' => (float) $s->paid, 'out' => 0.0]);
        }
        foreach ($business->payments()->where('direction', 'in')->whereBetween('date', [$from, $to])->get() as $p) {
            $entries->push(['date' => $p->date->toDateString(), 'label' => 'বাকি আদায়', 'ref' => 'আদায় #'.$p->id, 'in' => (float) $p->amount, 'out' => 0.0]);
        }
        foreach ($business->transactions()->where('type', 'income')->whereBetween('date', [$from, $to])->get() as $t) {
            $entries->push(['date' => $t->date->toDateString(), 'label' => 'আয় — '.$t->category, 'ref' => 'আয় #'.$t->id, 'in' => (float) $t->amount, 'out' => 0.0]);
        }
        foreach ($business->purchases()->where('paid', '>', 0)->whereBetween('purchase_date', [$from, $to])->get() as $p) {
            $entries->push(['date' => $p->purchase_date->toDateString(), 'label' => 'কেনাকাটা (পরিশোধ)', 'ref' => 'কেনা #'.$p->id, 'in' => 0.0, 'out' => (float) $p->paid]);
        }
        foreach ($business->payments()->where('direction', 'out')->whereBetween('date', [$from, $to])->get() as $p) {
            $entries->push(['date' => $p->date->toDateString(), 'label' => 'সাপ্লায়ারকে পরিশোধ', 'ref' => 'পরিশোধ #'.$p->id, 'in' => 0.0, 'out' => (float) $p->amount]);
        }
        foreach ($business->transactions()->where('type', 'expense')->whereBetween('date', [$from, $to])->get() as $t) {
            $entries->push(['date' => $t->date->toDateString(), 'label' => 'খরচ — '.$t->category, 'ref' => 'খরচ #'.$t->id, 'in' => 0.0, 'out' => (float) $t->amount]);
        }

        // Opening balance before $from, so the running balance is correct.
        $opening = $this->balanceBefore($business, $from);

        $sorted = $entries->sortBy([['date', 'asc']])->values();
        $running = $opening;
        $rows = $sorted->map(function (array $e) use (&$running) {
            $running = round($running + $e['in'] - $e['out'], 2);
            $e['balance'] = $running;

            return $e;
        });

        $closing = $rows->last()['balance'] ?? $opening;

        return view('cashbook.index', compact('business', 'from', 'to', 'rows', 'opening', 'closing'));
    }

    /** Cash balance just before $from (opening cash + all movements before that date). */
    private function balanceBefore($business, string $from): float
    {
        $bid = $business->id;
        $balance = (float) $business->opening_cash;
        $balance += (float) $business->sales()->where('sale_date', '<', $from)->sum('paid');
        $balance += (float) $business->payments()->where('direction', 'in')->where('date', '<', $from)->sum('amount');
        $balance += (float) $business->transactions()->where('type', 'income')->where('date', '<', $from)->sum('amount');
        $balance -= (float) $business->purchases()->where('purchase_date', '<', $from)->sum('paid');
        $balance -= (float) $business->payments()->where('direction', 'out')->where('date', '<', $from)->sum('amount');
        $balance -= (float) $business->transactions()->where('type', 'expense')->where('date', '<', $from)->sum('amount');

        return round($balance, 2);
    }
}
