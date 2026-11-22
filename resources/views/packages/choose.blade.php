@extends('layouts.app')
@section('title', 'প্ল্যান নিন — '.$plan['name'])
@section('content')
<h1>{{ $plan['name'] }} প্ল্যান নিন</h1>
<p class="muted">⏰ সীমিত সময়ের অফার — এই ছাড়ের দাম যেকোনো সময় শেষ হতে পারে।</p>

@if($pending)
  <div class="card" style="border:2px solid var(--gold)">
    <h2>⏳ অনুরোধ ইতিমধ্যেই পাঠানো আছে</h2>
    <p class="muted">আপনার <strong>{{ $pending->planName() }} প্ল্যান</strong> ({{ \App\Support\Hisab::money($pending->amount) }})-এর অনুরোধটি {{ $pending->created_at->format('d M Y') }} তারিখে পাঠানো হয়েছে। পেমেন্টের পর অ্যাডমিন আপনার মেয়াদ বাড়িয়ে দেবেন — নতুন করে অনুরোধ পাঠাতে হবে না।</p>
    <a class="btn dark" href="{{ route('packages.index') }}">প্যাকেজ দেখুন</a>
  </div>
  @include('packages.partials.pay-box')
@else
  <div class="card">
    <p class="section-label">{{ $plan['name'] }} · {{ $plan['months'] }} মাস</p>
    <div class="list-row"><span>নিয়মিত মূল্য</span><strong class="price-old">{{ \App\Support\Hisab::money($plan['regular']) }}</strong></div>
    <div class="list-row"><span>ছাড়</span><strong>{{ $plan['discount'] }}% — সাশ্রয় {{ \App\Support\Hisab::money($plan['savings']) }}</strong></div>
    <div class="list-row"><span>দিতে হবে</span><strong style="font-size:22px;color:var(--pine)">{{ \App\Support\Hisab::money($plan['payable']) }}</strong></div>
    <div class="list-row"><span>মাসপ্রতি</span><strong>{{ \App\Support\Hisab::money($plan['per_month']) }}</strong></div>
    <p class="muted">সব ফিচার অন্তর্ভুক্ত। পেমেন্ট অফলাইনে করতে হয় — অনুরোধ পাঠালে অ্যাডমিন পেমেন্ট পাওয়ার পর আপনার মেয়াদ {{ $plan['months'] }} মাস বাড়িয়ে দেবেন।</p>
    @include('packages.partials.pay-box')
    <form method="POST" action="{{ route('packages.request', $plan['slug']) }}">
      @csrf
      <button class="btn block" type="submit">অনুরোধ পাঠান — {{ \App\Support\Hisab::money($plan['payable']) }}</button>
    </form>
    <p class="mt" style="text-align:center"><a href="{{ route('packages.index') }}">← প্যাকেজে ফিরুন</a></p>
  </div>
@endif
@endsection
