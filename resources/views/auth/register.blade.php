@extends('layouts.app')
@section('title', 'রেজিস্টার')
@section('content')
<div class="card" style="max-width:520px;margin:0 auto">
  <h1>ফ্রি অ্যাকাউন্ট খুলুন</h1>
  <p class="muted">১৪ দিনের ফ্রি ট্রায়াল — সব ফিচার খোলা।</p>
  <form method="POST" action="{{ route('register') }}">
    @csrf
    <label for="name">আপনার নাম</label>
    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus>
    <label for="email">ইমেইল</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" required>
    <div class="field-row two">
      <div>
        <label for="business_name">দোকানের নাম</label>
        <input id="business_name" type="text" name="business_name" value="{{ old('business_name', 'আমার দোকান') }}" required>
      </div>
      <div>
        <label for="business_type">দোকানের ধরন</label>
        <input id="business_type" type="text" name="business_type" value="{{ old('business_type') }}" placeholder="যেমন: মুদি দোকান">
      </div>
    </div>
    <label for="phone">মোবাইল নম্বর</label>
    <input id="phone" type="text" name="phone" value="{{ old('phone') }}">
    <label for="password">পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)</label>
    <input id="password" type="password" name="password" required>
    <label for="password_confirmation">পাসওয়ার্ড আবার লিখুন</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required>
    <button class="btn block mt" type="submit">অ্যাকাউন্ট খুলুন</button>
  </form>
  <p class="mt">অ্যাকাউন্ট আছে? <a href="{{ route('login') }}">লগইন করুন</a></p>
</div>
@endsection
