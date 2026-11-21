<?php

namespace App\Livewire;

use App\Models\Business;
use Livewire\Attributes\On;
use Livewire\Component;

class CustomerManager extends Component
{
    public int $businessId;
    public ?int $editId = null;
    public string $name = '';
    public string $phone = '';
    public string $address = '';
    public string $credit_limit = '';

    public function mount(): void
    {
        $this->businessId = \App\Support\Hisab::requireBusiness()->id;
    }

    #[On('edit-customer')]
    public function editCustomer(int $id): void
    {
        $customer = Business::findOrFail($this->businessId)->customers()->findOrFail($id);
        $this->editId = $customer->id;
        $this->name = $customer->name;
        $this->phone = $customer->phone ?? '';
        $this->address = $customer->address ?? '';
        $this->credit_limit = $customer->credit_limit !== null ? (string) (float) $customer->credit_limit : '';
    }

    public function cancelEdit(): void
    {
        $this->reset(['editId', 'name', 'phone', 'address', 'credit_limit']);
    }

    public function save(): void
    {
        $business = Business::findOrFail($this->businessId);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
        ]);
        $data['credit_limit'] = $data['credit_limit'] === null || $data['credit_limit'] === ''
            ? null
            : (float) $data['credit_limit'];

        if ($this->editId) {
            $business->customers()->findOrFail($this->editId)->update($data);
            session()->flash('status', 'কাস্টমারের তথ্য হালনাগাদ হয়েছে।');
        } else {
            $business->customers()->create($data);
            session()->flash('status', 'নতুন কাস্টমার যোগ হয়েছে।');
        }
        $this->reset(['editId', 'name', 'phone', 'address', 'credit_limit']);
    }

    public function render()
    {
        return view('livewire.customer-manager');
    }
}
