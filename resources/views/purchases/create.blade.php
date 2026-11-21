@extends('layouts.app')
@section('title', 'নতুন কেনাকাটা')
@section('content')
<h1>নতুন কেনাকাটা</h1>
@if($business->products()->count() === 0)
  <div class="card"><p>প্রথমে মজুদে মাল যোগ করুন।</p><a class="btn" href="{{ route('products.index') }}">মজুদে যান</a></div>
@else
  <livewire:purchase-form />
@endif
@endsection
