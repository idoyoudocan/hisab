@extends('layouts.admin')
@section('title', $business->name)
@section('content')
<p><a href="{{ route('admin.businesses') }}">← সব ব্যবসা</a></p>
<h1>{{ $business->name }}</h1>

<div class="admin-card">
  <h2>প্রোফাইল</h2>
  <div class="list-row"><span>মালিক</span><strong>{{ $business->owner?->name ?? '—' }} ({{ $business->owner?->email ?? '' }})</strong></div>
  <div class="list-row"><span>ফোন</span><strong>{{ $business->phone ?? '—' }}</strong></div>
  <div class="list-row"><span>ঠিকানা</span><strong>{{ $business->address ?? '—' }}</strong></div>
  <div class="list-row"><span>ধরন</span><strong>{{ $business->type ?? '—' }}</strong></div>
  <div class="list-row"><span>রেজিস্টার</span><strong>{{ $business->created_at->format('d M Y, h:i A') }}</strong></div>
  <div class="list-row"><span>স্ট্যাটাস</span>
    @if($business->subscriptionStatus() === 'paid')<span class="badge green">পেইড — {{ $business->paid_until->format('d M Y') }} পর্যন্ত</span>
    @elseif($business->subscriptionStatus() === 'trial')<span class="badge amber">ট্রায়াল — {{ $business->trialDaysLeft() }} দিন বাকি ({{ $business->trial_ends_at->format('d M Y') }})</span>
    @else<span class="badge red">মেয়াদ শেষ</span>@endif
  </div>
</div>

<div class="admin-card">
  <h2>ব্যবহার</h2>
  <div class="list-row"><span>মোট বিক্রি</span><strong class="num">{{ $salesCount }}টি — {{ \App\Support\Hisab::money($salesTotal) }}</strong></div>
  <div class="list-row"><span>মাল</span><strong class="num">{{ $productCount }}টি</strong></div>
  <div class="list-row"><span>কাস্টমার</span><strong class="num">{{ $customerCount }} জন</strong></div>
</div>

<div class="admin-card">
  <h2>অ্যাকাউন্টের অবস্থা</h2>
  @if($business->isDisabled())
    <p><span class="badge red">বন্ধ আছে</span></p>
    <div class="list-row"><span>বন্ধের কারণ</span><strong>{{ $business->disabled_reason ?? '—' }}</strong></div>
    <div class="list-row"><span>বন্ধ করা হয়েছে</span><strong>{{ $business->disabled_at?->format('d M Y, h:i A') ?? '—' }}</strong></div>
    <p class="muted">বন্ধ থাকায় দোকানদার লগইন করতে পারছে না। তার ডেটা অক্ষত আছে — চালু করলেই আবার সব ফিরে পাবে।</p>
    <form method="POST" action="{{ route('admin.businesses.enable', $business) }}">
      @csrf
      <button class="btn dark mt" type="submit">অ্যাকাউন্ট আবার চালু করুন</button>
    </form>
  @else
    <p><span class="badge green">চালু আছে</span></p>
    <p class="muted">বন্ধ করলে দোকানদার লগইন করতে পারবে না (কারণসহ বার্তা দেখবে)। ডেটা কিছুই মুছবে না — আবার চালু করলেই ফিরে পাবে।</p>
    <form method="POST" action="{{ route('admin.businesses.disable', $business) }}">
      @csrf
      <label for="reason">বন্ধের কারণ — দোকানদারকে দেখানো হবে</label>
      <input id="reason" type="text" name="reason" placeholder="যেমন: পেমেন্ট বাকি" required>
      <button class="btn danger mt" type="submit">অ্যাকাউন্ট বন্ধ করুন</button>
    </form>
  @endif
</div>

