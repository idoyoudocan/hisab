@extends('layouts.admin')
@section('title', 'সেটিংস')
@section('content')
<h1>সেটিংস</h1>
<p class="muted">পেমেন্টের নম্বর আর প্ল্যানের দাম এখান থেকে বদলান — সংরক্ষণ করলেই প্যাকেজ পেজে নতুন দাম দেখা যাবে, কোড বদলাতে হবে না।</p>

<form method="POST" action="{{ route('admin.settings.update') }}">
  @csrf
  @method('PUT')

  <div class="admin-card">
    <h2>পেমেন্ট সেটিংস</h2>
    <p class="muted">এই নম্বর শুধু লগইন করা দোকানদারদের দেখানো হয় — পাবলিক প্যাকেজ পেজে কখনো দেখানো হয় না।</p>
    <label for="bkash_number">বিকাশ নম্বর (পার্সোনাল — Send Money)</label>
    <input id="bkash_number" name="bkash_number" type="tel" inputmode="numeric" value="{{ old('bkash_number', $bkashNumber) }}" placeholder="01XXXXXXXXX" required>
    <label for="whatsapp_number">WhatsApp নম্বর (স্ক্রিনশট পাঠানোর জন্য)</label>
    <input id="whatsapp_number" name="whatsapp_number" type="tel" inputmode="numeric" value="{{ old('whatsapp_number', $whatsappNumber) }}" placeholder="01XXXXXXXXX" required>
    <label>গ্রহণযোগ্য পেমেন্ট মাধ্যম</label>
    @foreach($methodNames as $slug => $label)
      <label style="display:inline-flex;align-items:center;gap:6px;margin:4px 14px 4px 0;font-weight:500">
        <input type="checkbox" name="methods[]" value="{{ $slug }}" style="width:auto" {{ in_array($slug, old('methods', $methods), true) ? 'checked' : '' }}> {{ $label }}
      </label>
    @endforeach
  </div>

  <div class="admin-card">
    <h2>প্ল্যানের দাম ও ছাড়</h2>
    <p class="muted">আসল দাম আর ছাড়ের % বদলান — দিতে হবে টাকা আর মাসপ্রতি খরচ আপনাআপনি হিসাব হবে। ছাড় সর্বোচ্চ ৯০%।</p>
    <table class="admin-table">
      <thead>
        <tr><th>প্ল্যান</th><th>আসল দাম (৳)</th><th>ছাড় (%)</th><th>দিতে হবে</th><th>মাসপ্রতি</th></tr>
      </thead>
      <tbody>
        @foreach($plans as $plan)
          <tr>
            <td><strong>{{ $plan['name'] }}</strong><br><span class="muted">{{ $plan['months'] }} মাস</span></td>
            <td><input name="plans[{{ $plan['slug'] }}][regular]" type="number" min="0" max="1000000" step="1" value="{{ old('plans.'.$plan['slug'].'.regular', $planValues[$plan['slug']]['regular']) }}" required style="max-width:130px"></td>
            <td><input name="plans[{{ $plan['slug'] }}][discount]" type="number" min="0" max="90" step="1" value="{{ old('plans.'.$plan['slug'].'.discount', $planValues[$plan['slug']]['discount']) }}" required style="max-width:90px"></td>
            <td><strong>{{ \App\Support\Hisab::money($planValues[$plan['slug']]['payable']) }}</strong></td>
            <td>{{ \App\Support\Hisab::money($planValues[$plan['slug']]['per_month']) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    <p class="muted">টিপ: বড় প্যাকেজে বেশি ছাড় রাখলে দোকানদার বড় প্যাকেজ নিতে আগ্রহী হয়।</p>
  </div>

  <button class="btn" type="submit">সেটিংস সংরক্ষণ করুন</button>
  <a class="btn ghost" href="{{ route('admin.dashboard') }}">ফিরুন</a>
</form>
@endsection
