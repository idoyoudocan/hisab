<?php

namespace App\Livewire;

use App\Models\Business;
use App\Support\Hisab;
use Livewire\Component;

class ProductManager extends Component
{
    public int $businessId;
    public string $search = '';
    public ?int $editingId = null;
    public string $name = '';
    public string $sku = '';
    public string $unit = 'পিস';
    public $purchase_price = 0;
    public $sale_price = 0;
    public $stock = 0;
    public $low_stock_at = 0;

    /** Number inputs send '' when cleared — coerce to 0 (typed float can't hold ''). */
    public function updatedPurchasePrice($value): void
    {
        $this->purchase_price = ($value === '' || $value === null) ? 0 : (float) $value;
    }

    public function updatedSalePrice($value): void
    {
        $this->sale_price = ($value === '' || $value === null) ? 0 : (float) $value;
    }

    public function updatedStock($value): void
    {
        $this->stock = ($value === '' || $value === null) ? 0 : (float) $value;
    }

    public function updatedLowStockAt($value): void
    {
        $this->low_stock_at = ($value === '' || $value === null) ? 0 : (float) $value;
    }

    public function mount(): void
    {
        $this->businessId = Hisab::requireBusiness()->id;
    }

    public function edit(int $id): void
    {
        $product = Business::findOrFail($this->businessId)->products()->findOrFail($id);
        $this->editingId = $product->id;
        $this->name = $product->name;
        $this->sku = (string) ($product->sku ?? '');
        $this->unit = $product->unit;
        $this->purchase_price = (float) $product->purchase_price;
        $this->sale_price = (float) $product->sale_price;
        $this->stock = (float) $product->stock;
        $this->low_stock_at = (float) $product->low_stock_at;
    }

    public function cancelEdit(): void
    {
        $this->reset(['editingId', 'name', 'sku', 'unit', 'purchase_price', 'sale_price', 'stock', 'low_stock_at']);
        $this->unit = 'পিস';
    }

    public function save(): void
    {
        $business = Business::findOrFail($this->businessId);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:150'],
            'sku' => ['nullable', 'string', 'max:50'],
            'unit' => ['required', 'string', 'max:20'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'numeric', 'min:0'],
            'low_stock_at' => ['required', 'numeric', 'min:0'],
        ]);
        $data['business_id'] = $business->id;

        if ($this->editingId) {
            $business->products()->findOrFail($this->editingId)->update($data);
            session()->flash('status', 'মাল আপডেট হয়েছে।');
        } else {
            $business->products()->create($data);
            session()->flash('status', 'নতুন মাল যোগ হয়েছে।');
        }
        $this->cancelEdit();
    }

    public function delete(int $id): void
    {
        Business::findOrFail($this->businessId)->products()->findOrFail($id)->delete();
        session()->flash('status', 'মাল মুছে ফেলা হয়েছে।');
    }

    public function render()
    {
        $business = Business::findOrFail($this->businessId);
        $query = $business->products()->orderBy('name');
        if ($this->search !== '') {
            $query->where(function ($w) {
                $w->where('name', 'like', '%'.$this->search.'%')
                  ->orWhere('sku', 'like', '%'.$this->search.'%');
            });
        }

        return view('livewire.product-manager', [
            'products' => $query->take(100)->get(),
            'units' => Hisab::units(),
        ]);
    }
}
