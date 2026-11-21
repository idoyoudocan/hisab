@extends('layouts.app')
@section('title', 'টাকার হিসাব')
@section('content')
<h1>টাকার হিসাব</h1>
<p class="muted">নগদ, ব্যাংক, মোবাইল ব্যাংকিং আর সিমের ব্যালেন্স — সব এক জায়গায়।</p>

<div class="hero-cash">
  <div class="eyebrow">সব অ্যাকাউন্ট মিলে মোট ব্যালেন্স</div>
  <div class="hero-amount">{{ \App\Support\Hisab::money($grandTotal) }}</div>
  <div class="hero-sub">নগদ {{ \App\Support\Hisab::money(\App\Support\Hisab::cashBalance($business)) }} + অন্যান্য অ্যাকাউন্ট {{ \App\Support\Hisab::money(array_sum($totals)) }}</div>
  <div class="hero-actions">
    <a class="btn" href="{{ route('capital.index') }}">আমার মূলধন দেখুন</a>
    <a class="btn ghost" href="{{ route('cash-count.index') }}">ক্যাশ মেলান</a>
  </div>
</div>

@livewire('money-account-manager')
@endsection
