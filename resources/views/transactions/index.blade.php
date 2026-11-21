@extends('layouts.app')
@section('title', 'আয়-ব্যয়')
@section('content')
<div class="flex space-between">
  <h1>আয়-ব্যয়</h1>
  <a class="btn ghost sm" href="{{ route('transactions.export', request()->only(['month', 'type', 'scope'])) }}">↓ CSV এক্সপোর্ট</a>
</div>
<div class="stat-grid">
  <div class="stat green"><div class="label">এই মাসের আয়</div><div class="value">{{ \App\Support\Hisab::money($monthIncome) }}</div></div>
  <div class="stat red"><div class="label">এই মাসের খরচ</div><div class="value">{{ \App\Support\Hisab::money($monthExpense) }}</div></div>
  <div class="stat amber"><div class="label">ব্যক্তিগত খরচ (এই মাস)</div><div class="value">{{ \App\Support\Hisab::money($personalExpense) }}</div></div>
</div>
<livewire:transaction-manager />
<div class="card">
  <form method="GET" class="field-row three">
    <div><label>মাস</label><input type="month" name="month" value="{{ $month }}"></div>
    <div><label>ধরন</label><select name="type"><option value="">সব</option><option value="income" {{ request('type')==='income'?'selected':'' }}>আয়</option><option value="expense" {{ request('type')==='expense'?'selected':'' }}>ব্যয়</option></select></div>
    <div><label>খাত</label><select name="scope"><option value="">সব</option><option value="shop" {{ request('scope')==='shop'?'selected':'' }}>দোকান</option><option value="personal" {{ request('scope')==='personal'?'selected':'' }}>ব্যক্তিগত</option></select></div>
    <div><button class="btn sm mt" type="submit">ফিল্টার</button></div>
  </form>
  <table class="table mt">
    <thead><tr><th>তারিখ</th><th>ধরন</th><th>খাত</th><th>বিবরণ</th><th class="num">টাকা</th></tr></thead>
    <tbody>
    @forelse($transactions as $t)
      <tr>
        <td data-label="তারিখ">{{ $t->date->format('d M Y') }}</td>
        <td data-label="ধরন">@if($t->type==='income')<span class="badge green">আয়</span>@else <span class="badge red">ব্যয়</span>@endif</td>
        <td data-label="খাত">{{ $t->category }} · {{ $t->scope==='personal' ? 'ব্যক্তিগত' : 'দোকান' }}</td>
        <td data-label="বিবরণ">{{ $t->note ?? '—' }}</td>
        <td data-label="টাকা" class="num">{{ \App\Support\Hisab::money($t->amount) }}</td>
      </tr>
    @empty
      <tr><td colspan="5" class="muted">কোনো লেনদেন পাওয়া যায়নি।</td></tr>
    @endforelse
    </tbody>
  </table>
  <div class="mt">{{ $transactions->links() }}</div>
</div>
@endsection
