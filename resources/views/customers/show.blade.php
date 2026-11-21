@extends('layouts.app')
@section('title', $customer->name)
@section('content')
<div class="flex space-between">
  <h1>{{ $customer->name }}</h1>
  <a class="btn ghost" href="{{ route('customers.index') }}">← সব কাস্টমার</a>
</div>
<p class="muted">{{ $customer->phone ?? '' }} {{ $customer->address ? '· '.$customer->address : '' }}</p>
<div class="stat-grid">
  <div class="stat amber"><div class="label">মোট বাকি</div><div class="value">{{ \App\Support\Hisab::money($customer->dueAmount()) }}</div></div>
  <div class="stat"><div class="label">মোট কেনাকাটা</div><div class="value">{{ \App\Support\Hisab::money($customer->sales()->sum('total')) }}</div></div>
</div>

@if($customer->dueAmount() > 0)
<div class="card">
  <h2>টাকা আদায়</h2>
  <livewire:collection-form :customer-id="$customer->id" />
</div>
@endif

<div class="card">
  <h2>বিক্রির খতিয়ান</h2>
  @forelse($sales as $s)
    <div class="list-row">
      <div><a href="{{ route('sales.show', $s) }}">বিক্রি #{{ $s->id }}</a> · {{ $s->sale_date->format('d M Y') }}<br><span class="muted">মোট {{ \App\Support\Hisab::money($s->total) }} · পরিশোধ {{ \App\Support\Hisab::money($s->paid) }}</span></div>
      <span class="badge {{ $s->dueAmount() > 0 ? 'amber' : 'green' }}">বাকি {{ \App\Support\Hisab::money($s->dueAmount()) }}</span>
    </div>
  @empty
    <p class="muted">এই কাস্টমারের কোনো বিক্রি নেই।</p>
  @endforelse
  {{ $sales->links() }}
</div>

<div class="card">
  <h2>আদায়ের খতিয়ান</h2>
  @forelse($collections as $p)
    <div class="list-row">
      <div>{{ $p->date->format('d M Y') }} · {{ \App\Support\Hisab::paymentMethods()[$p->method] ?? $p->method }}@if($p->note)<br><span class="muted">{{ $p->note }}</span>@endif</div>
      <strong>+ {{ \App\Support\Hisab::money($p->amount) }}</strong>
    </div>
  @empty
    <p class="muted">এখনো কোনো আদায় হয়নি।</p>
  @endforelse
  {{ $collections->links() }}
</div>
@endsection
