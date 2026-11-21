@extends('layouts.admin')
@section('title', 'অ্যাডমিন ড্যাশবোর্ড')
@section('content')
<h1>অ্যাডমিন ড্যাশবোর্ড</h1>

<div class="admin-stats">
  <div class="admin-stat"><div class="label">মোট ব্যবসা</div><div class="value">{{ $total }}</div><div class="sub">এই মাসে নতুন {{ $newThisMonth }}টি</div></div>
  <div class="admin-stat"><div class="label">পেইড</div><div class="value">{{ $paidCount }}</div><div class="sub">মাসিক আয় {{ \App\Support\Hisab::money($monthlyRevenue) }}</div></div>
  <div class="admin-stat"><div class="label">ট্রায়ালে</div><div class="value">{{ $trialCount }}</div><div class="sub">এখনো টাকা দেয়নি</div></div>
  <div class="admin-stat"><div class="label">মেয়াদ শেষ</div><div class="value">{{ $expiredCount }}</div><div class="sub">পেমেন্ট বাকি</div></div>
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
