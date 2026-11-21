@extends('layouts.app')
@section('title', 'লগইন')
@section('content')
<div class="card" style="max-width:440px;margin:0 auto">
  <h1>লগইন</h1>
  <form method="POST" action="{{ route('login') }}">
    @csrf
    <label for="email">ইমেইল</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
    <label for="password">পাসওয়ার্ড</label>
    <input id="password" type="password" name="password" required>
    <label style="display:flex;align-items:center;gap:8px;font-weight:400"><input type="checkbox" name="remember" style="width:auto"> মনে রাখুন</label>
    <button class="btn block mt" type="submit">লগইন</button>
  </form>
  <p class="mt"><a href="{{ route('password.request') }}">পাসওয়ার্ড ভুলে গেছেন?</a></p>
  <p>অ্যাকাউন্ট নেই? <a href="{{ route('register') }}">ফ্রি রেজিস্টার করুন</a></p>
</div>
@endsection
