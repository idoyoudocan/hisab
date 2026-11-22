<div>
<div class="flex space-between">
  <h1 style="margin-bottom:0">টাকার হিসাব</h1>
  <button type="button" class="btn dark sm" @click="window.dispatchEvent(new CustomEvent('open-account-modal'))" wire:click="openAdd">+ অ্যাকাউন্ট যোগ করুন</button>
</div>
<p class="muted">সব অ্যাকাউন্ট মিলে মোট ব্যালেন্স <strong>{{ \App\Support\Hisab::money($register->displayBalance($business) + collect($totals)->sum()) }}</strong></p>

<x-modal wire-prop="showFormModal" open-event="open-account-modal" title="{{ $editId ? 'অ্যাকাউন্ট এডিট করুন' : 'নতুন অ্যাকাউন্ট যোগ করুন' }}">
  <div class="field-row two">
    <div>
      <label>ধরন</label>
      <select wire:model.live="type">
        @foreach($types as $key => $label)
          @if($key !== 'cash_register' && ($key !== 'sim' || $simEnabled))<option value="{{ $key }}">{{ $label }}</option>@endif
        @endforeach
      </select>
      @error('type')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div>
      <label>প্রতিষ্ঠান / অপারেটর</label>
      <select wire:model="provider">
        @foreach($providers as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach
      </select>
      @error('provider')<div class="error">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="field-row two">
    <div>
      <label>নাম</label>
      <input type="text" wire:model="label" placeholder="যেমন: বিকাশ পার্সোনাল, ডাচ-বাংলা সেভিংস">
      @error('label')<div class="error">{{ $message }}</div>@enderror
    </div>
    <div>
      <label>অ্যাকাউন্ট / মোবাইল নম্বর — ঐচ্ছিক</label>
      <input type="text" wire:model="identifier" placeholder="যেমন: 01XXXXXXXXX">
      @error('identifier')<div class="error">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="field-row two">
    <div>
      <label>বর্তমান ব্যালেন্স (টাকা)</label>
      <input type="number" step="0.01" min="0" wire:model="balance" placeholder="0">
      @error('balance')<div class="error">{{ $message }}</div>@enderror
      <p class="hint">ব্যালেন্স বদলালে মূলধনের হিসাবও বদলে যাবে। ক্যাশ রেজিস্টারের টাকা হিসাব থেকে আপনাআপনি ধরা হয় — এখানে বদলানো যায় না।</p>
    </div>
    <div>
      <label>নোট — ঐচ্ছিক</label>
      <input type="text" wire:model="note" placeholder="যেমন: দোকানের বিকাশ">
    </div>
  </div>
  <div class="flex">
    <button type="button" class="btn mt" wire:click="save">{{ $editId ? 'হালনাগাদ করুন' : 'অ্যাকাউন্ট যোগ করুন' }}</button>
    <button type="button" class="btn ghost mt" @click="open = false" wire:click="cancelEdit">বাতিল</button>
  </div>
</x-modal>

<div class="card">
  <p class="section-label">ক্যাশ রেজিস্টার</p>
  <h2>নগদ টাকা</h2>
  <div class="list-row">
    <div>
      <strong>{{ $register->label }}</strong><br>
      <span class="muted">বিক্রি, আদায়, আয় ও খরচের হিসাব থেকে আপনাআপনি ধরা হয়</span>
    </div>
    <strong class="num">{{ \App\Support\Hisab::money($register->displayBalance($business)) }}</strong>
  </div>
  @if($business->moduleEnabled('cash_count'))
  <a href="{{ route('cash-count.index') }}">ক্যাশ মেলান →</a>
  @endif
</div>

@foreach($totals as $type => $total)
  @if($type === 'sim' && ! $simEnabled) @continue @endif
  @php($list = $others->where('type', $type))
  @if($list->isNotEmpty())
    <div class="card">
      <p class="section-label">{{ \App\Models\MoneyAccount::types()[$type] }}</p>
      <h2>{{ \App\Models\MoneyAccount::types()[$type] }} — মোট {{ \App\Support\Hisab::money($total) }}</h2>
      @foreach($list as $a)
        <div class="list-row">
          <div>
            <strong>{{ $a->label }}</strong>
            @if($a->provider) · {{ $a->provider }}@endif
            @if($a->identifier)<br><span class="muted">{{ $a->identifier }}</span>@endif
            @if($a->note)<br><span class="muted">{{ $a->note }}</span>@endif
          </div>
          <div style="display:flex;gap:8px;align-items:center">
            <strong class="num">{{ \App\Support\Hisab::money($a->balance) }}</strong>
            <button type="button" class="btn ghost sm" @click="window.dispatchEvent(new CustomEvent('open-account-modal'))" wire:click="editAccount({{ $a->id }})">এডিট</button>
            <button type="button" class="btn danger sm" wire:click="delete({{ $a->id }})" wire:confirm="এই অ্যাকাউন্টটি মুছে ফেলবেন?">মুছুন</button>
          </div>
        </div>
      @endforeach
      <button type="button" class="btn ghost sm mt" @click="window.dispatchEvent(new CustomEvent('open-account-modal'))" wire:click="openAdd('{{ $type }}')">+ আরও {{ \App\Models\MoneyAccount::types()[$type] }} যোগ করুন</button>
    </div>
  @else
    <div class="card">
      <div class="list-row" style="border-bottom:0;padding:4px 0">
        <span><strong>{{ \App\Models\MoneyAccount::types()[$type] }}</strong> <span class="muted">— এখনো যোগ করা হয়নি</span></span>
        <button type="button" class="btn ghost sm" @click="window.dispatchEvent(new CustomEvent('open-account-modal'))" wire:click="openAdd('{{ $type }}')">যোগ করুন</button>
      </div>
    </div>
  @endif
@endforeach

</div>
