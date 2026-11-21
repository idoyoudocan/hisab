{{--
  Reusable modal (popup). Livewire-driven: the owning Livewire component
  holds a boolean property and passes its name as :wire-prop.
  Desktop: centred card. Mobile: bottom sheet (full width, slides up).
  Closes via the × button, backdrop click, or Escape. No page reload.
  Usage inside a Livewire view:
    <x-modal :show="$showForm" wire-prop="showForm" title="শিরোনাম"> ... </x-modal>
--}}
@props(['show' => false, 'title' => '', 'wireProp' => null])

@if($show)
<div class="modal-overlay"
     x-data
     @keydown.escape.window="$wire.set('{{ $wireProp }}', false)"
     @click.self="$wire.set('{{ $wireProp }}', false)">
  <div class="modal-card" role="dialog" aria-modal="true" aria-label="{{ $title }}">
    <div class="modal-head">
      <h2>{{ $title }}</h2>
      <button type="button" class="modal-close" @click="$wire.set('{{ $wireProp }}', false)" aria-label="বন্ধ করুন">×</button>
    </div>
    <div class="modal-body">
      {{ $slot }}
    </div>
  </div>
</div>
@endif
