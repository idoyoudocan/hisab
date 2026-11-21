<div>
<button type="button" class="btn dark block" wire:click="openModal">💵 ক্যাশ মেলান</button>

<x-modal :show="$showModal" wire-prop="showModal" title="ক্যাশ মেলান">
  @include('livewire.cash-counter-form')
</x-modal>

<div class="card mt">
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
    <div class="empty"><span class="ic">💵</span>এখনো ক্যাশ মেলানো হয়নি।<br>উপরের বোতামে নোট গুনে সংরক্ষণ করুন।</div>
  @endforelse
</div>

</div>
