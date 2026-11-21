{{-- Modal-only rendering (mounted once in the layout so the popup works on every page). --}}
<div>
  <x-modal :show="$showModal" wire-prop="showModal" title="ক্যাশ মেলান">
    @include('livewire.cash-counter-form')
  </x-modal>
</div>
