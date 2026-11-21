<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>হিসাব — দোকানের হিসাব-নিকাশ, এক জায়গায়</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body style="padding-bottom:0">
<header class="site">
  <div class="header-inner">
    <a class="brand" href="{{ route('landing') }}"><span class="brand-mark"><i>৳</i></span><span>হিসাব<small>দোকানের হিসাব-নিকাশ</small></span></a>
    <span style="margin-left:auto"></span>
    @auth
      <a class="btn sm dark" href="{{ route('dashboard') }}">ড্যাশবোর্ডে যান</a>
    @else
      <a href="{{ route('login') }}" style="font-weight:600">লগইন</a>
      <a class="btn sm" href="{{ route('register') }}">ফ্রি শুরু করুন</a>
    @endauth
  </div>
</header>
<main class="container">
  <section class="hero">
    <p class="eyebrow" style="color:var(--gold)">দোকানের ডিজিটাল খাতা</p>
    <h1>দোকানের হিসাব-নিকাশ,<br><span class="gold">এখন মোবাইলেই — নিখুঁতভাবে।</span></h1>
    <p>বিক্রি, বাকি, কেনাকাটা, মজুদ, ক্যাশ আর আয়-ব্যয় — সব এক জায়গায়। খাতা-কলমের ঝামেলা শেষ, প্রতিটি টাকার হিসাব পরিষ্কার।</p>
    <a class="btn" href="{{ route('register') }}">১৪ দিন ফ্রি ব্যবহার করুন</a>
    <p class="hero-note">ক্রেডিট কার্ড লাগবে না · যেকোনো সময় বাতিল করা যায়</p>
  </section>

  <p class="section-label" style="text-align:center">যা যা করতে পারবেন</p>
  <h2 style="text-align:center;font-size:22px">একটি অ্যাপে পুরো দোকানের হিসাব</h2>
  <div class="features">
    <div class="feature"><div class="ic">🛒</div><h3>বিক্রি ও ছাড়</h3><p>মাল বাছুন, ছাড় দিন, টাকা নিন — বাকি থাকলে অটো হিসাব। রসিদ প্রিন্ট বা WhatsApp-এ পাঠানো যায়।</p></div>
    <div class="feature"><div class="ic">📒</div><h3>বাকির হিসাব</h3><p>কে কত টাকা বাকি রেখেছে, কত দিনের বাকি — এক নজরে। WhatsApp-এ ভদ্রভাবে তাগাদা পাঠান।</p></div>
    <div class="feature"><div class="ic">📦</div><h3>কেনাকাটা ও মজুদ</h3><p>মাল কিনলে স্টক বাড়ে, বিক্রি হলে কমে। কম স্টকে সতর্কতা আসে, ক্রেডিট লিমিটও ধরা যায়।</p></div>
    <div class="feature"><div class="ic">💵</div><h3>ক্যাশ বই ও দিন শেষ</h3><p>ক্যাশে কত টাকা আছে, কোথা থেকে এলো, কোথায় গেল — দিন শেষে এক পাতায় পুরো দিনের হিসাব।</p></div>
    <div class="feature"><div class="ic">📊</div><h3>লাভ-ক্ষতির বিবরণী</h3><p>মাসের আয়, কেনা দাম, মোট লাভ, খরচ আর নিট লাভ — দোকান ও ব্যক্তিগত খরচ আলাদা করে।</p></div>
    <div class="feature"><div class="ic">📱</div><h3>মোবাইল-ফার্স্ট</h3><p>ফোনে চালানোর মতো করে বানানো — বড় বোতাম, সহজ মেনু, দ্রুত লোড। CSV এক্সপোর্টও আছে।</p></div>
  </div>

  <p class="section-label mt" style="text-align:center">মূল্য</p>
  <h2 style="text-align:center;font-size:22px">সহজ মূল্য, কোনো লুকানো খরচ নেই</h2>
  <div class="pricing">
    <div class="price-card">
      <p class="eyebrow">ফ্রি ট্রায়াল</p>
      <div class="price">৳০</div>
      <p>১৪ দিন, সব ফিচার খোলা।</p>
      <a class="btn dark" href="{{ route('register') }}">শুরু করুন</a>
    </div>
    <div class="price-card featured">
      <p class="eyebrow">প্রো</p>
      <div class="price">৳৩০০<span style="font-size:16px">/মাস</span></div>
      <p>সব ফিচার, আনলিমিটেড মাল-কাস্টমার-লেনদেন।</p>
      <a class="btn" href="{{ route('register') }}">ট্রায়াল দিয়ে শুরু করুন</a>
    </div>
  </div>
  <p class="muted mt" style="text-align:center">হিসাব একটি SaaS পণ্য — প্রতিটি দোকানের হিসাব আলাদা অ্যাকাউন্টে সম্পূর্ণ গোপন থাকে।</p>
</main>
<footer class="site">© {{ date('Y') }} হিসাব — troxela.store পরিবারের পণ্য</footer>
</body>
</html>
