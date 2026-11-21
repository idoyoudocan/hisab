<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'অ্যাডমিন') — হিসাব অ্যাডমিন</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
{{-- Deliberately separate from the shop app's CSS: tomorrow's admin HTML
     template replaces this layout + admin.css without touching shop logic. --}}
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
<header class="admin-top">
  <a class="admin-brand" href="{{ route('admin.dashboard') }}"><span class="admin-mark">৳</span> হিসাব <small>অ্যাডমিন</small></a>
  <nav>
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">ড্যাশবোর্ড</a>
    <a href="{{ route('admin.businesses') }}" class="{{ request()->routeIs('admin.businesses*') ? 'active' : '' }}">ব্যবসা</a>
    <a href="{{ route('dashboard') }}">দোকানের অ্যাপ →</a>
    <a href="#" onclick="event.preventDefault();document.getElementById('admin-logout').submit();">লগআউট</a>
  </nav>
  <form id="admin-logout" action="{{ route('logout') }}" method="POST" style="display:none">@csrf</form>
</header>
<main class="admin-container">
  @if(session('status'))<div class="admin-flash">{{ session('status') }}</div>@endif
  @if($errors->any())
    <div class="admin-flash err">
      @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
    </div>
  @endif
  @yield('content')
</main>
</body>
</html>
