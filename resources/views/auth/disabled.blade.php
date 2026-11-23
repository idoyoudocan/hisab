<!DOCTYPE html>
<html lang="bn">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>অ্যাকাউন্ট বন্ধ — হিসাব</title>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  body{margin:0;font-family:'Hind Siliguri',sans-serif;background:#F7F5F0;color:#0B1F17;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;box-sizing:border-box}
  .box{background:#fff;border:1px solid #e8e2d6;border-radius:16px;max-width:480px;width:100%;padding:28px;box-shadow:0 8px 30px rgba(11,31,23,.08);text-align:center}
  .mark{width:56px;height:56px;border-radius:14px;background:#0E3B2E;color:#fff;display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:700;margin:0 auto 16px}
  h1{font-size:22px;margin:0 0 8px}
  p{color:#5c665f;font-size:15px;line-height:1.7;margin:8px 0}
  .reason{background:#fdecea;border:1px solid #b3352b;color:#b3352b;border-radius:10px;padding:12px 14px;font-size:14px;margin:16px 0}
  .btn{display:inline-block;background:#0E3B2E;color:#fff;border:0;border-radius:999px;padding:11px 22px;font-weight:700;font-size:15px;cursor:pointer;font-family:inherit;text-decoration:none}
</style>
</head>
<body>
<div class="box">
  <div class="mark">৳</div>
  <h1>আপনার অ্যাকাউন্টটি বন্ধ আছে</h1>
  @if($business)
    <p><strong>{{ $business->name }}</strong> — এই দোকানের অ্যাকাউন্টটি অ্যাডমিন বন্ধ করে দিয়েছে। আপনার কোনো ডেটা মোছা হয়নি, অ্যাকাউন্ট আবার চালু হলেই সব ফিরে পাবেন।</p>
    @if($business->disabled_reason)
      <div class="reason">কারণ: {{ $business->disabled_reason }}</div>
    @endif
  @else
    <p>আপনার অ্যাকাউন্টটি বন্ধ আছে। বিস্তারিত জানতে অ্যাডমিনের সাথে যোগাযোগ করুন।</p>
  @endif
  <p>চালু করতে অ্যাডমিনের সাথে যোগাযোগ করুন।</p>
  <form method="POST" action="{{ route('logout') }}" style="margin-top:16px">
    @csrf
    <button class="btn" type="submit">লগআউট</button>
  </form>
</div>
</body>
</html>
