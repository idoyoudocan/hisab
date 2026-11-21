<div class="card">
  <div class="field-row two">
    <div>
      <label>কাস্টমার</label>
      <select wire:model="customer_id">
        <option value="">খুচরা কাস্টমার (নগদ)</option>
        @foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}@if($c->phone) — {{ $c->phone }}@endif</option>@endforeach
      </select>
      @error('customer_id')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div>
      <label>তারিখ</label>
      <input type="date" wire:model="sale_date">
      @error('sale_date')<div class="error">{{ $message }}</div>@enderror
    </div>
  </div>

  @foreach($rows as $i => $row)
  <div class="card" style="background:var(--input);box-shadow:none">
    <div class="field-row three">
      <div>
        <label>মাল</label>
        <select wire:model="rows.{{ $i }}.product_id">
          <option value="">বাছুন…</option>
          @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} (স্টক {{ (float) $p->stock }})</option>@endforeach
        </select>
        @error("rows.$i.product_id")<div class="error">{{ $message }}</div>@enderror
      </div>
      <div>
        <label>পরিমাণ</label>
        <input type="number" step="0.01" min="0.01" wire:model.live="rows.{{ $i }}.qty">
        @error("rows.$i.qty")<div class="error">{{ $message }}</div>@enderror
      </div>
      <div>
        <label>দাম (প্রতি {{ $products->firstWhere('id', (int) ($row['product_id'] ?? 0))?->unit ?? 'পিস' }})</label>
        <input type="number" step="0.01" min="0" wire:model.live="rows.{{ $i }}.price">
        @error("rows.$i.price")<div class="error">{{ $message }}</div>@enderror
      </div>
    </div>
    <div class="flex space-between">
      <strong>লাইন মোট: {{ \App\Support\Hisab::money((float) ($row['qty'] ?? 0) * (float) ($row['price'] ?? 0)) }}</strong>
      @if(count($rows) > 1)<button type="button" class="btn danger sm" wire:click="removeRow({{ $i }})">মুছুন</button>@endif
    </div>
  </div>
  @endforeach
  <button type="button" class="btn ghost sm" wire:click="addRow">+ আরেকটি মাল যোগ করুন</button>

  <div class="field-row two mt">
    <div>
      <label>মাধ্যম</label>
      <select wire:model="payment_method">
        @foreach($methods as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
      </select>
    </div>
    <div>
      <label>পরিশোধ (টাকা)</label>
      <input type="number" step="0.01" min="0" wire:model.live="paid">
      @error('paid')<div class="error">{{ $message }}</div>@enderror
    </div>
  </div>
  <label>নোট</label>
  <input type="text" wire:model="note" placeholder="ঐচ্ছিক">

  <div class="total-row"><span>মোট</span><span>{{ \App\Support\Hisab::money($this->total) }}</span></div>
  <div class="list-row"><span>পরিশোধ</span><span>{{ \App\Support\Hisab::money($paid) }}</span></div>
  <div class="list-row"><span>বাকি থাকবে</span><span>@if($this->due > 0)<span class="badge amber">{{ \App\Support\Hisab::money($this->due) }}</span>@else <span class="badge green">৳০</span>@endif</span></div>

  <button type="button" class="btn block mt" wire:click="save" wire:loading.attr="disabled">বিক্রি সংরক্ষণ করুন</button>
</div>
