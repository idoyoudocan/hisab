<?php

namespace App\Livewire;

use App\Models\Business;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PurchaseForm extends Component
{
    public int $businessId;
    public string $supplier_id = '';
    public string $purchase_date = '';
    public string $note = '';
    public float $paid = 0;
    public array $rows = [];

    public function mount(): void
    {
        $business = \App\Support\Hisab::requireBusiness();
        $this->businessId = $business->id;
        $this->purchase_date = now()->toDateString();
        $this->rows = [['product_id' => '', 'qty' => 1, 'price' => 0]];
    }

    public function addRow(): void
    {
        $this->rows[] = ['product_id' => '', 'qty' => 1, 'price' => 0];
    }

    public function removeRow(int $index): void
    {
        unset($this->rows[$index]);
        $this->rows = array_values($this->rows);
    }

    public function updatedRows($value, $key): void
    {
        [$index, $field] = explode('.', $key, 2) + [null, null];
        if ($field !== 'product_id' || ! $value) {
            return;
        }
        $product = Business::find($this->businessId)?->products()->find($value);
        if ($product) {
            $this->rows[(int) $index]['price'] = (float) $product->purchase_price;
        }
    }

    #[Computed]
    public function total(): float
    {
        $total = 0.0;
        foreach ($this->rows as $row) {
            $total += (float) ($row['qty'] ?? 0) * (float) ($row['price'] ?? 0);
        }

        return round($total, 2);
    }

    #[Computed]
    public function due(): float
    {
        return round($this->total - (float) $this->paid, 2);
    }

    public function save()
    {
        $business = Business::findOrFail($this->businessId);
        $purchase = $this->createPurchase($business);

        return redirect()->route('purchases.show', $purchase)->with('status', 'কেনাকাটা সংরক্ষিত হয়েছে।');
    }

    /** Save and stay on the page with a fresh form for the next purchase. */
    public function saveAndAddAnother(): void
    {
        $business = Business::findOrFail($this->businessId);
        $this->createPurchase($business);

        $this->supplier_id = '';
        $this->purchase_date = now()->toDateString();
        $this->note = '';
        $this->paid = 0;
        $this->rows = [['product_id' => '', 'qty' => 1, 'price' => 0]];
        session()->flash('status', 'কেনাকাটা সংরক্ষিত হয়েছে — পরের কেনাকাটা লিখুন।');
    }

    private function createPurchase(Business $business): Purchase
    {
        $this->validate([
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('business_id', $business->id)],
            'purchase_date' => ['required', 'date'],
            'paid' => ['required', 'numeric', 'min:0', 'max:'.$this->total],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.product_id' => ['required', Rule::exists('products', 'id')->where('business_id', $business->id)],
            'rows.*.qty' => ['required', 'numeric', 'min:0.01'],
            'rows.*.price' => ['required', 'numeric', 'min:0'],
        ], [
            'rows.*.product_id.required' => 'প্রতিটি লাইনে মাল বাছুন।',
            'paid.max' => 'পরিশোধ মোট টাকার বেশি হতে পারবে না।',
        ]);

        $purchase = PurchaseService::createPurchase($business, [
            'supplier_id' => $this->supplier_id ?: null,
            'purchase_date' => $this->purchase_date,
            'paid' => $this->paid,
            'note' => $this->note ?: null,
        ], $this->rows);

        return $purchase;
    }

    public function render()
    {
        $business = Business::findOrFail($this->businessId);

        return view('livewire.purchase-form', [
            'products' => $business->products()->orderBy('name')->get(),
            'suppliers' => $business->suppliers()->orderBy('name')->get(),
        ]);
    }
}
