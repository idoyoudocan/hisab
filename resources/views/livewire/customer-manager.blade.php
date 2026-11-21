<div class="card">
  <h2>{{ $editId ? 'কাস্টমার এডিট করুন' : 'নতুন কাস্টমার' }}</h2>
  <div class="field-row three">
    <div>
      <label>নাম</label>
      <input type="text" wire:model="name" placeholder="কাস্টমারের নাম">
      @error('name')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div>
      <label>ফোন</label>
      <input type="text" wire:model="phone" placeholder="01XXXXXXXXX">
    </div>
    <div>
      <label>ঠিকানা</label>
      <input type="text" wire:model="address" placeholder="ঐচ্ছিক">
    </div>
  </div>
  <div class="field-row two">
    <div>
      <label>ক্রেডিট লিমিট (টাকা) — ঐচ্ছিক</label>
      <input type="number" step="0.01" min="0" wire:model="credit_limit" placeholder="যেমন ৫০০০ — খালি রাখলে সীমা নেই">
      @error('credit_limit')<div class="error">{{ $message }}</div>@enderror
      <p class="muted">বাকি এই সীমা ছাড়ালে বিক্রির সময় সতর্কতা দেখাবে।</p>
    </div>
  </div>
  <div class="flex">
    <button type="button" class="btn mt" wire:click="save">{{ $editId ? 'হালনাগাদ করুন' : 'কাস্টমার যোগ করুন' }}</button>
    @if($editId)<button type="button" class="btn ghost mt" wire:click="cancelEdit">বাতিল</button>@endif
  </div>
</div>
