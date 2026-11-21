<?php

namespace App\Livewire;

use App\Models\Business;
use App\Services\SaleService;
use App\Support\Hisab;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SaleForm extends Component
{
    public int $businessId;
    public string $customer_id = '';
    public string $sale_date = '';
    public string $payment_method = 'cash';
    public string $note = '';
    public float $paid = 0;
    public float $discount = 0;
    public array $rows = [];

    public function mount(): void
    {
        $business = Hisab::requireBusiness();
        $this->businessId = $business->id;
        $this->sale_date = now()->toDateString();
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

    /** When a product is picked, fill its sale price automatically. */
    public function updatedRows($value, $key): void
    {
        [$index, $field] = explode('.', $key, 2) + [null, null];
        if ($field !== 'product_id' || ! $value) {
            return;
        }
        $product = Business::find($this->businessId)?->products()->find($value);
        if ($product) {
            $this->rows[(int) $index]['price'] = (float) $product->sale_price;
        }
    }

    #[Computed]
    public function subtotal(): float
    {
        $total = 0.0;
        foreach ($this->rows as $row) {
            $total += (float) ($row['qty'] ?? 0) * (float) ($row['price'] ?? 0);
        }

        return round($total, 2);
    }

    #[Computed]
    public function total(): float
    {
        $discount = max(0, min((float) $this->discount, $this->subtotal));

        return round($this->subtotal - $discount, 2);
    }

    #[Computed]
    public function due(): float
    {
        return round($this->total - (float) $this->paid, 2);
    }

    /** Credit-limit warning when this sale would push the customer over their limit. */
    #[Computed]
    public function creditWarning(): ?string
    {
        if ($this->customer_id === '' || $this->due() <= 0) {
            return null;
        }
        $customer = Business::find($this->businessId)?->customers()->find($this->customer_id);
        if (! $customer || $customer->credit_limit === null || (float) $customer->credit_limit <= 0) {
            return null;
        }
        $projected = round($customer->dueAmount() + $this->due(), 2);
        if ($projected <= (float) $customer->credit_limit) {
            return null;
        }

        return 'সতর্কতা: '.$customer->name.'-এর বাকি '.Hisab::money($projected).' হবে, যা ক্রেডিট সীমা '.Hisab::money($customer->credit_limit).' ছাড়িয়ে যাবে। বিক্রি করা যাবে, তবে আদায়ের কথা মনে রাখবেন।';
    }

    public function save()
    {
        $business = Business::findOrFail($this->businessId);

        $this->validate([
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->where('business_id', $business->id)],
            'sale_date' => ['required', 'date'],
            'payment_method' => ['required', 'string'],
            'paid' => ['required', 'numeric', 'min:0', 'max:'.$this->total],
            'discount' => ['nullable', 'numeric', 'min:0', 'max:'.$this->subtotal],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.product_id' => ['required', Rule::exists('products', 'id')->where('business_id', $business->id)],
            'rows.*.qty' => ['required', 'numeric', 'min:0.01'],
            'rows.*.price' => ['required', 'numeric', 'min:0'],
        ], [
            'rows.*.product_id.required' => 'প্রতিটি লাইনে মাল বাছুন।',
            'paid.max' => 'পরিশোধ মোট টাকার বেশি হতে পারবে না।',
            'discount.max' => 'ছাড় মোট টাকার বেশি হতে পারবে না।',
        ]);

        $sale = SaleService::createSale($business, [
            'customer_id' => $this->customer_id ?: null,
            'sale_date' => $this->sale_date,
            'paid' => $this->paid,
            'discount_amount' => $this->discount,
            'payment_method' => $this->payment_method,
            'note' => $this->note ?: null,
        ], $this->rows);

        return redirect()->route('sales.show', $sale)->with('status', 'বিক্রি সংরক্ষিত হয়েছে।');
    }

    public function render()
    {
        $business = Business::findOrFail($this->businessId);

        return view('livewire.sale-form', [
            'products' => $business->products()->orderBy('name')->get(),
            'customers' => $business->customers()->orderBy('name')->get(),
            'methods' => Hisab::paymentMethods(),
        ]);
    }
}
