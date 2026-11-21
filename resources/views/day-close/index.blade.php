@extends('layouts.app')
@section('title', 'দিন শেষ')
@section('content')
<div class="no-print flex space-between">
  <h1>দিনের হিসাব</h1>
  <button class="btn dark" type="button" onclick="window.print()">🖨️ প্রিন্ট</button>
</div>
<div class="no-print card">
  <form method="GET" class="flex">
    <div style="flex:1"><label>তারিখ</label><input type="date" name="date" value="{{ $date }}"></div>
    <div><button class="btn sm mt" type="submit">দেখুন</button></div>
  </form>
</div>

<div class="hero-cash">
  <div class="eyebrow">দিন শেষে ক্যাশে থাকবে</div>
  <div class="hero-amount">{{ \App\Support\Hisab::money($closing) }}</div>
  <div class="hero-sub">{{ \Carbon\Carbon::parse($date)->format('d M Y') }} · {{ $business->name }}</div>
</div>

<div class="card">
  <p class="section-label">দিনের শুরু</p>
  <div class="list-row"><span>দিনের শুরুর ক্যাশ</span><span class="num">{{ \App\Support\Hisab::money($opening) }}</span></div>
</div>

<div class="card">
  <p class="section-label">টাকা এলো</p>
  <div class="list-row"><span>বিক্রি — নগদ পাওয়া ({{ $salesCount }}টি বিক্রি)</span><span class="num">+ {{ \App\Support\Hisab::money($salesPaid) }}</span></div>
  <div class="list-row"><span>বাকি আদায়</span><span class="num">+ {{ \App\Support\Hisab::money($collections) }}</span></div>
  <div class="list-row"><span>অন্যান্য আয়</span><span class="num">+ {{ \App\Support\Hisab::money($income) }}</span></div>
  <div class="total-row"><span>মোট এলো</span><span class="num">{{ \App\Support\Hisab::money($cashIn) }}</span></div>
  <p class="muted">মোট বিক্রি {{ \App\Support\Hisab::money($salesTotal) }} — এর মধ্যে বাকি {{ \App\Support\Hisab::money($salesDue) }} (পরে আদায় হবে)।</p>
</div>

<div class="card">
  <p class="section-label">টাকা গেলো</p>
  <div class="list-row"><span>কেনাকাটায় পরিশোধ</span><span class="num">− {{ \App\Support\Hisab::money($purchasesPaid) }}</span></div>
  <div class="list-row"><span>সাপ্লায়ারকে পরিশোধ</span><span class="num">− {{ \App\Support\Hisab::money($supplierPaid) }}</span></div>
  <div class="list-row"><span>দোকানের খরচ</span><span class="num">− {{ \App\Support\Hisab::money($shopExpense) }}</span></div>
  <div class="list-row"><span>ব্যক্তিগত খরচ</span><span class="num">− {{ \App\Support\Hisab::money($personalExpense) }}</span></div>
  <div class="total-row"><span>মোট গেলো</span><span class="num">{{ \App\Support\Hisab::money($cashOut) }}</span></div>
</div>

<div class="card">
  <div class="total-row"><span>দিন শেষে ক্যাশ</span><span class="num">{{ \App\Support\Hisab::money($closing) }}</span></div>
  <p class="muted">হিসাব: শুরুর ক্যাশ {{ \App\Support\Hisab::money($opening) }} + এলো {{ \App\Support\Hisab::money($cashIn) }} − গেলো {{ \App\Support\Hisab::money($cashOut) }}</p>
</div>
@endsection
