@extends('layouts.app')
@section('title', 'ড্যাশবোর্ড')
@section('content')
<div class="flex space-between">
  <h1>{{ $business->name }}</h1>
  @if($business->isTrialActive())
    <span class="badge gold">ফ্রি ট্রায়াল — {{ $business->trial_ends_at->diffForHumans() }} পর্যন্ত</span>
  @elseif($business->plan === 'trial')
    <span class="badge amber">ট্রায়াল শেষ — <a href="{{ route('packages.index') }}">প্যাকেজ দেখুন</a></span>
  @else
    <span class="badge green">{{ $business->currentPlanName() ?? 'পেইড' }} প্ল্যান</span>
  @endif
</div>

@if($business->subscriptionStatus() === 'trial' && ($business->trialDaysLeft() ?? 99) <= 3)
  <div class="trial-banner">⏰ ফ্রি ট্রায়াল শেষ হতে আর {{ $business->trialDaysLeft() }} দিন বাকি ({{ $business->trial_ends_at->format('d M Y') }})। এরপর প্যাকেজ ৳১৫০ থেকে শুরু — <a href="{{ route('packages.index') }}">প্যাকেজ দেখুন</a></div>
@elseif($business->subscriptionStatus() === 'expired')
  <div class="trial-banner urgent">⚠️ আপনার ট্রায়ালের মেয়াদ শেষ। প্যাকেজ নিতে পেমেন্ট করুন — <a href="{{ route('packages.index') }}">প্যাকেজ দেখুন</a></div>
@endif

{{-- Messages from the admin --}}
@foreach($notices as $notice)
  <div class="notice-banner">
    <div><strong>📩 অ্যাডমিনের বার্তা:</strong> {{ $notice->message }}<br><span class="muted">{{ $notice->created_at->format('d M Y, h:i A') }}</span></div>
    <form method="POST" action="{{ route('notices.read', $notice) }}">
      @csrf
      <button class="btn sm ghost" type="submit">পড়েছি</button>
    </form>
  </div>
@endforeach

<div class="hero-cash">
  <div class="eyebrow">ক্যাশে আছে</div>
  <div class="hero-amount">{{ \App\Support\Hisab::money($cash['balance']) }}</div>
  <div class="hero-sub">আজ বিক্রি {{ \App\Support\Hisab::money($todaySaleTotal) }} · আদায় {{ \App\Support\Hisab::money($todayCollections) }} · বাকি {{ \App\Support\Hisab::money($customerDue) }}</div>
  <div class="hero-actions">
    <a class="btn" href="{{ route('sales.create') }}">+ নতুন বিক্রি</a>
    @if($business->moduleEnabled('day_close'))
    <a class="btn ghost" href="{{ route('day-close.index') }}">দিনের হিসাব দেখুন</a>
    @endif
  </div>
</div>

<div class="insight"><strong>আজকের সারাংশ</strong> — {{ $insight }}।</div>

@if(!$checklistDone)
<div class="card checklist">
  <p class="section-label">শুরু করুন</p>
  <h2>তিন ধাপে দোকান চালু করুন</h2>
  <div class="list-row">
    <span>@if($checklist['products']) ✅ @else 1️⃣ @endif মাল যোগ করুন</span>
    @if($checklist['products'])<span class="badge green">হয়ে গেছে</span>@else<a href="{{ route('products.index') }}">যোগ করুন →</a>@endif
  </div>
  <div class="list-row">
    <span>@if($checklist['sales']) ✅ @else 2️⃣ @endif প্রথম বিক্রি করুন</span>
    @if($checklist['sales'])<span class="badge green">হয়ে গেছে</span>@else<a href="{{ route('sales.create') }}">বিক্রি করুন →</a>@endif
  </div>
  @if($business->moduleEnabled('cash_count'))
  <div class="list-row">
    <span>@if($checklist['cashCount']) ✅ @else 3️⃣ @endif ক্যাশ মেলান</span>
    @if($checklist['cashCount'])<span class="badge green">হয়ে গেছে</span>@else<a href="{{ route('cash-count.index') }}">মেলান →</a>@endif
  </div>
  @endif
  <p class="hint">তিনটি শেষ হলেই এই কার্ডটি আর দেখাবে না।</p>
</div>
@endif

<div class="stat-grid">
  <div class="stat">
    <span class="ic-chip">🛒</span>
    <div class="label">আজকের বিক্রি</div>
    <div class="value">{{ \App\Support\Hisab::money($todaySaleTotal) }}</div>
    <div class="sub">{{ $todaySales->count() }}টি বিক্রি</div>
  </div>
  <div class="stat">
    <span class="ic-chip">📈</span>
    <div class="label">আজকের লাভ</div>
    <div class="value">{{ \App\Support\Hisab::money($todayProfit) }}</div>
    <div class="sub">বিক্রয়মূল্য − কেনা দাম</div>
  </div>
  <div class="stat">
    <span class="ic-chip">📒</span>
    <div class="label">মোট বাকি</div>
    <div class="value">{{ \App\Support\Hisab::money($customerDue) }}</div>
    <div class="sub">সাপ্লায়ারকে দিতে হবে {{ \App\Support\Hisab::money($supplierDue) }}</div>
  </div>
  <div class="stat">
    <span class="ic-chip">💸</span>
    <div class="label">আজকের খরচ</div>
    <div class="value">{{ \App\Support\Hisab::money($todayExpense) }}</div>
    <div class="sub">আজকের কেনাকাটা {{ \App\Support\Hisab::money($todayPurchase) }}</div>
  </div>
