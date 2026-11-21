@extends('layouts.app')
@section('title', 'আমার মূলধন')
@section('content')
<h1>আমার মূলধন</h1>
<p class="muted">আপনার দোকানের মোট মূলধন — হাতে থাকা টাকা, ব্যাংক ও মোবাইল ব্যালেন্স, মজুদ মালের কেনা দাম আর বাকি পাওনা যোগ করে, সাপ্লায়ারের পাওনা বাদ দিয়ে। সব সংখ্যা লাইভ হিসাব থেকে ধরা হয়, কোথাও জমিয়ে রাখা হয় না।</p>

<div class="hero-cash">
  <div class="eyebrow">মোট মূলধন</div>
  <div class="hero-amount">{{ \App\Support\Hisab::money($capital['total']) }}</div>
  <div class="hero-sub">{{ $business->name }} · আজকের হিসাব অনুযায়ী</div>
</div>

<div class="card">
  <p class="section-label">মূলধনের হিসাব</p>
  <h2>যা যা ধরা হয়েছে</h2>
  <div class="list-row"><span>নগদ — ক্যাশ রেজিস্টার</span><span class="num">+ {{ \App\Support\Hisab::money($capital['cash']) }}</span></div>
  <div class="list-row"><span>ব্যাংক অ্যাকাউন্ট মোট</span><span class="num">+ {{ \App\Support\Hisab::money($capital['bank']) }}</span></div>
  <div class="list-row"><span>মোবাইল ব্যাংকিং মোট (বিকাশ, নগদ, রকেট…)</span><span class="num">+ {{ \App\Support\Hisab::money($capital['mobile_banking']) }}</span></div>
  <div class="list-row"><span>সিম ব্যালেন্স মোট</span><span class="num">+ {{ \App\Support\Hisab::money($capital['sim']) }}</span></div>
  <div class="list-row"><span>অন্যান্য অ্যাকাউন্ট</span><span class="num">+ {{ \App\Support\Hisab::money($capital['other']) }}</span></div>
  <div class="list-row"><span>মজুদ মালের কেনা দাম</span><span class="num">+ {{ \App\Support\Hisab::money($capital['stock_value']) }}</span></div>
  <div class="list-row"><span>বাকি পাওনা (কাস্টমারদের কাছে)</span><span class="num">+ {{ \App\Support\Hisab::money($capital['customer_due']) }}</span></div>
  <div class="list-row"><span>সাপ্লায়ার পাওনা (দিতে হবে)</span><span class="num">− {{ \App\Support\Hisab::money($capital['supplier_due']) }}</span></div>
  <div class="total-row"><span>মোট মূলধন</span><span class="num">{{ \App\Support\Hisab::money($capital['total']) }}</span></div>
</div>

<div class="card">
  <p class="section-label">বিস্তারিত</p>
  <h2>প্রতিটি অংশ দেখুন</h2>
  <div class="list-row"><span>💵 নগদ মেলান</span><a href="{{ route('cash-count.index') }}">ক্যাশ মেলান →</a></div>
  <div class="list-row"><span>🏦 ব্যাংক ও মোবাইল ব্যালেন্স</span><a href="{{ route('money.index') }}">টাকার হিসাব →</a></div>
  <div class="list-row"><span>📦 মজুদ মাল</span><a href="{{ route('products.index') }}">মজুদ দেখুন →</a></div>
  <div class="list-row"><span>📒 বাকি পাওনা</span><a href="{{ route('customers.index') }}">কাস্টমার দেখুন →</a></div>
  <div class="list-row"><span>📦 সাপ্লায়ার পাওনা</span><a href="{{ route('purchases.index') }}">কেনাকাটা দেখুন →</a></div>
</div>
@endsection
