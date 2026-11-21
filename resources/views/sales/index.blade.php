@extends('layouts.app')
@section('title', 'বিক্রি')
@section('content')
<div class="flex space-between">
  <h1>বিক্রি</h1>
  <div class="flex">
    <a class="btn ghost sm" href="{{ route('sales.export', request()->only(['from', 'to'])) }}">↓ CSV এক্সপোর্ট</a>
    <a class="btn" href="{{ route('sales.create') }}">+ নতুন বিক্রি</a>
  </div>
</div>
<div class="card">
  <form method="GET" class="field-row two">
    <div><label>শুরু</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>শেষ</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div><button class="btn sm mt" type="submit">ফিল্টার</button> <a class="btn ghost sm mt" href="{{ route('sales.index') }}">সব দেখুন</a></div>
  </form>
</div>
<div class="card">
  <table class="table">
    <thead><tr><th>তারিখ</th><th>কাস্টমার</th><th class="num">মোট</th><th class="num">ছাড়</th><th class="num">পরিশোধ</th><th class="num">বাকি</th><th></th></tr></thead>
    <tbody>
    @forelse($sales as $s)
      <tr>
        <td data-label="তারিখ">{{ $s->sale_date->format('d M Y') }}</td>
        <td data-label="কাস্টমার">{{ $s->customer?->name ?? 'খুচরা' }}</td>
        <td data-label="মোট" class="num">{{ \App\Support\Hisab::money($s->total) }}</td>
        <td data-label="ছাড়" class="num">@if((float) $s->discount_amount > 0)− {{ \App\Support\Hisab::money($s->discount_amount) }}@else —@endif</td>
        <td data-label="পরিশোধ" class="num">{{ \App\Support\Hisab::money($s->paid) }}</td>
        <td data-label="বাকি" class="num">@if($s->dueAmount() > 0)<span class="badge amber">{{ \App\Support\Hisab::money($s->dueAmount()) }}</span>@else <span class="badge green">পরিশোধিত</span>@endif</td>
        <td data-label=""><a href="{{ route('sales.show', $s) }}">রসিদ →</a></td>
      </tr>
    @empty
      <tr><td colspan="6" class="muted">কোনো বিক্রি পাওয়া যায়নি।</td></tr>
    @endforelse
    </tbody>
  </table>
  <div class="mt">{{ $sales->links() }}</div>
</div>
@endsection
