@extends('layouts.app')
@section('title', 'ফিচার বন্ধ আছে')
@section('content')
<div class="card module-disabled">
  <span class="ic">⚙️</span>
  <h1>{{ $moduleLabel }} বন্ধ আছে</h1>
  <p class="muted">এই ফিচারটি আপনি সেটিংস থেকে বন্ধ করে রেখেছেন, তাই এখানে কিছু দেখানো হচ্ছে না। আবার চালু করতে চাইলে সেটিংসে গিয়ে সুইচটি অন করুন।</p>
  <a class="btn dark" href="{{ route('settings.index') }}">সেটিংসে যান →</a>
  <a class="btn ghost" href="{{ route('dashboard') }}">ড্যাশবোর্ডে ফিরুন</a>
</div>
@endsection
