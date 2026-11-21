@extends('layouts.app')
@section('title', 'পাসওয়ার্ড ভুলে গেছেন')
@section('content')
<div class="card" style="max-width:440px;margin:0 auto">
  <h1>পাসওয়ার্ড রিসেট</h1>
  <p class="muted">ইমেইল দিন — রিসেটের লিংক পাঠানো হবে।</p>
  <form method="POST" action="{{ route('password.email') }}">
    @csrf
    <label for="email">ইমেইল</label>
    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
    <button class="btn block mt" type="submit">লিংক পাঠান</button>
  </form>
  <p class="mt"><a href="{{ route('login') }}">লগইনে ফিরুন</a></p>
</div>
@endsection
