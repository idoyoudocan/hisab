<div>
  @if($error)<div class="flash err">{{ $error }}</div>@endif
  <p class="muted">বর্তমান বাকি: <strong>{{ \App\Support\Hisab::money($due) }}</strong></p>
  <div class="field-row three">
    <div>
      <label>পরিশোধের পরিমাণ (টাকা)</label>
      <input type="number" step="0.01" min="0.01" wire:model="amount">
      @error('amount')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div>
      <label>মাধ্যম</label>
      <select wire:model="method">
        @foreach($methods as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
      </select>
    </div>
    <div>
      <label>তারিখ</label>
      <input type="date" wire:model="date">
    </div>
  </div>
  <label>নোট</label>
  <input type="text" wire:model="note" placeholder="ঐচ্ছিক">
  <button type="button" class="btn mt" wire:click="save">পরিশোধ সংরক্ষণ করুন</button>
</div>
