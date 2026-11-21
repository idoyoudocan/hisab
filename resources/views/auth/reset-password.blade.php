@extends('layouts.app')
@section('title', 'নতুন পাসওয়ার্ড')
@section('content')
<div class="card" style="max-width:440px;margin:0 auto">
  <h1>নতুন পাসওয়ার্ড দিন</h1>
  <form method="POST" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <label for="email">ইমেইল</label>
    <input id="email" type="email" name="email" value="{{ $email ?? old('email') }}" required autofocus>
    <label for="password">নতুন পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)</label>
    <input id="password" type="password" name="password" required>
    <label for="password_confirmation">পাসওয়ার্ড আবার লিখুন</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required>
    <button class="btn block mt" type="submit">পাসওয়ার্ড বদলান</button>
  </form>
</div>
@endsection
