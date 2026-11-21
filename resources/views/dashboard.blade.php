@extends('layouts.app')
@section('title', 'ড্যাশবোর্ড')
@section('content')
<div class="flex space-between">
  <h1>{{ $business->name }}</h1>
  @if($business->isTrialActive())
    <span class="badge gold">ফ্রি ট্রায়াল — {{ $business->trial_ends_at->diffForHumans() }} পর্যন্ত</span>
  @elseif($business->plan === 'trial')
    <span class="badge amber">ট্রায়াল শেষ — প্রো নিন ৳৩০০/মাস</span>
  @else
    <span class="badge green">প্রো প্ল্যান</span>
  @endif
</div>

<div class="hero-cash">
  <div class="eyebrow">ক্যাশে আছে</div>
  <div class="hero-amount">{{ \App\Support\Hisab::money($cash['balance']) }}</div>
  <div class="hero-sub">আজ বিক্রি {{ \App\Support\Hisab::money($todaySaleTotal) }} · আদায় {{ \App\Support\Hisab::money($todayCollections) }} · বাকি {{ \App\Support\Hisab::money($customerDue) }}</div>
  <div class="hero-actions">
    <a class="btn" href="{{ route('sales.create') }}">+ নতুন বিক্রি</a>
    <a class="btn ghost" href="{{ route('day-close.index') }}">দিনের হিসাব দেখুন</a>
  </div>
</div>

<div class="insight"><strong>আজকের সারাংশ</strong> — {{ $insight }}।</div>

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

<div class="flex" style="margin-bottom:16px">
  <a class="btn dark" href="{{ route('sales.create') }}">+ নতুন বিক্রি</a>
  <a class="btn ghost" href="{{ route('purchases.create') }}">+ নতুন কেনাকাটা</a>
  <a class="btn ghost" href="{{ route('transactions.index') }}">+ আয়-ব্যয়</a>
  <a class="btn ghost" href="{{ route('profit-loss.index') }}">লাভ-ক্ষতি</a>
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
</div>
@endsection
