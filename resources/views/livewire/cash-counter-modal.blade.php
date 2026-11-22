{{-- Modal-only rendering (mounted once in the layout so the popup works on every page). --}}
<div>
  <x-modal wire-prop="showModal" open-event="open-cash-modal" title="ক্যাশ মেলান">
    @include('livewire.cash-counter-form')
  </x-modal>
</div>
