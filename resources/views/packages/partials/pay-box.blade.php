{{--
  Payment instructions — LOGGED-IN shopkeepers only.
  Included on the plan-choose page and the pending-request card.
  Never rendered on the public packages page. Number/methods come from
  site settings (/admin/settings), so changing them there updates this
  box automatically.
--}}
@php
  $payBkash = \App\Models\SiteSetting::paymentBkashNumber();
  $payWhatsapp = \App\Models\SiteSetting::paymentWhatsappNumber();
  $payMethods = \App\Models\SiteSetting::paymentMethods();
  $payNames = \App\Models\SiteSetting::paymentMethodNames();
  $payPrimary = in_array('bkash', $payMethods, true) ? 'bkash' : ($payMethods[0] ?? 'bkash');
  $payPrimaryLabel = $payPrimary === 'bkash' ? 'বিকাশ Send Money' : ($payNames[$payPrimary] ?? $payPrimary);
  $payMethodLabels = collect($payMethods)->map(fn ($m) => $payNames[$m] ?? $m)->join(', ');
@endphp
@if($payBkash !== '')
<div class="card pay-box">
  <h2>💳 পেমেন্ট করুন</h2>
  <p><strong>পেমেন্ট: {{ $payPrimaryLabel }} — <a href="tel:{{ $payBkash }}">{{ $payBkash }}</a></strong> (ট্যাপ করলেই কল হবে)</p>
  <p class="muted">পেমেন্ট মাধ্যম: {{ $payMethodLabels }}</p>
  <p>টাকা পাঠানোর পর <a href="tel:{{ $payWhatsapp }}">{{ $payWhatsapp }}</a> নম্বরে WhatsApp-এ ট্রানজেকশন আইডিসহ স্ক্রিনশট পাঠান — পেমেন্ট পেলেই অ্যাডমিন আপনার মেয়াদ বাড়িয়ে দেবেন।</p>
</div>
@endif
