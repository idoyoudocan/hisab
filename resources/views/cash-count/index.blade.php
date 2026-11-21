@extends('layouts.app')
@section('title', 'ক্যাশ মেলান')
@section('content')
<h1>ক্যাশ মেলান</h1>
<p class="muted">হিসাব অনুযায়ী ক্যাশে থাকার কথা <strong>{{ \App\Support\Hisab::money($systemCash) }}</strong>। নোটগুলো গুনে মিলিয়ে নিন।</p>

@livewire('cash-counter')
@endsection