<div class="admin-card">
  <h2>মেয়াদ বাড়ান</h2>
  <p class="muted">টাকা পাওয়ার পর এখান থেকে মেয়াদ বাড়ান — পেমেন্ট আপনাআপনি প্ল্যানের দামে লেখা হবে। চালু থাকলে বর্তমান মেয়াদের পর থেকে বাড়বে, মেয়াদ শেষ হলে আজ থেকে।</p>
  <form method="POST" action="{{ route('admin.businesses.extend', $business) }}">
    @csrf
    <div class="field-row two">
      <div>
        <label for="months">কত মাস</label>
        <select id="months" name="months">
          @foreach(\App\Support\Plans::all() as $plan)
            <option value="{{ $plan['months'] }}">{{ $plan['months'] }} মাস — {{ $plan['name'] }} {{ \App\Support\Hisab::money($plan['payable']) }} ({{ $plan['discount'] }}% ছাড়)</option>
          @endforeach
        </select>
      </div>
      <div>
        <label for="method">পেমেন্টের মাধ্যম</label>
        <select id="method" name="method">
          @foreach(\App\Models\SubscriptionPayment::methods() as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
        </select>
      </div>
    </div>
    <label for="note">নোট — ঐচ্ছিক</label>
    <input id="note" type="text" name="note" placeholder="যেমন: বিকাশে পেমেন্ট পেয়েছি">
    <button class="btn dark mt" type="submit">মেয়াদ বাড়ান</button>
  </form>
</div>

<div class="admin-card">
  <h2>পেমেন্ট যোগ করুন</h2>
  <p class="muted">মেয়াদ না বাড়িয়ে শুধু পেমেন্ট লিখতে চাইলে এখানে লিখুন। মাস দিলে মেয়াদও বাড়বে।</p>
  <form method="POST" action="{{ route('admin.businesses.payments', $business) }}">
    @csrf
    <div class="field-row two">
      <div>
        <label for="amount">টাকা</label>
        <input id="amount" type="number" step="0.01" min="0" name="amount" required placeholder="300">
      </div>
      <div>
        <label for="months2">মেয়াদ বাড়বে (মাস)</label>
        <select id="months2" name="months">
          <option value="0">বাড়বে না</option>
          <option value="1">১ মাস</option>
          <option value="3">৩ মাস</option>
          <option value="6">৬ মাস</option>
          <option value="12">১২ মাস</option>
        </select>
      </div>
    </div>
    <div class="field-row two">
      <div>
        <label for="method2">মাধ্যম</label>
        <select id="method2" name="method">
          @foreach(\App\Models\SubscriptionPayment::methods() as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
        </select>
      </div>
      <div>
        <label for="paid_at">পেমেন্টের তারিখ</label>
        <input id="paid_at" type="date" name="paid_at" value="{{ now()->toDateString() }}" required>
      </div>
    </div>
    <label for="note2">নোট — ঐচ্ছিক</label>
    <input id="note2" type="text" name="note" placeholder="যেমন: আংশিক পেমেন্ট">
    <button class="btn mt" type="submit">পেমেন্ট সংরক্ষণ করুন</button>
  </form>
</div>

<div class="admin-card">
  <h2>পেমেন্টের ইতিহাস</h2>
  @forelse($payments as $p)
    <div class="list-row">
      <div>
        <strong class="num">{{ \App\Support\Hisab::money($p->amount) }}</strong> · {{ $p->methodLabel() }}
        @if($p->planName()) · {{ $p->planName() }} প্ল্যান @endif
        @if($p->months > 0) · {{ $p->months }} মাসের মেয়াদ @endif
        <br><span class="muted">{{ $p->paid_at->format('d M Y') }}@if($p->note) — {{ $p->note }}@endif</span>
      </div>
      <span class="muted">{{ $p->recorded_by ?? '' }}</span>
    </div>
  @empty
    <p class="muted">এখনো কোনো পেমেন্ট লেখা হয়নি।</p>
  @endforelse
</div>

<div class="admin-card">
  <h2>নোটিফিকেশন পাঠান</h2>
  <p class="muted">বার্তাটি দোকানদার তার ড্যাশবোর্ডের উপরে ব্যানার আকারে দেখবে, "পড়েছি" চাপলে সরবে।</p>
  <form method="POST" action="{{ route('admin.businesses.notices', $business) }}">
    @csrf
    <label for="message">বার্তা</label>
    <textarea id="message" name="message" rows="3" placeholder="যেমন: আগামীকালের মধ্যে পেমেন্ট করুন" required></textarea>
    <button class="btn dark mt" type="submit">নোটিফিকেশন পাঠান</button>
  </form>
  @forelse($notices as $n)
    <div class="list-row">
      <div>
        {{ $n->message }}
        <br><span class="muted">{{ $n->created_at->format('d M Y, h:i A') }}@if($n->read_at) · পড়েছে {{ $n->read_at->format('d M Y, h:i A') }}@else · এখনো পড়েনি@endif</span>
      </div>
      @php($wa = \App\Support\Hisab::whatsappUrl($business->phone, $n->message))
      @if($wa)<a class="btn sm ghost" href="{{ $wa }}" target="_blank" rel="noopener">WhatsApp-এ পাঠান</a>@endif
    </div>
  @empty
    <p class="muted">এখনো কোনো নোটিফিকেশন পাঠানো হয়নি।</p>
  @endforelse
</div>

<div class="admin-card">
  <h2>অ্যাকাউন্ট ডিলেট করুন</h2>
  @if(\App\Support\Hisab::isAdmin($business->owner))
    <p class="muted">এটি অ্যাডমিনের অ্যাকাউন্ট — এখান থেকে ডিলেট করা যাবে না।</p>
  @else
    <p class="muted">সাবধান — ডিলেট করলে দোকানদারের লগইন, ব্যবসা, বিক্রি, কেনাকাটা, কাস্টমার, বাকি, পেমেন্টের হিস্ট্রি, নোটিফিকেশন — সব চিরতরে মুছে যাবে। ফেরানো যাবে না।</p>
    <form method="POST" action="{{ route('admin.businesses.destroy', $business) }}">
      @csrf
      <label for="confirm_name">কনফার্ম করতে দোকানের নাম লিখুন: <strong>{{ $business->name }}</strong></label>
      <input id="confirm_name" type="text" name="confirm_name" placeholder="{{ $business->name }}" required>
      <button class="btn danger mt" type="submit">অ্যাকাউন্ট সম্পূর্ণ ডিলেট করুন</button>
    </form>
  @endif
</div>
@endsection
