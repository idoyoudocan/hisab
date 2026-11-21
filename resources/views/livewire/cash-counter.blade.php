<div>
<div class="card">
  <h2>নোট গুনুন</h2>
  <p class="muted">ক্যাশে কত টাকার কতগুলো নোট আছে, শুধু সংখ্যাগুলো বসান — মোট আপনাআপনি হিসাব হবে।</p>
  @foreach($denominations as $d)
    <div class="list-row">
      <span><strong>{{ number_format($d) }} টাকার নোট</strong></span>
      <span class="stepper">
        <button type="button" class="step-btn" wire:click="decrement({{ $d }})" aria-label="একটি কমান">−</button>
        <input type="number" min="0" max="100000" step="1" wire:model.live="counts.{{ $d }}" aria-label="{{ $d }} টাকার নোটের সংখ্যা" style="width:84px;text-align:center">
        <button type="button" class="step-btn" wire:click="increment({{ $d }})" aria-label="একটি বাড়ান">+</button>
        <span class="num" style="min-width:96px;text-align:right">{{ \App\Support\Hisab::money((float) $d * (int) ($counts[(string) $d] ?? 0)) }}</span>
      </span>
    </div>
  @endforeach

  <div class="total-row"><span>গোনা মোট</span><span class="num">{{ \App\Support\Hisab::money($this->total) }}</span></div>
  <div class="list-row"><span>হিসাব অনুযায়ী ক্যাশে থাকার কথা</span><span class="num">{{ \App\Support\Hisab::money($this->systemCash) }}</span></div>
  <div class="list-row">
    <span>পার্থক্য</span>
    @if($this->isMatched)
      <span class="badge green">মিলেছে ✓</span>
    @elseif($this->difference < 0)
      <span class="badge red">{{ \App\Support\Hisab::money(abs($this->difference)) }} কম</span>
    @else
      <span class="badge amber">{{ \App\Support\Hisab::money($this->difference) }} বেশি</span>
    @endif
  </div>

  <label>নোট — ঐচ্ছিক</label>
  <input type="text" wire:model="note" placeholder="যেমন: রাতের ক্যাশ মেলানো">
  <button type="button" class="btn block mt" wire:click="save" wire:loading.attr="disabled">গোনা সংরক্ষণ করুন</button>
  <p class="hint">সংরক্ষণ করলে এই গোনা ইতিহাসে থাকবে — পরে যেকোনো সময় মেলাতে পারবেন।</p>
</div>

<div class="card">
  <p class="section-label">ইতিহাস</p>
  <h2>আগের গোনা</h2>
  @forelse($history as $c)
    <div class="list-row">
      <div>
        <strong>{{ $c->counted_at->format('d M Y, h:i A') }}</strong><br>
        <span class="muted">গোনা {{ \App\Support\Hisab::money($c->total) }} · হিসাব {{ \App\Support\Hisab::money($c->system_cash) }}</span>
        @if($c->note)<br><span class="muted">{{ $c->note }}</span>@endif
      </div>
      @if($c->isMatched())
        <span class="badge green">মিলেছে ✓</span>
      @elseif((float) $c->difference < 0)
        <span class="badge red">{{ \App\Support\Hisab::money(abs((float) $c->difference)) }} কম</span>
      @else
        <span class="badge amber">{{ \App\Support\Hisab::money((float) $c->difference) }} বেশি</span>
      @endif
    </div>
  @empty
    <div class="empty"><span class="ic">💵</span>এখনো ক্যাশ মেলানো হয়নি।<br>উপরে নোট গুনে সংরক্ষণ করুন।</div>
  @endforelse
</div>

</div>
