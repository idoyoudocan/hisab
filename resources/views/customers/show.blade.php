@extends('layouts.app')
@section('title', $customer->name)
@section('content')
<div class="flex space-between">
  <h1>{{ $customer->name }}</h1>
  <div class="flex">
    <a class="btn ghost sm" href="{{ route('customers.export', $customer) }}">↓ খতিয়ান CSV</a>
    <a class="btn ghost" href="{{ route('customers.index') }}">← সব কাস্টমার</a>
  </div>
</div>
<p class="muted">{{ $customer->phone ?? '' }} {{ $customer->address ? '· '.$customer->address : '' }}</p>
@php($dueNow = $customer->dueAmount())
@php($ageNow = $customer->oldestDueDays())
<div class="stat-grid">
  <div class="stat amber"><div class="label">মোট বাকি</div><div class="value">{{ \App\Support\Hisab::money($dueNow) }}</div>@if($ageNow !== null)<div class="sub">সবচেয়ে পুরনো বাকি {{ $ageNow }} দিনের</div>@endif</div>
  <div class="stat"><div class="label">মোট কেনাকাটা</div><div class="value">{{ \App\Support\Hisab::money($customer->sales()->sum('total')) }}</div>@if($customer->credit_limit !== null)<div class="sub">ক্রেডিট সীমা {{ \App\Support\Hisab::money($customer->credit_limit) }}@if($customer->isOverLimit()) — <span class="badge red">সীমা ছাড়িয়েছে</span>@endif</div>@endif</div>
</div>
@if($dueNow > 0 && ($wa = \App\Support\Hisab::whatsappUrl($customer->phone, \App\Support\Hisab::dueReminderText($business, $customer->name, $dueNow))))
  <p><a class="btn dark" href="{{ $wa }}" target="_blank" rel="noopener">💬 WhatsApp-এ তাগাদা পাঠান ({{ \App\Support\Hisab::money($dueNow) }})</a></p>
@elseif($dueNow > 0)
  <p class="muted">ফোন নম্বর যোগ করলে WhatsApp-এ তাগাদা পাঠানো যাবে।</p>
@endif

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
      <div><a href="{{ route('sales.show', $s) }}">বিক্রি #{{ $s->id }}</a> · {{ $s->sale_date->format('d M Y') }} · <span class="muted">{{ $s->sale_date->diffInDays(now()) }} দিন আগে</span><br><span class="muted">মোট {{ \App\Support\Hisab::money($s->total) }} · পরিশোধ {{ \App\Support\Hisab::money($s->paid) }}</span></div>
      <span class="badge {{ $s->dueAmount() > 0 ? 'amber' : 'green' }}">বাকি {{ \App\Support\Hisab::money($s->dueAmount()) }}</span>
    </div>
  @empty
    <div class="empty"><span class="ic">🧾</span>এই কাস্টমারের কোনো বিক্রি নেই।</div>
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
