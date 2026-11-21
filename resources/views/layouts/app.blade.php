<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'হিসাব') — হিসাব</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
@livewireStyles
</head>
<body>
<header class="site">
  <div class="header-inner">
    <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark"><i>৳</i></span><span>হিসাব<small>দোকানের হিসাব-নিকাশ</small></span></a>
    @auth
      @php($biz = auth()->user()->business)
      @if($biz)
        <span class="cash-badge">ক্যাশ: {{ \App\Support\Hisab::money(\App\Support\Hisab::cashBalance($biz)) }}</span>
        <span class="header-user">{{ $biz->name }}</span>
      @endif
    @endauth
  </div>
  @auth
  <nav class="top" aria-label="মূল মেনু">
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">ড্যাশবোর্ড</a>
    <a href="{{ route('sales.index') }}" class="{{ request()->routeIs('sales.*') ? 'active' : '' }}">বিক্রি</a>
    <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">বাকি</a>
    <a href="{{ route('purchases.index') }}" class="{{ request()->routeIs('purchases.*') ? 'active' : '' }}">কেনাকাটা</a>
    <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">মজুদ</a>
    <a href="{{ route('transactions.index') }}" class="{{ request()->routeIs('transactions.*') ? 'active' : '' }}">আয়-ব্যয়</a>
    <a href="{{ route('cashbook.index') }}" class="{{ request()->routeIs('cashbook.*') ? 'active' : '' }}">ক্যাশ বই</a>
    <a href="{{ route('day-close.index') }}" class="{{ request()->routeIs('day-close.*') ? 'active' : '' }}">দিন শেষ</a>
    <a href="{{ route('reports.index') }}" class="{{ request()->routeIs('reports.*') ? 'active' : '' }}">রিপোর্ট</a>
    <a href="{{ route('profit-loss.index') }}" class="{{ request()->routeIs('profit-loss.*') ? 'active' : '' }}">লাভ-ক্ষতি</a>
    <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">সেটিংস</a>
    <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();">লগআউট</a>
  </nav>
  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
  @endauth
</header>
<main class="container">
  @if(session('status'))<div class="flash">{{ session('status') }}</div>@endif
  @if($errors->any())
    <div class="flash err">
      @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
  @endif
  @yield('content')
</main>
@auth
<nav class="mobile" aria-label="মোবাইল মেনু">
  <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><span class="ic">🏠</span>ড্যাশবোর্ড</a>
  <a href="{{ route('sales.create') }}" class="{{ request()->routeIs('sales.create') ? 'active' : '' }}"><span class="ic">🛒</span>বিক্রি</a>
  <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}"><span class="ic">📒</span>বাকি</a>
  <a href="{{ route('purchases.create') }}" class="{{ request()->routeIs('purchases.create') ? 'active' : '' }}"><span class="ic">📦</span>কেনাকাটা</a>
  <a href="{{ route('transactions.index') }}" class="{{ request()->routeIs('transactions.*') || request()->routeIs('products.*') || request()->routeIs('cashbook.*') || request()->routeIs('reports.*') || request()->routeIs('settings.*') ? 'active' : '' }}"><span class="ic">⋯</span>আরও</a>
</nav>
@endauth
@livewireScripts
</body>
</html>
