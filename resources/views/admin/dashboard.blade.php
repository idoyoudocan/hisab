@extends('layouts.admin')
@section('title', 'অ্যাডমিন ড্যাশবোর্ড')
@section('content')
<h1>অ্যাডমিন ড্যাশবোর্ড</h1>

<div class="admin-stats">
  <div class="admin-stat"><div class="label">মোট ব্যবসা</div><div class="value">{{ $total }}</div><div class="sub">এই মাসে নতুন {{ $newThisMonth }}টি</div></div>
  <div class="admin-stat"><div class="label">পেইড</div><div class="value">{{ $paidCount }}</div><div class="sub">মাসিক আয় {{ \App\Support\Hisab::money($monthlyRevenue) }}</div></div>
  <div class="admin-stat"><div class="label">ট্রায়ালে</div><div class="value">{{ $trialCount }}</div><div class="sub">এখনো টাকা দেয়নি</div></div>
  <div class="admin-stat"><div class="label">মেয়াদ শেষ</div><div class="value">{{ $expiredCount }}</div><div class="sub">পেমেন্ট বাকি</div></div>
  <div class="admin-stat"><div class="label">বন্ধ অ্যাকাউন্ট</div><div class="value">{{ $disabledCount }}</div><div class="sub">অ্যাডমিন বন্ধ করেছে</div></div>
</div>

<div class="admin-card">
  <h2>📦 প্ল্যানের অনুরোধ (অনুমোদনের অপেক্ষায়)</h2>
  @forelse($pendingRequests as $req)
    <div class="list-row">
      <div>
        <a href="{{ route('admin.businesses.show', $req->business) }}"><strong>{{ $req->business->name }}</strong></a>
        {{ $req->business->owner?->name }} · {{ $req->planName() }} প্ল্যান ({{ $req->plan()['months'] ?? '' }} মাস) · <strong>{{ \App\Support\Hisab::money($req->amount) }}</strong>
        <br><span class="muted">{{ $req->created_at->format('d M Y, h:i A') }}</span>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <form method="POST" action="{{ route('admin.plan-requests.approve', $req) }}" style="display:flex;gap:6px">
          @csrf
          <select name="method" aria-label="পেমেন্টের মাধ্যম">
            @foreach(\App\Models\SubscriptionPayment::methods() as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach
          </select>
          <button class="btn sm dark" type="submit">অনুমোদন করুন</button>
        </form>
        <form method="POST" action="{{ route('admin.plan-requests.reject', $req) }}">
          @csrf
          <button class="btn sm ghost" type="submit">বাতিল</button>
        </form>
      </div>
    </div>
  @empty
    <p class="muted">কোনো প্ল্যানের অনুরোধ অপেক্ষায় নেই।</p>
  @endforelse
  <p class="muted">অনুমোদন করলে পেমেন্ট পাওয়ার পর মেয়াদ বাড়বে — চালু থাকলে বর্তমান মেয়াদের পর থেকে, মেয়াদ শেষ হলে আজ থেকে।</p>
</div>

<div class="admin-card">
  <h2>⏰ ৭ দিনের মধ্যে মেয়াদ শেষ হচ্ছে</h2>
  @forelse($expiring as $b)
    <div class="list-row">
      <div>
        <a href="{{ route('admin.businesses.show', $b) }}"><strong>{{ $b->name }}</strong></a>
        {{ $b->owner?->name }} · {{ $b->phone ?? 'ফোন নেই' }}
      </div>
      <span class="badge {{ $b->subscriptionStatus() === 'paid' ? 'gold' : 'amber' }}">
        {{ $b->subscriptionStatus() === 'paid' ? 'পেইড পর্যন্ত ' : 'ট্রায়াল শেষ ' }}{{ ($b->subscriptionStatus() === 'paid' ? $b->paid_until : $b->trial_ends_at)?->format('d M Y') }}
      </span>
    </div>
  @empty
    <p class="muted">আগামী ৭ দিনে কারো মেয়াদ শেষ হচ্ছে না।</p>
  @endforelse
</div>

<div class="admin-card">
  <h2>🆕 সাম্প্রতিক রেজিস্ট্রেশন</h2>
  @forelse($recent as $b)
    <div class="list-row">
      <div>
        <a href="{{ route('admin.businesses.show', $b) }}"><strong>{{ $b->name }}</strong></a>
        {{ $b->owner?->name }} · {{ $b->type ?? '' }} · {{ $b->created_at->format('d M Y') }}
      </div>
      @if($b->subscriptionStatus() === 'paid')<span class="badge green">পেইড</span>
      @elseif($b->subscriptionStatus() === 'trial')<span class="badge amber">ট্রায়াল</span>
      @else<span class="badge red">মেয়াদ শেষ</span>@endif
    </div>
  @empty
    <p class="muted">এখনো কোনো ব্যবসা রেজিস্টার করেনি।</p>
  @endforelse
  <a href="{{ route('admin.businesses') }}">সব ব্যবসা দেখুন →</a>
</div>
@endsection
