{{--
  Reusable modal (popup). The markup is ALWAYS in the DOM; visibility is
  driven by Alpine on the client, entangled with the owning Livewire
  component's boolean property — so a tap shows the popup instantly,
  with no server round-trip, and the server state still stays in sync.
  Desktop: centred card. Mobile: bottom sheet (full width, slides up).
  Closes via the × button, backdrop click, or Escape — all client-side
  (entangle syncs the close back to Livewire in the background).
  Usage inside a Livewire view:
    <x-modal wire-prop="showForm" open-event="open-account-modal" title="শিরোনাম"> ... </x-modal>
  Triggers should fire BOTH the window event (instant open) and the
  Livewire event/method (server state), e.g.:
    @click="window.dispatchEvent(new CustomEvent('open-account-modal'))" wire:click="openAdd"
--}}
@props(['title' => '', 'wireProp' => null, 'openEvent' => null])

<div class="modal-overlay"
     x-data="{ open: @entangle($wireProp) }"
     @if($openEvent) x-on:{{ $openEvent }}.window="open = true" @endif
     x-show="open"
     x-cloak
     x-on:keydown.escape.window="open = false"
     @click.self="open = false">
  <div class="modal-card" role="dialog" aria-modal="true" aria-label="{{ $title }}">
    <div class="modal-head">
      <h2>{{ $title }}</h2>
      <button type="button" class="modal-close" @click="open = false" aria-label="বন্ধ করুন">×</button>
    </div>
    <div class="modal-body">
      {{ $slot }}
    </div>
  </div>
</div>
