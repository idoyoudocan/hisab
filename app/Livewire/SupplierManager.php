<?php

namespace App\Livewire;

use App\Models\Business;
use Livewire\Component;

class SupplierManager extends Component
{
    public int $businessId;
    public string $name = '';
    public string $phone = '';
    public string $address = '';

    public function mount(): void
    {
        $this->businessId = \App\Support\Hisab::requireBusiness()->id;
    }

    public function save(): void
    {
        $business = Business::findOrFail($this->businessId);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);
        $business->suppliers()->create($data);
        $this->reset(['name', 'phone', 'address']);
        session()->flash('status', 'নতুন সাপ্লায়ার যোগ হয়েছে।');
    }

    public function render()
    {
        return view('livewire.supplier-manager');
    }
}
