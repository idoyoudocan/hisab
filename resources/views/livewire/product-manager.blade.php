<div>
  <div class="card">
    <h2>{{ $editingId ? 'মাল সম্পাদনা' : 'নতুন মাল যোগ করুন' }}</h2>
    <div class="field-row two">
      <div>
        <label>নাম</label>
        <input type="text" wire:model="name" placeholder="যেমন: চিনি">
        @error('name')<div class="error">{{ $message }}</div>@enderror
      </div>
      <div>
        <label>SKU (ঐচ্ছিক)</label>
        <input type="text" wire:model="sku" placeholder="কোড">
      </div>
    </div>
    <div class="field-row three">
      <div>
        <label>একক</label>
        <select wire:model="unit">
          @foreach($units as $u)<option value="{{ $u }}">{{ $u }}</option>@endforeach
        </select>
      </div>
      <div>
        <label>কেনা দাম</label>
        <input type="number" step="0.01" min="0" wire:model="purchase_price">
        @error('purchase_price')<div class="error">{{ $message }}</div>@enderror
      </div>
      <div>
        <label>বিক্রয় দাম</label>
        <input type="number" step="0.01" min="0" wire:model="sale_price">
        @error('sale_price')<div class="error">{{ $message }}</div>@enderror
      </div>
    </div>
    <div class="field-row two">
      <div>
        <label>স্টক</label>
        <input type="number" step="0.01" min="0" wire:model="stock">
        @error('stock')<div class="error">{{ $message }}</div>@enderror
      </div>
      <div>
        <label>কম স্টক সতর্কতা (এর নিচে গেলে সতর্কতা)</label>
        <input type="number" step="0.01" min="0" wire:model="low_stock_at">
      </div>
    </div>
    <div class="flex mt">
      <button type="button" class="btn" wire:click="save">{{ $editingId ? 'আপডেট করুন' : 'যোগ করুন' }}</button>
      @if($editingId)<button type="button" class="btn ghost" wire:click="cancelEdit">বাতিল</button>@endif
    </div>
  </div>

  <div class="card">
    <input type="text" wire:model.live="search" placeholder="নাম বা SKU দিয়ে খুঁজুন">
    <table class="table mt">
      <thead><tr><th>মাল</th><th class="num">কেনা</th><th class="num">বিক্রয়</th><th class="num">স্টক</th><th></th></tr></thead>
      <tbody>
      @forelse($products as $p)
        <tr>
          <td data-label="মাল"><strong>{{ $p->name }}</strong>@if($p->sku)<br><span class="muted">{{ $p->sku }}</span>@endif<br><span class="muted">{{ $p->unit }}</span></td>
          <td data-label="কেনা" class="num">{{ \App\Support\Hisab::money($p->purchase_price) }}</td>
          <td data-label="বিক্রয়" class="num">{{ \App\Support\Hisab::money($p->sale_price) }}</td>
          <td data-label="স্টক" class="num">@if($p->isLowStock())<span class="badge red">{{ (float) $p->stock }} — কম!</span>@else {{ (float) $p->stock }}@endif</td>
          <td data-label=""><button type="button" class="btn ghost sm" wire:click="edit({{ $p->id }})">সম্পাদনা</button>
          <button type="button" class="btn danger sm" wire:click="delete({{ $p->id }})" wire:confirm="মালটি মুছে ফেলবেন?">মুছুন</button></td>
        </tr>
      @empty
        <tr><td colspan="5" class="muted">কোনো মাল পাওয়া যায়নি।</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
</div>
