<?php

namespace App\Livewire;

use App\Models\Business;
use App\Support\Hisab;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CashCounter extends Component
{
    public int $businessId;
    /** counts keyed by denomination, e.g. ['1000' => 3] */
    public array $counts = [];
    public string $note = '';
    public bool $showModal = false;
    /** When true (dashboard/layout), only the modal renders — no history. */
    public bool $modalOnly = false;
    public ?string $lastResult = null;

    public function mount(bool $modalOnly = false): void
    {
        $this->businessId = Hisab::requireBusiness()->id;
        $this->modalOnly = $modalOnly;
        $this->resetCounts();
    }

    /** Opened from the dashboard, bottom nav or any page (Livewire.dispatch). */
    #[On('open-cash-modal')]
    public function openModal(): void
    {
        $this->lastResult = null;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function resetCounts(): void
    {
        foreach (Hisab::denominations() as $d) {
            $this->counts[(string) $d] = 0;
        }
    }

    public function increment(int $denomination): void
    {
        $key = (string) $denomination;
        $this->counts[$key] = (int) ($this->counts[$key] ?? 0) + 1;
    }

    public function decrement(int $denomination): void
    {
        $key = (string) $denomination;
        $this->counts[$key] = max(0, (int) ($this->counts[$key] ?? 0) - 1);
    }

    #[Computed]
    public function total(): float
    {
        $total = 0.0;
        foreach ($this->counts as $denomination => $qty) {
            $total += (float) $denomination * max(0, (int) $qty);
        }

        return round($total, 2);
    }

    #[Computed]
    public function systemCash(): float
    {
        return Hisab::cashBalance(Business::findOrFail($this->businessId));
    }

    #[Computed]
    public function difference(): float
    {
        return round($this->total - $this->systemCash, 2);
    }

    #[Computed]
    public function isMatched(): bool
    {
        return abs($this->difference) < 0.01;
    }

    public function save(): void
    {
        $business = Business::findOrFail($this->businessId);

        $this->validate([
            'counts.*' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $denominations = [];
        foreach (Hisab::denominations() as $d) {
            $denominations[(string) $d] = (int) ($this->counts[(string) $d] ?? 0);
        }

        $business->cashCounts()->create([
            'counted_at' => now(),
            'denominations' => $denominations,
            'total' => $this->total,
            'system_cash' => $this->systemCash,
            'difference' => $this->difference,
            'note' => $this->note ?: null,
        ]);

        $matched = $this->isMatched;
        $difference = $this->difference;
        $this->resetCounts();
        $this->note = '';
        $this->lastResult = $matched
            ? 'ক্যাশ মিলেছে ✓ — হিসাবের সাথে গোনা টাকা পুরোপুরি মিলেছে।'
            : 'গোনা হয়েছে। পার্থক্য '.Hisab::money(abs($difference)).($difference < 0 ? ' কম' : ' বেশি').' — খতিয়ান দেখে নিন।';
        session()->flash('status', $this->lastResult);
    }

    public function render()
    {
        $business = Business::findOrFail($this->businessId);

        if ($this->modalOnly) {
            return view('livewire.cash-counter-modal', [
                'denominations' => Hisab::denominations(),
            ]);
        }

        return view('livewire.cash-counter', [
            'denominations' => Hisab::denominations(),
            'history' => $business->cashCounts()
                ->latest('counted_at')->latest('id')
                ->take(20)
                ->get(),
        ]);
    }
}
