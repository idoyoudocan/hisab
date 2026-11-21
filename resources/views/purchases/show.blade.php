@extends('layouts.app')
@section('title', 'কেনাকাটা #' . $purchase->id)
@section('content')
<div class="flex space-between">
  <h1>কেনাকাটা #{{ $purchase->id }}</h1>
  <a class="btn ghost" href="{{ route('purchases.index') }}">← সব কেনাকাটা</a>
</div>
<div class="card">
  <div class="r-row" style="display:flex;justify-content:space-between;padding:6px 0"><span>তারিখ</span><span>{{ $purchase->purchase_date->format('d M Y') }}</span></div>
  <div class="r-row" style="display:flex;justify-content:space-between;padding:6px 0"><span>সাপ্লায়ার</span><span>@if($purchase->supplier)<a href="{{ route('suppliers.show', $purchase->supplier) }}">{{ $purchase->supplier->name }}</a>@else — @endif</span></div>
  @foreach($purchase->items as $item)
    <div class="list-row">
      <span>{{ $item->product->name }} × {{ (float) $item->qty }} {{ $item->product->unit }} @ {{ \App\Support\Hisab::money($item->price) }}</span>
      <strong>{{ \App\Support\Hisab::money($item->total) }}</strong>
    </div>
  @endforeach
  <div class="total-row"><span>মোট</span><span>{{ \App\Support\Hisab::money($purchase->total) }}</span></div>
  <div class="list-row"><span>পরিশোধ</span><span>{{ \App\Support\Hisab::money($purchase->paid) }}</span></div>
  <div class="list-row"><span>বাকি</span><span>@if($purchase->dueAmount() > 0){{ \App\Support\Hisab::money($purchase->dueAmount()) }}@else ৳০ (পরিশোধিত)@endif</span></div>
  @if($purchase->note)<p class="muted mt">নোট: {{ $purchase->note }}</p>@endif
</div>
@if($purchase->supplier && $purchase->dueAmount() > 0)
  <p class="mt"><a class="btn" href="{{ route('suppliers.show', $purchase->supplier) }}">সাপ্লায়ারকে পরিশোধ করুন →</a></p>
@endif
@endsection