</div>

<div class="quick-actions">
  <a class="btn dark qa" href="{{ route('sales.create') }}">+ বিক্রি</a>
  <a class="btn qa" href="{{ route('transactions.index') }}">+ খরচ</a>
  <a class="btn ghost qa" href="{{ route('customers.index') }}">+ আদায়</a>
  @if($business->moduleEnabled('cash_count'))
  <button type="button" class="btn ghost qa" onclick="window.dispatchEvent(new CustomEvent('open-cash-modal'));Livewire.dispatch('open-cash-modal')">💵 ক্যাশ মেলান</button>
  @endif
</div>

@if($lowStock->isNotEmpty())
<div class="card">
  <p class="section-label">সতর্কতা</p>
  <h2>কম স্টক — এখনই কিনুন</h2>
  @foreach($lowStock as $p)
    <div class="list-row">
      <div><strong>{{ $p->name }}</strong><br><span class="muted">{{ $p->unit }} · কেনা {{ \App\Support\Hisab::money($p->purchase_price) }}</span></div>
      <div style="display:flex;gap:8px;align-items:center">
        <span class="badge red">স্টক {{ (float) $p->stock }}</span>
        <a class="btn sm dark" href="{{ route('purchases.create') }}">কিনুন</a>
      </div>
    </div>
  @endforeach
  <a href="{{ route('products.index', ['low' => 1]) }}">সব কম স্টক দেখুন →</a>
</div>
@endif

<div class="card">
  <p class="section-label">সাম্প্রতিক</p>
  <h2>সাম্প্রতিক বিক্রি</h2>
  @forelse($recentSales as $s)
    <div class="list-row">
      <div>
        <a href="{{ route('sales.show', $s) }}"><strong>বিক্রি #{{ $s->id }}</strong></a>
        {{ $s->customer?->name ?? 'খুচরা কাস্টমার' }} · {{ $s->sale_date->format('d M, h:i A') }}
        @if($s->dueAmount() > 0) <span class="badge amber">বাকি {{ \App\Support\Hisab::money($s->dueAmount()) }}</span>@endif
      </div>
      <strong class="num">{{ \App\Support\Hisab::money($s->total) }}</strong>
    </div>
  @empty
    <div class="empty"><span class="ic">🧾</span>এখনো কোনো বিক্রি হয়নি।<br><a href="{{ route('sales.create') }}">প্রথম বিক্রি করুন →</a></div>
  @endforelse
</div>

<div class="card">
  <p class="section-label">স্বচ্ছ হিসাব</p>
  <h2>ক্যাশের হিসাব</h2>
  <div class="list-row"><span>খোলা ক্যাশ</span><span class="num">{{ \App\Support\Hisab::money($cash['opening_cash']) }}</span></div>
  <div class="list-row"><span>বিক্রি থেকে পাওয়া</span><span class="num">+ {{ \App\Support\Hisab::money($cash['sales_paid']) }}</span></div>
  <div class="list-row"><span>বাকি আদায়</span><span class="num">+ {{ \App\Support\Hisab::money($cash['collections']) }}</span></div>
  <div class="list-row"><span>অন্যান্য আয়</span><span class="num">+ {{ \App\Support\Hisab::money($cash['income']) }}</span></div>
  <div class="list-row"><span>কেনাকাটায় পরিশোধ</span><span class="num">− {{ \App\Support\Hisab::money($cash['purchases_paid']) }}</span></div>
  <div class="list-row"><span>সাপ্লায়ারকে পরিশোধ</span><span class="num">− {{ \App\Support\Hisab::money($cash['supplier_payments']) }}</span></div>
  <div class="list-row"><span>খরচ</span><span class="num">− {{ \App\Support\Hisab::money($cash['expenses']) }}</span></div>
  <div class="total-row"><span>ক্যাশে আছে</span><span class="num">{{ \App\Support\Hisab::money($cash['balance']) }}</span></div>
  <a href="{{ route('cashbook.index') }}">ক্যাশ বই দেখুন →</a>
  @if($business->moduleEnabled('cash_count')) · <a href="{{ route('cash-count.index') }}">ক্যাশ মেলান →</a>@endif
  @if($business->moduleEnabled('money_accounts')) · <a href="{{ route('money.index') }}">টাকার হিসাব দেখুন →</a>@endif
  @if($business->moduleEnabled('capital')) · <a href="{{ route('capital.index') }}">মূলধন দেখুন →</a>@endif
</div>
@endsection
