@extends('layouts.app')
@section('title', $supplier->name)
@section('content')
<div class="flex space-between">
  <h1>{{ $supplier->name }}</h1>
  <a class="btn ghost" href="{{ route('purchases.index') }}">← কেনাকাটা</a>
</div>
<p class="muted">{{ $supplier->phone ?? '' }} {{ $supplier->address ? '· '.$supplier->address : '' }}</p>
<div class="stat-grid">
  <div class="stat amber"><div class="label">সাপ্লায়ারকে দিতে হবে</div><div class="value">{{ \App\Support\Hisab::money($supplier->dueAmount()) }}</div></div>
  <div class="stat"><div class="label">মোট কেনাকাটা</div><div class="value">{{ \App\Support\Hisab::money($supplier->purchases()->sum('total')) }}</div></div>
</div>

@if($supplier->dueAmount() > 0)
<div class="card">
  <h2>পরিশোধ করুন</h2>
  <livewire:supplier-payment-form :supplier-id="$supplier->id" />
</div>
@endif

<div class="card">
  <h2>কেনাকাটার খতিয়ান</h2>
  @forelse($purchases as $p)
    <div class="list-row">
      <div><a href="{{ route('purchases.show', $p) }}">কেনাকাটা #{{ $p->id }}</a> · {{ $p->purchase_date->format('d M Y') }}<br><span class="muted">মোট {{ \App\Support\Hisab::money($p->total) }} · পরিশোধ {{ \App\Support\Hisab::money($p->paid) }}</span></div>
      <span class="badge {{ $p->dueAmount() > 0 ? 'amber' : 'green' }}">বাকি {{ \App\Support\Hisab::money($p->dueAmount()) }}</span>
    </div>
  @empty
    <p class="muted">এই সাপ্লায়ারের কাছ থেকে এখনো কেনা হয়নি।</p>
  @endforelse
  {{ $purchases->links() }}
</div>

<div class="card">
  <h2>পরিশোধের খতিয়ান</h2>
  @forelse($payments as $p)
    <div class="list-row">
      <div>{{ $p->date->format('d M Y') }} · {{ \App\Support\Hisab::paymentMethods()[$p->method] ?? $p->method }}@if($p->note)<br><span class="muted">{{ $p->note }}</span>@endif</div>
      <strong>− {{ \App\Support\Hisab::money($p->amount) }}</strong>
    </div>
  @empty
    <p class="muted">এখনো কোনো পরিশোধ হয়নি।</p>
  @endforelse
  {{ $payments->links() }}
</div>
@endsection
