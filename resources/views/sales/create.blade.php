@extends('layouts.app')
@section('title', 'নতুন বিক্রি')
@section('content')
<h1>নতুন বিক্রি</h1>
@if($business->products()->count() === 0)
  <div class="card"><p>প্রথমে মজুদে মাল যোগ করুন।</p><a class="btn" href="{{ route('products.index') }}">মজুদে যান</a></div>
@else
  <livewire:sale-form />
@endif
@endsection
