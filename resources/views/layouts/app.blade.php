<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'হিসাব') — হিসাব</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css').'?v='.filemtime(public_path('css/app.css')) }}">
@include('partials.pwa-head')
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
  @php($m = fn($key) => ($biz ?? null)?->moduleEnabled($key) ?? true)
  <nav class="top" aria-label="মূল মেনু">
    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">ড্যাশবোর্ড</a>
    <a href="{{ route('sales.index') }}" class="{{ request()->routeIs('sales.*') ? 'active' : '' }}">বিক্রি</a>
    @if($m('ledger'))
    <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}">বাকি</a>
    @endif
    @if($m('purchases'))
    <a href="{{ route('purchases.index') }}" class="{{ request()->routeIs('purchases.*') ? 'active' : '' }}">কেনাকাটা</a>
    @endif
    <a href="{{ route('products.index') }}" class="{{ request()->routeIs('products.*') ? 'active' : '' }}">মজুদ</a>
    @if($m('money_accounts'))
    <a href="{{ route('money.index') }}" class="{{ request()->routeIs('money.*') ? 'active' : '' }}">টাকার হিসাব</a>
    @endif
    <div class="nav-more">
      <button type="button" class="nav-more-btn" aria-haspopup="true">আরও ▾</button>
      <div class="nav-more-menu">
        @if($m('cash_count'))<a href="{{ route('cash-count.index') }}">💵 ক্যাশ মেলান</a>@endif
        @if($m('capital'))<a href="{{ route('capital.index') }}">💰 আমার মূলধন</a>@endif
        <a href="{{ route('transactions.index') }}">আয়-ব্যয়</a>
        <a href="{{ route('cashbook.index') }}">ক্যাশ বই</a>
        @if($m('day_close'))<a href="{{ route('day-close.index') }}">দিন শেষ</a>@endif
        @if($m('profit_loss'))<a href="{{ route('profit-loss.index') }}">লাভ-ক্ষতি</a>@endif
        @if($m('reports'))<a href="{{ route('reports.index') }}">রিপোর্ট</a>@endif
      </div>
    </div>
    <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings.*') ? 'active' : '' }}">সেটিংস</a>
    @if(\App\Support\Hisab::isAdmin())
    <a href="{{ route('admin.dashboard') }}">অ্যাডমিন</a>
    @endif
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
{{-- Cash-count popup, available on every page (the cash-count page mounts its own full component instead). Not mounted at all when the module is switched off in Settings. --}}
@if($biz && ($m('cash_count') ?? true) && ! request()->routeIs('cash-count.*'))
<livewire:cash-counter :modal-only="true" />
@endif
<nav class="mobile" aria-label="মোবাইল মেনু" x-data="{ more: false }" @keydown.escape.window="more = false">
  <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><span class="ic">🏠</span>ড্যাশবোর্ড</a>
  <a href="{{ route('sales.create') }}" class="{{ request()->routeIs('sales.create') ? 'active' : '' }}"><span class="ic">🛒</span>বিক্রি</a>
  @if($m('cash_count'))
  <a href="#" onclick="event.preventDefault();window.dispatchEvent(new CustomEvent('open-cash-modal'));Livewire.dispatch('open-cash-modal')"><span class="ic">💵</span>ক্যাশ মেলান</a>
  @elseif($m('capital'))
  <a href="{{ route('capital.index') }}" class="{{ request()->routeIs('capital.*') ? 'active' : '' }}"><span class="ic">💰</span>মূলধন</a>
  @else
  <a href="{{ route('money.index') }}" class="{{ request()->routeIs('money.*') ? 'active' : '' }}"><span class="ic">🏦</span>টাকার হিসাব</a>
  @endif
  @if($m('ledger'))
  <a href="{{ route('customers.index') }}" class="{{ request()->routeIs('customers.*') ? 'active' : '' }}"><span class="ic">📒</span>বাকি</a>
  @endif
  <a href="#" @click.prevent="more = true"><span class="ic">⋯</span>আরও</a>

  {{-- "আরও" drawer — slides in from the right, closes with ×, backdrop or Escape. --}}
  <div class="more-backdrop" :class="{ 'show': more }" @click="more = false" aria-hidden="true"></div>
  <aside class="more-drawer" :class="{ 'open': more }" role="dialog" aria-label="আরও">
    <div class="more-drawer-head">
      <h2>আরও</h2>
      <button type="button" class="modal-close" @click="more = false" aria-label="বন্ধ করুন">×</button>
    </div>
    <div class="more-drawer-body">
      @if($m('cash_count'))<a href="{{ route('cash-count.index') }}" @click="more = false">💵 ক্যাশ মেলান <span>›</span></a>@endif
      @if($m('money_accounts'))<a href="{{ route('money.index') }}" @click="more = false">🏦 টাকার হিসাব <span>›</span></a>@endif
      @if($m('capital'))<a href="{{ route('capital.index') }}" @click="more = false">💰 আমার মূলধন <span>›</span></a>@endif
      @if($m('purchases'))<a href="{{ route('purchases.index') }}" @click="more = false">📦 কেনাকাটা <span>›</span></a>@endif
      <a href="{{ route('products.index') }}" @click="more = false">🗄️ মজুদ <span>›</span></a>
      <a href="{{ route('transactions.index') }}" @click="more = false">💸 আয়-ব্যয় <span>›</span></a>
      <a href="{{ route('cashbook.index') }}" @click="more = false">📖 ক্যাশ বই <span>›</span></a>
      @if($m('day_close'))<a href="{{ route('day-close.index') }}" @click="more = false">🌙 দিন শেষ <span>›</span></a>@endif
      @if($m('profit_loss'))<a href="{{ route('profit-loss.index') }}" @click="more = false">📈 লাভ-ক্ষতি <span>›</span></a>@endif
      @if($m('reports'))<a href="{{ route('reports.index') }}" @click="more = false">📊 রিপোর্ট <span>›</span></a>@endif
      <a href="{{ route('settings.index') }}" @click="more = false">⚙️ সেটিংস <span>›</span></a>
      @if(\App\Support\Hisab::isAdmin())
      <a href="{{ route('admin.dashboard') }}" @click="more = false">🛡️ অ্যাডমিন <span>›</span></a>
      @endif
      <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();">🚪 লগআউট <span>›</span></a>
    </div>
  </aside>
</nav>
@endauth
@include('partials.pwa-banner')
@livewireScripts
</body>
</html>
