<?php

namespace App\Livewire;

use App\Models\Business;
use App\Support\Hisab;
use Livewire\Component;

class TransactionManager extends Component
{
    public int $businessId;
    public string $type = 'expense';
    public string $category = '';
    public string $scope = 'shop';
    public float $amount = 0;
    public string $date = '';
    public string $method = 'cash';
    public string $note = '';

    public function mount(): void
    {
        $business = Hisab::requireBusiness();
        $this->businessId = $business->id;
        $this->date = now()->toDateString();
        $this->category = $this->defaultCategory();
    }

    public function updatedType(): void
    {
        $this->category = $this->defaultCategory();
    }

    private function defaultCategory(): string
    {
        $business = Business::find($this->businessId);
        $cat = $business?->categories()->where('type', $this->type)->orderBy('id')->first();

        return $cat ? $cat->name : 'অন্যান্য';
    }

    public function save(): void
    {
        $business = Business::findOrFail($this->businessId);

        $data = $this->validate([
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'string', 'max:100'],
            'scope' => ['required', 'in:shop,personal'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'method' => ['required', 'string'],
        ]);
        $business->transactions()->create($data);
        $this->reset(['amount', 'note']);
        $this->date = now()->toDateString();
        session()->flash('status', 'লেনদেন সংরক্ষিত হয়েছে।');
    }

    public function delete(int $id): void
    {
        Business::findOrFail($this->businessId)->transactions()->findOrFail($id)->delete();
        session()->flash('status', 'লেনদেন মুছে ফেলা হয়েছে।');
    }

    public function render()
    {
        $business = Business::findOrFail($this->businessId);

        return view('livewire.transaction-manager', [
            'categories' => $business->categories()->where('type', $this->type)->orderBy('name')->get(),
            'recent' => $business->transactions()->latest('date')->latest('id')->take(20)->get(),
            'methods' => Hisab::paymentMethods(),
        ]);
    }
}
