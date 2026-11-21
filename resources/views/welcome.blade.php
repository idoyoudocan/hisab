<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>হিসাব — দোকানের হিসাব-নিকাশ, এক জায়গায়</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body style="padding-bottom:0">
<header class="site">
  <div class="header-inner">
    <a class="brand" href="{{ route('landing') }}">হিসাব<small>দোকানের হিসাব-নিকাশ</small></a>
    <span style="margin-left:auto"></span>
    @auth
      <a class="btn sm" href="{{ route('dashboard') }}">ড্যাশবোর্ডে যান</a>
    @else
      <a href="{{ route('login') }}" style="font-weight:600">লগইন</a>
      <a class="btn sm" href="{{ route('register') }}">ফ্রি শুরু করুন</a>
    @endauth
  </div>
</header>
<main class="container">
  <section class="hero">
    <h1>দোকানের হিসাব-নিকাশ, এখন মোবাইলেই</h1>
    <p>বিক্রি, বাকি, কেনাকাটা, মজুদ, ক্যাশ আর আয়-ব্যয় — সব এক জায়গায়। খাতা-কলমের ঝামেলা শেষ।</p>
    <a class="btn" href="{{ route('register') }}">১৪ দিন ফ্রি ব্যবহার করুন</a>
    <p style="margin-top:10px;font-size:13px">ক্রেডিট কার্ড লাগবে না</p>
  </section>

  <h2>যা যা করতে পারবেন</h2>
  <div class="features">
    <div class="feature"><div class="ic">🛒</div><h3>বিক্রি</h3><p>মাল বাছুন, টাকা নিন, বাকি থাকলে অটো হিসাব। রসিদ প্রিন্ট করা যায়।</p></div>
    <div class="feature"><div class="ic">📒</div><h3>বাকির হিসাব</h3><p>কে কত টাকা বাকি রেখেছে, এক নজরে। আদায় করলে বাকি কমে যায়।</p></div>
    <div class="feature"><div class="ic">📦</div><h3>কেনাকাটা ও মজুদ</h3><p>মাল কিনলে স্টক বাড়ে, বিক্রি হলে কমে। কম স্টকে সতর্কতা আসে।</p></div>
    <div class="feature"><div class="ic">💵</div><h3>ক্যাশ বই</h3><p>ক্যাশে কত টাকা আছে, কোথা থেকে এলো, কোথায় গেল — চলমান ব্যালান্সসহ।</p></div>
    <div class="feature"><div class="ic">📊</div><h3>আয়-ব্যয় ও রিপোর্ট</h3><p>দোকানের খরচ আর ব্যক্তিগত খরচ আলাদা। দিন ও মাসের লাভ-ক্ষতি দেখুন।</p></div>
    <div class="feature"><div class="ic">📱</div><h3>মোবাইল-ফার্স্ট</h3><p>ফোনে চালানোর মতো করে বানানো — বড় বোতাম, সহজ মেনু, দ্রুত লোড।</p></div>
  </div>

  <h2 class="mt">মূল্য</h2>
  <div class="pricing">
    <div class="price-card">
      <h3>ফ্রি ট্রায়াল</h3>
      <div class="price">৳০</div>
      <p>১৪ দিন, সব ফিচার খোলা।</p>
      <a class="btn" href="{{ route('register') }}">শুরু করুন</a>
    </div>
    <div class="price-card">
      <h3>প্রো</h3>
      <div class="price">৳৩০০<span style="font-size:16px">/মাস</span></div>
      <p>সব ফিচার, আনলিমিটেড মাল-কাস্টমার-লেনদেন।</p>
      <a class="btn ghost" href="{{ route('register') }}">ট্রায়াল দিয়ে শুরু করুন</a>
    </div>
  </div>
  <p class="muted mt">হিসাব একটি SaaS পণ্য — প্রতিটি দোকানের হিসাব আলাদা অ্যাকাউন্টে সম্পূর্ণ গোপন থাকে।</p>
</main>
<footer class="site">© {{ date('Y') }} হিসাব — troxela.store পরিবারের পণ্য</footer>
</body>
</html>
