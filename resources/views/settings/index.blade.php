@extends('layouts.app')
@section('title', 'সেটিংস')
@section('content')
<h1>সেটিংস</h1>
<div class="card">
  <h2>দোকানের তথ্য</h2>
  <p class="muted">প্ল্যান: {{ $business->plan === 'pro' ? 'প্রো (৳৩০০/মাস)' : 'ফ্রি ট্রায়াল' }} @if($business->trial_ends_at) · ট্রায়াল শেষ {{ $business->trial_ends_at->format('d M Y') }}@endif</p>
  <form method="POST" action="{{ route('settings.update') }}">
    @csrf
    @method('PUT')
    <label for="name">দোকানের নাম</label>
    <input id="name" type="text" name="name" value="{{ old('name', $business->name) }}" required>
    <div class="field-row two">
      <div>
        <label for="type">দোকানের ধরন</label>
        <select id="type" name="type">
          @foreach(\App\Models\Business::TYPES as $t)
            <option value="{{ $t }}" @selected(old('type', $business->type) === $t)>{{ $t }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="phone">মোবাইল নম্বর</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone', $business->phone) }}">
      </div>
    </div>
    <label for="address">ঠিকানা</label>
    <input id="address" type="text" name="address" value="{{ old('address', $business->address) }}">
    <label for="opening_cash">খোলা ক্যাশ (শুরুর টাকা)</label>
    <input id="opening_cash" type="number" step="0.01" min="0" name="opening_cash" value="{{ old('opening_cash', $business->opening_cash) }}" required>
    <p class="muted">খোলা ক্যাশ বদলালে ক্যাশের হিসাবও বদলে যাবে।</p>
    <button class="btn mt" type="submit">সংরক্ষণ করুন</button>
  </form>
</div>
<div class="card">
  <h2>পাসওয়ার্ড বদলান</h2>
  <form method="POST" action="{{ route('settings.password') }}">
    @csrf
    <label for="current_password">বর্তমান পাসওয়ার্ড</label>
    <input id="current_password" type="password" name="current_password" required>
    <label for="password">নতুন পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর)</label>
    <input id="password" type="password" name="password" required>
    <label for="password_confirmation">নতুন পাসওয়ার্ড আবার লিখুন</label>
    <input id="password_confirmation" type="password" name="password_confirmation" required>
    <button class="btn mt" type="submit">পাসওয়ার্ড বদলান</button>
  </form>
</div>
@endsection
