@extends('layouts.app')
@section('title', 'ক্যাশ বই')
@section('content')
<h1>ক্যাশ বই</h1>
<div class="stat-grid">
  <div class="stat"><div class="label">শুরুর ব্যালান্স ({{ $from }})</div><div class="value">{{ \App\Support\Hisab::money($opening) }}</div></div>
  <div class="stat green"><div class="label">শেষ ব্যালান্স ({{ $to }})</div><div class="value">{{ \App\Support\Hisab::money($closing) }}</div></div>
</div>
<div class="card">
  <form method="GET" class="field-row two">
    <div><label>শুরু</label><input type="date" name="from" value="{{ $from }}"></div>
    <div><label>শেষ</label><input type="date" name="to" value="{{ $to }}"></div>
    <div><button class="btn sm mt" type="submit">দেখুন</button></div>
  </form>
</div>
<div class="card">
  <table class="table">
    <thead><tr><th>তারিখ</th><th>বিবরণ</th><th class="num">জমা</th><th class="num">খরচ</th><th class="num">ব্যালান্স</th></tr></thead>
    <tbody>
    @forelse($rows as $r)
      <tr>
        <td data-label="তারিখ">{{ $r['date'] }}</td>
        <td data-label="বিবরণ">{{ $r['label'] }}<br><span class="muted">{{ $r['ref'] }}</span></td>
        <td data-label="জমা" class="num">@if($r['in'] > 0)+ {{ \App\Support\Hisab::money($r['in']) }}@else — @endif</td>
        <td data-label="খরচ" class="num">@if($r['out'] > 0)− {{ \App\Support\Hisab::money($r['out']) }}@else — @endif</td>
        <td data-label="ব্যালান্স" class="num"><strong>{{ \App\Support\Hisab::money($r['balance']) }}</strong></td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted">এই সময়ে কোনো ক্যাশ লেনদেন নেই।</td></tr>
    @endforelse
    </tbody>
  </table>
</div>
@endsection
