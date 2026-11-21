@extends('layouts.app')
@section('title', 'বিক্রি #' . $sale->id)
@section('content')
<div class="no-print flex space-between">
  <h1>বিক্রি #{{ $sale->id }}</h1>
  <div class="flex">
    <a class="btn ghost" href="{{ route('sales.index') }}">← সব বিক্রি</a>
    <button class="btn" type="button" onclick="window.print()">🖨️ রসিদ প্রিন্ট</button>
  </div>
</div>
<div class="receipt">
  <h2>{{ $business->name }}</h2>
  <p style="text-align:center" class="muted">{{ $business->phone ?? '' }} {{ $business->address ? '· '.$business->address : '' }}</p>
  <div class="r-row"><span>তারিখ</span><span>{{ $sale->sale_date->format('d M Y') }}</span></div>
  <div class="r-row"><span>কাস্টমার</span><span>{{ $sale->customer?->name ?? 'খুচরা কাস্টমার' }}</span></div>
  <div class="r-row"><span>মাধ্যম</span><span>{{ \App\Support\Hisab::paymentMethods()[$sale->payment_method] ?? $sale->payment_method }}</span></div>
  @foreach($sale->items as $item)
    <div class="r-row">
      <span>{{ $item->product->name }} × {{ (float) $item->qty }} {{ $item->product->unit }}</span>
      <span>{{ \App\Support\Hisab::money($item->total) }}</span>
    </div>
  @endforeach
  <div class="r-row"><strong>মোট</strong><strong>{{ \App\Support\Hisab::money($sale->total) }}</strong></div>
  <div class="r-row"><span>পরিশোধ</span><span>{{ \App\Support\Hisab::money($sale->paid) }}</span></div>
  <div class="r-row"><span>বাকি</span><span>@if($sale->dueAmount() > 0){{ \App\Support\Hisab::money($sale->dueAmount()) }}@else ৳০ (পরিশোধিত)@endif</span></div>
  @if($sale->note)<p class="muted mt">নোট: {{ $sale->note }}</p>@endif
  <p style="text-align:center" class="muted mt">ধন্যবাদ — আবার আসবেন!</p>
</div>
@if($sale->customer && $sale->dueAmount() > 0)
  <p class="mt no-print"><a class="btn" href="{{ route('customers.show', $sale->customer) }}">বাকি আদায় করুন →</a></p>
@endif
@endsection
