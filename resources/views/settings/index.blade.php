@extends('layouts.app')
@section('title', 'সেটিংস')
@section('content')
<h1>সেটিংস</h1>

{{-- Subscription status --}}
<div class="card">
  <p class="section-label">সাবস্ক্রিপশন</p>
  @if($business->subscriptionStatus() === 'paid')
    <h2>প্রো প্ল্যান — পেইড</h2>
    <p class="muted">আপনার প্ল্যান <strong>{{ $business->paid_until->format('d M Y') }}</strong> পর্যন্ত চালু আছে। মেয়াদ শেষ হলে আবার পেমেন্ট করে বাড়াতে হবে।</p>
  @elseif($business->subscriptionStatus() === 'trial')
    <h2>ফ্রি ট্রায়াল — {{ $business->trialDaysLeft() }} দিন বাকি</h2>
    <p class="muted">ট্রায়াল শেষ <strong>{{ $business->trial_ends_at->format('d M Y') }}</strong>। এরপর প্রো প্ল্যান ৳৩০০/মাস — পেমেন্ট করতে আমাদের সাথে যোগাযোগ করুন।</p>
  @else
    <h2>মেয়াদ শেষ</h2>
    <p class="muted">আপনার ট্রায়াল শেষ হয়ে গেছে। প্রো প্ল্যান (৳৩০০/মাস) নিতে পেমেন্ট করুন — পেমেন্টের পর মেয়াদ বাড়িয়ে দেওয়া হবে।</p>
  @endif
</div>

{{-- Business profile --}}
<div class="card">
  <h2>দোকানের তথ্য</h2>
  <p class="muted">যেকোনো সময় বদলাতে পারবেন — সংরক্ষণ করলেই সব জায়গায় নতুন তথ্য দেখাবে।</p>
  <form method="POST" action="{{ route('settings.update') }}">
    @csrf
    @method('PUT')
    <div class="field-row two">
      <div>
        <label for="owner_name">আপনার নাম</label>
        <input id="owner_name" type="text" name="owner_name" value="{{ old('owner_name', auth()->user()->name) }}" required>
      </div>
      <div>
        <label for="name">দোকানের নাম</label>
        <input id="name" type="text" name="name" value="{{ old('name', $business->name) }}" required>
      </div>
    </div>
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

    <h2 class="mt">কী কী দেখাবে</h2>
    <p class="muted">যা দরকার নেই, তা বন্ধ করে দিন — অ্যাপে শুধু দরকারি জিনিসই দেখাবে।</p>
    @foreach(\App\Models\Business::MODULES as $key => $label)
      <div class="toggle-row">
        <span class="t-label"><strong>{{ $label }}</strong>
          <span class="muted">
            @if($key === 'sim_balance') সিমের ব্যালেন্স যোগ ও দেখা
            @elseif($key === 'cash_count') নোট গুনে ক্যাশ মেলানো
            @elseif($key === 'money_accounts') ব্যাংক, বিকাশ, সিমসহ সব অ্যাকাউন্ট
            @elseif($key === 'capital') নগদ + মজুদ + পাওনা মিলিয়ে মোট মূলধন
            @elseif($key === 'ledger') কাস্টমারের বাকি খাতা
            @elseif($key === 'purchases') মাল কেনা ও সাপ্লায়ারের হিসাব
            @elseif($key === 'day_close') দিন শেষে সারা দিনের হিসাব
            @elseif($key === 'profit_loss') মাসের লাভ-ক্ষতির হিসাব
            @elseif($key === 'reports') দৈনিক/মাসিক রিপোর্ট
            @endif
          </span>
        </span>
        <label class="switch">
          <input type="checkbox" name="modules[{{ $key }}]" value="1" @checked(old('modules.'.$key, $business->moduleEnabled($key)))>
          <span class="track"></span>
        </label>
      </div>
    @endforeach

    <button class="btn mt" type="submit">সংরক্ষণ করুন</button>
  </form>
</div>

{{-- Money accounts --}}
<div class="card">
  <p class="section-label">আমার অ্যাকাউন্ট</p>
  <h2>ব্যাংক, বিকাশ, সিম — সব অ্যাকাউন্ট</h2>
  <p class="muted">কোনো অ্যাকাউন্ট না থাকলে সেটা অ্যাপে দেখাবে না। নতুন অ্যাকাউন্ট যোগ করতে "অ্যাকাউন্ট যোগ করুন" চাপুন।</p>
  @livewire('money-account-manager')
</div>

{{-- Password --}}
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
