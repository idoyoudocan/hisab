<div class="card">
  <h2>নতুন কাস্টমার</h2>
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
  <button type="button" class="btn mt" wire:click="save">কাস্টমার যোগ করুন</button>
</div>
