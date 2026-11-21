@extends('layouts.app')
@section('title', 'কেনাকাটা')
@section('content')
<div class="flex space-between">
  <h1>কেনাকাটা</h1>
  <a class="btn" href="{{ route('purchases.create') }}">+ নতুন কেনাকাটা</a>
</div>
<livewire:supplier-manager />
<div class="card">
  <form method="GET" class="field-row two">
    <div><label>শুরু</label><input type="date" name="from" value="{{ request('from') }}"></div>
    <div><label>শেষ</label><input type="date" name="to" value="{{ request('to') }}"></div>
    <div><button class="btn sm mt" type="submit">ফিল্টার</button> <a class="btn ghost sm mt" href="{{ route('purchases.index') }}">সব দেখুন</a></div>
  </form>
</div>
<div class="card">
  <table class="table">
    <thead><tr><th>তারিখ</th><th>সাপ্লায়ার</th><th class="num">মোট</th><th class="num">পরিশোধ</th><th class="num">বাকি</th><th></th></tr></thead>
    <tbody>
    @forelse($purchases as $p)
      <tr>
        <td data-label="তারিখ">{{ $p->purchase_date->format('d M Y') }}</td>
        <td data-label="সাপ্লায়ার">@if($p->supplier)<a href="{{ route('suppliers.show', $p->supplier) }}">{{ $p->supplier->name }}</a>@else — @endif</td>
        <td data-label="মোট" class="num">{{ \App\Support\Hisab::money($p->total) }}</td>
        <td data-label="পরিশোধ" class="num">{{ \App\Support\Hisab::money($p->paid) }}</td>
        <td data-label="বাকি" class="num">@if($p->dueAmount() > 0)<span class="badge amber">{{ \App\Support\Hisab::money($p->dueAmount()) }}</span>@else <span class="badge green">পরিশোধিত</span>@endif</td>
        <td data-label=""><a href="{{ route('purchases.show', $p) }}">বিস্তারিত →</a></td>
      </tr>
    @empty
      <tr><td colspan="6" class="muted">কোনো কেনাকাটা পাওয়া যায়নি।</td></tr>
    @endforelse
    </tbody>
  </table>
  <div class="mt">{{ $purchases->links() }}</div>
</div>
@endsection
