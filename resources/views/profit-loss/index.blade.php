@extends('layouts.app')
@section('title', 'লাভ-ক্ষতি')
@section('content')
<h1>লাভ-ক্ষতির বিবরণী</h1>
<div class="card">
  <form method="GET" class="flex">
    <div style="flex:1"><label>মাস</label><input type="month" name="month" value="{{ $month }}"></div>
    <div><button class="btn sm mt" type="submit">দেখুন</button></div>
  </form>
</div>

<div class="card">
  <p class="section-label">{{ \Carbon\Carbon::parse($month.'-01')->format('F Y') }}</p>
  <div class="list-row"><span>বিক্রি থেকে আয় (ছাড়ের পর)</span><span class="num">{{ \App\Support\Hisab::money($revenue) }}</span></div>
  @if($discounts > 0)
  <div class="list-row"><span class="muted">এর মধ্যে ছাড় দেওয়া হয়েছে</span><span class="num muted">− {{ \App\Support\Hisab::money($discounts) }}</span></div>
  @endif
  <div class="list-row"><span>বিক্রি করা মালের কেনা দাম</span><span class="num">− {{ \App\Support\Hisab::money($cogs) }}</span></div>
  <div class="total-row"><span>মোট লাভ</span><span class="num">{{ \App\Support\Hisab::money($grossProfit) }}</span></div>
  <p class="muted">মোট লাভের হার {{ $margin }}%</p>
</div>

<div class="card">
  <p class="section-label">অন্যান্য</p>
  <div class="list-row"><span>অন্যান্য আয়</span><span class="num">+ {{ \App\Support\Hisab::money($otherIncome) }}</span></div>
  <div class="list-row"><span>দোকানের খরচ</span><span class="num">− {{ \App\Support\Hisab::money($shopExpense) }}</span></div>
  <div class="total-row"><span>নিট লাভ (ব্যবসা)</span><span class="num">{{ \App\Support\Hisab::money($netProfit) }}</span></div>
</div>

<div class="card">
  <p class="section-label">মালিকের হিসাব</p>
  <div class="list-row"><span>ব্যক্তিগত খরচ (নিট লাভের বাইরে)</span><span class="num">− {{ \App\Support\Hisab::money($personalExpense) }}</span></div>
  <p class="muted">ব্যক্তিগত খরচ ব্যবসার লাভ কমায় না — এটি মালিকের নিজের টাকা তোলা হিসেবে ধরা হয়।</p>
</div>
@endsection
