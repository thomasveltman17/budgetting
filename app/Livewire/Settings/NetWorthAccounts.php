<?php

namespace App\Livewire\Settings;

use App\Models\NetWorthAccount;
use App\Models\NetWorthSnapshot;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class NetWorthAccounts extends Component
{
    public ?int $editingId = null;

    public string $editName = '';

    public string $editType = 'savings';

    public string $editNotes = '';

    public string $newName = '';

    public string $newType = 'savings';

    public string $newStartingBalance = '';

    public string $newNotes = '';

    #[Computed]
    public function netWorthAccounts(): Collection
    {
        return NetWorthAccount::orderBy('sort_order')
            ->withCount('snapshots')
            ->with('latestSnapshot')
            ->get();
    }

    public function startEdit(int $accountId): void
    {
        $account = NetWorthAccount::findOrFail($accountId);
        $this->editingId = $accountId;
        $this->editName = $account->name;
        $this->editType = $account->type;
        $this->editNotes = $account->notes ?? '';
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->resetValidation();
    }

    public function saveEdit(int $accountId): void
    {
        $this->validate([
            'editName' => ['required', 'string', 'max:100'],
            'editType' => ['required', 'in:savings,investment'],
            'editNotes' => ['nullable', 'string', 'max:500'],
        ]);

        NetWorthAccount::findOrFail($accountId)->update([
            'name' => $this->editName,
            'type' => $this->editType,
            'notes' => $this->editNotes ?: null,
        ]);

        $this->editingId = null;
        unset($this->netWorthAccounts);

        $this->dispatch('toast', type: 'success', message: 'Account saved.');
    }

    public function toggleActive(int $accountId): void
    {
        $account = NetWorthAccount::findOrFail($accountId);
        $account->update(['is_active' => ! $account->is_active]);
        unset($this->netWorthAccounts);

        $message = $account->is_active ? 'Account deactivated.' : 'Account activated.';
        $this->dispatch('toast', type: 'info', message: $message);
    }

    public function removeAccount(int $accountId): void
    {
        $account = NetWorthAccount::withCount('snapshots')->findOrFail($accountId);

        if ($account->snapshots_count === 0) {
            $account->delete();
            $this->dispatch('toast', type: 'success', message: 'Account deleted.');
        } else {
            $account->update(['is_active' => false]);
            $this->dispatch('toast', type: 'info', message: 'Account deactivated (has historical data).');
        }

        unset($this->netWorthAccounts);
    }

    public function addAccount(): void
    {
        $this->validate([
            'newName' => ['required', 'string', 'max:100'],
            'newType' => ['required', 'in:savings,investment'],
            'newStartingBalance' => ['nullable', 'numeric'],
            'newNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $maxSortOrder = NetWorthAccount::max('sort_order') ?? 0;

        $account = NetWorthAccount::create([
            'name' => $this->newName,
            'type' => $this->newType,
            'notes' => $this->newNotes ?: null,
            'is_active' => true,
            'sort_order' => $maxSortOrder + 1,
        ]);

        if ($this->newStartingBalance !== '') {
            NetWorthSnapshot::create([
                'net_worth_account_id' => $account->id,
                'balance' => (float) $this->newStartingBalance,
                'recorded_at' => now(),
            ]);
        }

        $this->reset(['newName', 'newStartingBalance', 'newNotes']);
        $this->newType = 'savings';
        unset($this->netWorthAccounts);

        $this->dispatch('toast', type: 'success', message: 'Account added.');
    }

    public function render(): View
    {
        return view('livewire.settings.net-worth-accounts', [
            'netWorthAccounts' => $this->netWorthAccounts,
        ]);
    }
}
