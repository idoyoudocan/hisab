<?php

namespace App\Livewire;

use App\Models\Business;
use App\Services\PaymentService;
use App\Support\Hisab;
use Livewire\Component;

class CollectionForm extends Component
{
    public int $businessId;
    public int $customerId;
    public float $due = 0;
    public float $amount = 0;
    public string $method = 'cash';
    public string $date = '';
    public string $note = '';
    public string $error = '';

    public function mount(int $customerId): void
    {
        $business = Hisab::requireBusiness();
        $this->businessId = $business->id;
        $this->customerId = $customerId;
        $this->due = $business->customers()->findOrFail($customerId)->dueAmount();
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
            PaymentService::record($business, 'customer', $this->customerId, [
                'amount' => $this->amount,
                'method' => $this->method,
                'date' => $this->date,
                'note' => $this->note ?: null,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->error = $e->getMessage();

            return;
        }

        return redirect()->route('customers.show', $this->customerId)
            ->with('status', 'আদায় সংরক্ষিত হয়েছে।');
    }

    public function render()
    {
        return view('livewire.collection-form', [
            'methods' => Hisab::paymentMethods(),
        ]);
    }
}
