@extends('layouts.app')
@section('title', 'রিপোর্ট')
@section('content')
<h1>রিপোর্ট</h1>
<div class="tabs">
  <a href="{{ route('reports.index') }}" class="active">সারাংশ</a>
  <a href="{{ route('profit-loss.index') }}">লাভ-ক্ষতির বিবরণী</a>
  <a href="{{ route('day-close.index') }}">দিনের হিসাব</a>
  <a href="{{ route('cashbook.index') }}">ক্যাশ বই</a>
</div>
<div class="card">
  <form method="GET" class="field-row two">
    <div><label>শুরু</label><input type="date" name="from" value="{{ $from }}"></div>
    <div><label>শেষ</label><input type="date" name="to" value="{{ $to }}"></div>
    <div><button class="btn sm mt" type="submit">দেখুন</button></div>
  </form>
</div>
<div class="stat-grid">
  <div class="stat green"><div class="label">মোট বিক্রি</div><div class="value">{{ \App\Support\Hisab::money($salesTotal) }}</div><div class="sub">পাওয়া {{ \App\Support\Hisab::money($salesPaid) }} · বাকি {{ \App\Support\Hisab::money($salesDue) }}</div></div>
  <div class="stat"><div class="label">লাভ (আনুমানিক)</div><div class="value">{{ \App\Support\Hisab::money($profit) }}</div><div class="sub">বিক্রয়মূল্য − কেনা দাম</div></div>
  <div class="stat"><div class="label">মোট কেনাকাটা</div><div class="value">{{ \App\Support\Hisab::money($purchaseTotal) }}</div></div>
  <div class="stat red"><div class="label">মোট খরচ</div><div class="value">{{ \App\Support\Hisab::money($expense) }}</div><div class="sub">দোকান {{ \App\Support\Hisab::money($shopExpense) }} · ব্যক্তিগত {{ \App\Support\Hisab::money($personalExpense) }}</div></div>
</div>
<div class="stat-grid">
  <div class="stat"><div class="label">অন্যান্য আয়</div><div class="value">{{ \App\Support\Hisab::money($income) }}</div></div>
  <div class="stat"><div class="label">বাকি আদায়</div><div class="value">{{ \App\Support\Hisab::money($collections) }}</div></div>
  <div class="stat"><div class="label">সাপ্লায়ারকে পরিশোধ</div><div class="value">{{ \App\Support\Hisab::money($supplierPaid) }}</div></div>
</div>
<div class="card">
  <h2>দিনভিত্তিক হিসাব</h2>
  <table class="table">
    <thead><tr><th>তারিখ</th><th class="num">বিক্রি</th><th class="num">কেনাকাটা</th><th class="num">খরচ</th></tr></thead>
    <tbody>
    @foreach($daily as $d)
      <tr>
        <td data-label="তারিখ">{{ $d['date'] }}</td>
        <td data-label="বিক্রি" class="num">{{ \App\Support\Hisab::money($d['sales']) }}</td>
        <td data-label="কেনাকাটা" class="num">{{ \App\Support\Hisab::money($d['purchases']) }}</td>
        <td data-label="খরচ" class="num">{{ \App\Support\Hisab::money($d['expense']) }}</td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endsection
