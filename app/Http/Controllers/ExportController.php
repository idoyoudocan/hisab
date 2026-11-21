<?php

namespace App\Http\Controllers;

use App\Support\Hisab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV exports with a UTF-8 BOM so Excel opens Bangla headers correctly.
 * Every export is scoped to the current business.
 */
class ExportController extends Controller
{
    public function salesCsv(Request $request): StreamedResponse
    {
        $business = Hisab::requireBusiness();
        $query = $business->sales()->with('customer')->latest('sale_date')->latest('id');
        if ($request->filled('from')) {
            $query->whereDate('sale_date', '>=', $request->string('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('sale_date', '<=', $request->string('to'));
        }
        $sales = $query->get();

        $rows = [['তারিখ', 'বিক্রি নং', 'কাস্টমার', 'মোট (ছাড়ের আগে)', 'ছাড়', 'মোট', 'পরিশোধ', 'বাকি']];
        foreach ($sales as $s) {
            $rows[] = [
                $s->sale_date->toDateString(),
                $s->id,
                $s->customer?->name ?? 'খুচরা',
                (float) $s->subtotalAmount(),
                (float) $s->discount_amount,
                (float) $s->total,
                (float) $s->paid,
                $s->dueAmount(),
            ];
        }

        return $this->download('sales-'.now()->format('Y-m-d').'.csv', $rows);
    }

    public function customerLedgerCsv(int $id): StreamedResponse
    {
        $business = Hisab::requireBusiness();
        $customer = $business->customers()->findOrFail($id);

        $rows = [['তারিখ', 'ধরন', 'বিবরণ', 'টাকা', 'চলমান বাকি']];
        $running = 0.0;
        $entries = collect();
        foreach ($customer->sales()->latest('sale_date')->latest('id')->get() as $s) {
            $entries->push(['date' => $s->sale_date->toDateString(), 'type' => 'বিক্রি', 'note' => 'বিক্রি #'.$s->id.' (মোট '.Hisab::money($s->total).', পরিশোধ '.Hisab::money($s->paid).')', 'amount' => $s->dueAmount()]);
        }
        foreach ($customer->payments()->latest('date')->latest('id')->get() as $p) {
            $entries->push(['date' => $p->date->toDateString(), 'type' => 'আদায়', 'note' => 'আদায় #'.$p->id, 'amount' => -((float) $p->amount)]);
        }
        foreach ($entries->sortBy([['date', 'asc']]) as $e) {
            $running = round($running + $e['amount'], 2);
            $rows[] = [$e['date'], $e['type'], $e['note'], $e['amount'], $running];
        }
        $rows[] = ['', '', 'মোট বাকি', '', $customer->dueAmount()];

        $safe = preg_replace('/[^\pL\pN\-_]+/u', '_', $customer->name);

        return $this->download('ledger-'.$safe.'-'.now()->format('Y-m-d').'.csv', $rows);
    }

    public function transactionsCsv(Request $request): StreamedResponse
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

        $rows = [['তারিখ', 'ধরন', 'খাত', 'খাতের ধরন', 'বিবরণ', 'টাকা']];
        foreach ($query->get() as $t) {
            $rows[] = [
                $t->date->toDateString(),
                $t->type === 'income' ? 'আয়' : 'ব্যয়',
                $t->category,
                $t->scope === 'personal' ? 'ব্যক্তিগত' : 'দোকান',
                $t->note ?? '',
                (float) $t->amount,
            ];
        }

        return $this->download('transactions-'.$month.'.csv', $rows);
    }

    private function download(string $filename, array $rows): StreamedResponse
    {
        return Response::streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
