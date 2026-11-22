<?php

namespace App\Livewire;

use App\Models\Business;
use App\Services\PaymentService;
use App\Support\Hisab;
use Livewire\Component;

class SupplierPaymentForm extends Component
{
    public int $businessId;
    public int $supplierId;
    public float $due = 0;
    public $amount = 0;
    public string $method = 'cash';
    public string $date = '';
    public string $note = '';
    public string $error = '';

    /** Number inputs send '' when cleared — coerce to 0 (typed float can't hold ''). */
    public function updatedAmount($value): void
    {
        $this->amount = ($value === '' || $value === null) ? 0 : (float) $value;
    }

    public function mount(int $supplierId): void
    {
        $business = Hisab::requireBusiness();
        $this->businessId = $business->id;
        $this->supplierId = $supplierId;
        $this->due = $business->suppliers()->findOrFail($supplierId)->dueAmount();
        $this->date = now()->toDateString();
    }

    public function save()
    {
        $business = Business::findOrFail($this->businessId);
        $this->error = '';

        $this->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string'],
            'date' => ['required', 'date'],
        ]);

        try {
            PaymentService::record($business, 'supplier', $this->supplierId, [
                'amount' => $this->amount,
                'method' => $this->method,
                'date' => $this->date,
                'note' => $this->note ?: null,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->error = $e->getMessage();

            return;
        }

        return redirect()->route('suppliers.show', $this->supplierId)
            ->with('status', 'পরিশোধ সংরক্ষিত হয়েছে।');
    }

    public function render()
    {
        return view('livewire.supplier-payment-form', [
            'methods' => Hisab::paymentMethods(),
        ]);
    }
}
