@extends('layouts.app')
@section('title', 'বাকি')
@section('content')
<h1>বাকি (কাস্টমার)</h1>
<livewire:customer-manager />
<div class="card">
  <form method="GET" class="field-row three">
    <div style="flex:1"><input type="text" name="q" value="{{ request('q') }}" placeholder="নাম বা ফোন দিয়ে খুঁজুন" style="margin-top:12px"></div>
    <div>
      <label>সাজান</label>
      <select name="sort" onchange="this.form.submit()">
        <option value="name" {{ $sort==='name'?'selected':'' }}>নাম অনুযায়ী</option>
        <option value="due" {{ $sort==='due'?'selected':'' }}>বাকি বেশি আগে</option>
        <option value="age" {{ $sort==='age'?'selected':'' }}>সবচেয়ে পুরনো বাকি আগে</option>
      </select>
    </div>
    <div><button class="btn sm mt" type="submit">খুঁজুন</button></div>
  </form>
</div>
<div class="card">
  @forelse($customers as $c)
    @php($due = $c->dueAmount())
    @php($age = $aging[$c->id] ?? null)
    <div class="list-row">
      <div>
        <a href="{{ route('customers.show', $c) }}"><strong>{{ $c->name }}</strong></a>
        @if($c->phone)<br><span class="muted">{{ $c->phone }}</span>@endif
        @if($age !== null)<br><span class="badge {{ $age >= 30 ? 'red' : 'amber' }}">{{ $age }} দিনের বাকি</span>@endif
        @if($c->credit_limit !== null)<span class="muted"> · সীমা {{ \App\Support\Hisab::money($c->credit_limit) }}</span>@endif
      </div>
      <div style="text-align:right">
        @if($due > 0)<span class="badge amber">বাকি {{ \App\Support\Hisab::money($due) }}</span>@else <span class="badge green">বাকি নেই</span>@endif
        @if($c->isOverLimit())<br><span class="badge red">সীমা ছাড়িয়েছে</span>@endif
        <br><a href="{{ route('customers.show', $c) }}">খতিয়ান →</a>
        · <a href="#" onclick="Livewire.dispatch('edit-customer', {id: {{ $c->id }}}); return false;">এডিট</a>
        @if($due > 0 && ($wa = \App\Support\Hisab::whatsappUrl($c->phone, \App\Support\Hisab::dueReminderText($business, $c->name, $due))))
          <br><a href="{{ $wa }}" target="_blank" rel="noopener">💬 WhatsApp-এ তাগাদা</a>
        @endif
      </div>
    </div>
  @empty
    <div class="empty"><span class="ic">📒</span>কোনো কাস্টমার নেই।<br>উপরের ফর্ম থেকে প্রথম কাস্টমার যোগ করুন।</div>
  @endforelse
  <div class="mt">{{ $customers->links() }}</div>
</div>
@endsection
