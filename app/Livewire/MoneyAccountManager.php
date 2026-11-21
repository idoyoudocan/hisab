<?php

namespace App\Livewire;

use App\Models\Business;
use App\Models\MoneyAccount;
use Illuminate\Validation\Rule;
use Livewire\Component;

class MoneyAccountManager extends Component
{
    public int $businessId;
    public ?int $editId = null;
    public string $type = MoneyAccount::TYPE_BANK;
    public string $provider = '';
    public string $label = '';
    public string $identifier = '';
    public string $balance = '';
    public string $note = '';

    public function mount(): void
    {
        $this->businessId = \App\Support\Hisab::requireBusiness()->id;
        $this->provider = MoneyAccount::providersFor($this->type)[0];
    }

    public function updatedType(): void
    {
        $this->provider = MoneyAccount::providersFor($this->type)[0];
    }

    public function editAccount(int $id): void
    {
        $account = Business::findOrFail($this->businessId)->moneyAccounts()->findOrFail($id);
        if ($account->isRegister()) {
            return; // register balance is derived, never edited
        }
        $this->editId = $account->id;
        $this->type = $account->type;
        $this->provider = $account->provider ?? '';
        $this->label = $account->label;
        $this->identifier = $account->identifier ?? '';
        $this->balance = (string) (float) $account->balance;
        $this->note = $account->note ?? '';
    }

    public function cancelEdit(): void
    {
        $this->reset(['editId', 'provider', 'label', 'identifier', 'balance', 'note']);
        $this->type = MoneyAccount::TYPE_BANK;
        $this->provider = MoneyAccount::providersFor($this->type)[0];
    }

    public function save(): void
    {
        $business = Business::findOrFail($this->businessId);

        $data = $this->validate([
            'type' => ['required', 'string', Rule::in([
                MoneyAccount::TYPE_BANK, MoneyAccount::TYPE_MOBILE_BANKING,
                MoneyAccount::TYPE_SIM, MoneyAccount::TYPE_OTHER,
            ])],
            'provider' => ['nullable', 'string', 'max:100'],
            'label' => ['required', 'string', 'max:150'],
            'identifier' => ['nullable', 'string', 'max:100'],
            'balance' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $data['balance'] = (float) $data['balance'];

        if ($this->editId) {
            $business->moneyAccounts()->findOrFail($this->editId)->update($data);
            session()->flash('status', 'অ্যাকাউন্টের তথ্য হালনাগাদ হয়েছে।');
        } else {
            $business->moneyAccounts()->create($data);
            session()->flash('status', 'নতুন অ্যাকাউন্ট যোগ হয়েছে।');
        }
        $this->cancelEdit();
    }

    public function delete(int $id): void
    {
        $account = Business::findOrFail($this->businessId)->moneyAccounts()->findOrFail($id);
        if ($account->isRegister()) {
            return;
        }
        $account->delete();
        session()->flash('status', 'অ্যাকাউন্ট মুছে ফেলা হয়েছে।');
    }

    public function render()
    {
        $business = Business::findOrFail($this->businessId);
        \App\Models\MoneyAccount::ensureRegister($business);

        $accounts = $business->moneyAccounts()->orderBy('type')->orderBy('label')->get();
        $register = $accounts->firstWhere('type', MoneyAccount::TYPE_CASH_REGISTER);
        $others = $accounts->where('type', '!=', MoneyAccount::TYPE_CASH_REGISTER);
        $totals = [];
        foreach (MoneyAccount::types() as $type => $label) {
            if ($type === MoneyAccount::TYPE_CASH_REGISTER) {
                continue;
            }
            $totals[$type] = \App\Support\Hisab::accountTotal($business, $type);
        }

        return view('livewire.money-account-manager', [
            'types' => MoneyAccount::types(),
            'providers' => MoneyAccount::providersFor($this->type),
            'business' => $business,
            'register' => $register,
            'others' => $others,
            'totals' => $totals,
        ]);
    }
}
