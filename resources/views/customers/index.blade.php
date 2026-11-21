@extends('layouts.app')
@section('title', 'বাকি')
@section('content')
<h1>বাকি (কাস্টমার)</h1>
<livewire:customer-manager />
<div class="card">
  <form method="GET" class="flex">
    <input type="text" name="q" value="{{ request('q') }}" placeholder="নাম বা ফোন দিয়ে খুঁজুন" style="flex:1">
    <button class="btn sm" type="submit">খুঁজুন</button>
  </form>
</div>
<div class="card">
  @forelse($customers as $c)
    <div class="list-row">
      <div>
        <a href="{{ route('customers.show', $c) }}"><strong>{{ $c->name }}</strong></a>
        @if($c->phone)<br><span class="muted">{{ $c->phone }}</span>@endif
      </div>
      <div style="text-align:right">
        @php($due = $c->dueAmount())
        @if($due > 0)<span class="badge amber">বাকি {{ \App\Support\Hisab::money($due) }}</span>@else <span class="badge green">বাকি নেই</span>@endif
        <br><a href="{{ route('customers.show', $c) }}">খতিয়ান →</a>
      </div>
    </div>
  @empty
    <p class="muted">কোনো কাস্টমার নেই। উপরের ফর্ম থেকে যোগ করুন।</p>
  @endforelse
  <div class="mt">{{ $customers->links() }}</div>
</div>
@endsection
