@extends('layouts.admin')
@section('title', 'সব ব্যবসা')
@section('content')
<h1>সব ব্যবসা</h1>

<form method="GET" action="{{ route('admin.businesses') }}" class="search-bar">
  <input type="text" name="q" value="{{ $q }}" placeholder="ব্যবসা, মালিক বা ফোন দিয়ে খুঁজুন…">
  <select name="status" onchange="this.form.submit()">
    <option value="all" @selected($status === 'all')>সব</option>
    <option value="trial" @selected($status === 'trial')>ট্রায়াল</option>
    <option value="paid" @selected($status === 'paid')>পেইড</option>
    <option value="expired" @selected($status === 'expired')>মেয়াদ শেষ</option>
  </select>
  <button class="btn dark sm" type="submit">খুঁজুন</button>
</form>

<div class="admin-card">
  @if($businesses->isEmpty())
    <div class="empty">কোনো ব্যবসা পাওয়া যায়নি।</div>
  @else
  <table class="admin-table">
    <thead>
      <tr><th>ব্যবসা</th><th>মালিক</th><th>ফোন</th><th>ধরন</th><th>রেজিস্টার</th><th>ট্রায়াল শেষ</th><th>পেইড পর্যন্ত</th><th>স্ট্যাটাস</th></tr>
    </thead>
    <tbody>
      @foreach($businesses as $b)
      <tr>
        <td data-label="ব্যবসা"><a href="{{ route('admin.businesses.show', $b) }}"><strong>{{ $b->name }}</strong></a></td>
        <td data-label="মালিক">{{ $b->owner?->name ?? '—' }}</td>
        <td data-label="ফোন">{{ $b->phone ?? '—' }}</td>
        <td data-label="ধরন">{{ $b->type ?? '—' }}</td>
        <td data-label="রেজিস্টার">{{ $b->created_at->format('d M Y') }}</td>
        <td data-label="ট্রায়াল শেষ">{{ $b->trial_ends_at?->format('d M Y') ?? '—' }}</td>
        <td data-label="পেইড পর্যন্ত">{{ $b->paid_until?->format('d M Y') ?? '—' }}</td>
        <td data-label="স্ট্যাটাস">
          @if($b->subscriptionStatus() === 'paid')<span class="badge green">পেইড</span>
          @elseif($b->subscriptionStatus() === 'trial')<span class="badge amber">ট্রায়াল</span>
          @else<span class="badge red">মেয়াদ শেষ</span>@endif
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @endif
</div>
@endsection
