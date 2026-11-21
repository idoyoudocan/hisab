<div class="card">
  <h2>নতুন আয় / ব্যয়</h2>
  <div class="field-row two">
    <div>
      <label>ধরন</label>
      <select wire:model.live="type">
        <option value="expense">ব্যয়</option>
        <option value="income">আয়</option>
      </select>
    </div>
    <div>
      <label>খাত</label>
      <select wire:model="category">
        @foreach($categories as $c)<option value="{{ $c->name }}">{{ $c->name }}{{ $c->scope === 'personal' ? ' (ব্যক্তিগত)' : '' }}</option>@endforeach
      </select>
      @error('category')<div class="error">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="field-row three">
    <div>
      <label>টাকা</label>
      <input type="number" step="0.01" min="0.01" wire:model="amount">
      @error('amount')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div>
      <label>তারিখ</label>
      <input type="date" wire:model="date">
    </div>
    <div>
      <label>মাধ্যম</label>
      <select wire:model="method">
        @foreach($methods as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
      </select>
    </div>
  </div>
  <div class="field-row two">
    <div>
      <label>কার খরচ?</label>
      <select wire:model="scope">
        <option value="shop">দোকানের</option>
        <option value="personal">ব্যক্তিগত</option>
      </select>
    </div>
    <div>
      <label>নোট</label>
      <input type="text" wire:model="note" placeholder="ঐচ্ছিক">
    </div>
  </div>
  <button type="button" class="btn mt" wire:click="save">সংরক্ষণ করুন</button>

  <h3 class="mt">সাম্প্রতিক লেনদেন</h3>
  @forelse($recent as $t)
    <div class="list-row">
      <div>{{ $t->date->format('d M Y') }} · {{ $t->category }} · {{ $t->scope === 'personal' ? 'ব্যক্তিগত' : 'দোকান' }}@if($t->note)<br><span class="muted">{{ $t->note }}</span>@endif</div>
      <div style="text-align:right">
        <span class="badge {{ $t->type === 'income' ? 'green' : 'red' }}">{{ $t->type === 'income' ? '+' : '−' }} {{ \App\Support\Hisab::money($t->amount) }}</span><br>
        <button type="button" class="btn danger sm" wire:click="delete({{ $t->id }})" wire:confirm="লেনদেনটি মুছে ফেলবেন?">মুছুন</button>
      </div>
    </div>
  @empty
    <p class="muted">এখনো কোনো লেনদেন নেই।</p>
  @endforelse
</div>
