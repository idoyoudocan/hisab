<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>প্যাকেজ — হিসাব</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css').'?v='.filemtime(public_path('css/app.css')) }}">
@include('partials.pwa-head')
</head>
<body style="padding-bottom:0">
<header class="site">
  <div class="header-inner">
    <a class="brand" href="{{ route('landing') }}"><span class="brand-mark"><i>৳</i></span><span>হিসাব<small>দোকানের হিসাব-নিকাশ</small></span></a>
    <span style="margin-left:auto"></span>
    <a href="{{ route('packages.index') }}" class="hide-mobile" style="font-weight:600">প্যাকেজ</a>
    @auth
      <a class="btn sm dark" href="{{ route('dashboard') }}">ড্যাশবোর্ডে যান</a>
    @else
      <a href="{{ route('login') }}" style="font-weight:600">লগইন</a>
      <a class="btn sm" href="{{ route('register') }}">ফ্রি শুরু করুন</a>
    @endauth
  </div>
</header>
<main class="container">
  @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif

  <p class="section-label" style="text-align:center">প্যাকেজ</p>
  <h1 style="text-align:center;font-size:24px">যত বড় প্যাকেজ, তত বেশি ছাড়</h1>
  <p style="text-align:center" class="muted">সব প্যাকেজে সব ফিচার — আনলিমিটেড মাল, কাস্টমার আর লেনদেন। পার্থক্য শুধু মেয়াদ আর ছাড়ে: বড় প্যাকেজ নিলে মাসপ্রতি খরচ কমে, আপনারও লাভ, আমাদেরও।</p>

  <div class="offer-banner">🎉 সীমিত সময়ের অফার — এই ছাড়ের দাম যেকোনো সময় শেষ হতে পারে, এখনই প্ল্যান নিন।</div>

  @if($pending)
    <div class="card" style="border:2px solid var(--gold);text-align:center">
      <h2>⏳ অনুরোধ পাঠানো হয়েছে</h2>
      <p class="muted">আপনার <strong>{{ $pending->planName() }} প্ল্যান</strong> ({{ \App\Support\Hisab::money($pending->amount) }})-এর অনুরোধটি {{ $pending->created_at->format('d M Y') }} তারিখে পাঠানো হয়েছে। পেমেন্টের পর অ্যাডমিন আপনার মেয়াদ বাড়িয়ে দেবেন।</p>
    </div>
    @include('packages.partials.pay-box')
  @endif

  <div class="pricing">
    @foreach($plans as $plan)
      <div class="price-card {{ $plan['badge'] === 'সবচেয়ে জনপ্রিয়' ? 'featured' : '' }}">
        @if($plan['badge'])<span class="plan-badge">{{ $plan['badge'] }}</span>@endif
        <span class="offer-tag">⏰ সীমিত সময়ের অফার</span>
        <p class="eyebrow">{{ $plan['name'] }} · {{ $plan['months'] }} মাস</p>
        <p class="real-price-label">আসল দাম</p>
        <p class="real-price">{{ \App\Support\Hisab::money($plan['regular']) }}</p>
        <p><span class="discount-badge">{{ $plan['discount'] }}% ছাড়</span></p>
        <p class="pay-label">আপনি দেবেন</p>
        <div class="price">{{ \App\Support\Hisab::money($plan['payable']) }}</div>
        <p class="price-save">✓ আপনার সাশ্রয় {{ \App\Support\Hisab::money($plan['savings']) }}</p>
        <p class="price-per">মাসে মাত্র {{ \App\Support\Hisab::money($plan['per_month']) }}</p>
        <p class="muted">সব ফিচার অন্তর্ভুক্ত</p>
        @if($pending && $pending->plan_slug === $plan['slug'])
          <a class="btn ghost block" href="{{ route('packages.choose', $plan['slug']) }}">অনুরোধ পাঠানো হয়েছে ✓</a>
        @else
          <a class="btn {{ $plan['badge'] === 'সবচেয়ে জনপ্রিয়' ? '' : 'dark' }} block" href="{{ route('packages.choose', $plan['slug']) }}">এই প্ল্যান নিন</a>
        @endif
      </div>
    @endforeach
  </div>

  <p class="muted mt" style="text-align:center">পেমেন্ট অফলাইনে করতে হয় — প্ল্যান বেছে অনুরোধ পাঠান, পেমেন্টের পর অ্যাডমিন আপনার মেয়াদ বাড়িয়ে দেবেন। প্রথমে ১৪ দিন ফ্রি ট্রায়াল দিয়ে শুরু করতে পারেন।</p>
  <p style="text-align:center"><a href="{{ route('landing') }}">← মূল পাতায় ফিরুন</a></p>
</main>
<footer class="site">© {{ date('Y') }} হিসাব — troxela.store পরিবারের পণ্য</footer>
@include('partials.pwa-banner')
</body>
</html>
