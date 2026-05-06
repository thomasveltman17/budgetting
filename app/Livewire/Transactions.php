<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\Period;
use App\Models\Transaction;
use App\Services\Import\AmexImporter;
use App\Services\Import\RabobankImporter;
use App\Services\Import\RevolutImporter;
use App\Services\PeriodService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Transactions extends Component
{
    use WithFileUploads;

    #[Url]
    public string $search = '';

    public string $filterAccount = '';

    public string $filterCategory = '';

    #[Url]
    public string $filterType = '';

    #[Url]
    public bool $filterPendingReturn = false;

    #[Url]
    public bool $uncategorizedOnly = false;

    public bool $showModal = false;

    public string $newDate = '';

    public string $newDescription = '';

    public string $newAmount = '';

    public string $newAccountId = '';

    public ?string $newCategoryId = null;

    public string $newNotes = '';

    public bool $showEditModal = false;

    public ?int $editTransactionId = null;

    public string $editDate = '';

    public string $editDescription = '';

    public string $editAmount = '';

    public string $editAccountId = '';

    public ?string $editCategoryId = null;

    public string $editNotes = '';

    public bool $showLinkModal = false;

    public ?int $linkingTransactionId = null;

    public bool $showImportModal = false;

    public string $importBank = '';

    public string $importAccountId = '';

    /** @var TemporaryUploadedFile|null */
    public $importFile = null;

    public ?array $importResult = null;

    public function mount(): void
    {
        $this->newDate = now()->format('Y-m-d');
    }

    #[Computed]
    public function period(): Period
    {
        return app(PeriodService::class)->getSelectedPeriod();
    }

    #[Computed]
    public function accounts(): Collection
    {
        return Account::orderBy('label')->get();
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::where('is_archived', false)->orderBy('sort_order')->get();
    }

    #[Computed]
    public function transactions(): Collection
    {
        return Transaction::with(['account', 'category', 'repayments.account'])
            ->where('period_id', $this->period->id)
            ->whereNull('parent_transaction_id')
            ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('description', 'like', '%'.$this->search.'%')
                ->orWhere('notes', 'like', '%'.$this->search.'%')
            ))
            ->when($this->filterAccount !== '', fn ($q) => $q->where('account_id', (int) $this->filterAccount))
            ->when($this->filterType === 'expense', fn ($q) => $q->where('amount', '<', 0))
            ->when($this->filterType === 'income', fn ($q) => $q->where('amount', '>', 0))
            ->when($this->filterPendingReturn, fn ($q) => $q->where('is_pending_return', true))
            ->when($this->uncategorizedOnly, fn ($q) => $q->whereNull('category_id'))
            ->when(! $this->uncategorizedOnly && $this->filterCategory !== '', fn ($q) => $q->where('category_id', (int) $this->filterCategory))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($t) => $t->date->format('Y-m-d'));
    }

    #[Computed]
    public function hasActiveFilters(): bool
    {
        return $this->search !== ''
            || $this->filterAccount !== ''
            || $this->filterCategory !== ''
            || $this->filterType !== ''
            || $this->filterPendingReturn
            || $this->uncategorizedOnly;
    }

    #[Computed]
    public function uncategorizedCount(): int
    {
        return Transaction::where('period_id', $this->period->id)
            ->whereNull('parent_transaction_id')
            ->whereNull('category_id')
            ->count();
    }

    #[Computed]
    public function linkableTransactions(): Collection
    {
        return Transaction::with('account')
            ->whereNull('parent_transaction_id')
            ->where('amount', '>', 0)
            ->when($this->linkingTransactionId, fn ($q) => $q->where('id', '!=', $this->linkingTransactionId))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
    }

    public function updatedUncategorizedOnly(): void
    {
        if ($this->uncategorizedOnly) {
            $this->filterCategory = '';
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->filterAccount = '';
        $this->filterCategory = '';
        $this->filterType = '';
        $this->filterPendingReturn = false;
        $this->uncategorizedOnly = false;
    }

    public function updateCategory(int $transactionId, mixed $categoryId): void
    {
        Transaction::findOrFail($transactionId)->update([
            'category_id' => ($categoryId !== '' && $categoryId !== '0' && $categoryId !== null)
                ? (int) $categoryId
                : null,
        ]);

        unset($this->transactions, $this->uncategorizedCount);
    }

    public function deleteTransaction(int $transactionId): void
    {
        Transaction::findOrFail($transactionId)->delete();

        unset($this->transactions, $this->uncategorizedCount);

        $this->dispatch('toast', type: 'success', message: 'Transaction deleted.');
    }

    public function openModal(): void
    {
        $this->reset(['newDescription', 'newAmount', 'newAccountId', 'newCategoryId', 'newNotes']);
        $this->resetValidation();
        $this->newDate = now()->format('Y-m-d');
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->validate([
            'newDate' => ['required', 'date'],
            'newDescription' => ['required', 'string', 'max:255'],
            'newAmount' => ['required', 'numeric'],
            'newAccountId' => ['required', 'exists:accounts,id'],
            'newCategoryId' => ['nullable', 'exists:categories,id'],
            'newNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        Transaction::create([
            'period_id' => $this->period->id,
            'account_id' => (int) $this->newAccountId,
            'category_id' => $this->newCategoryId ? (int) $this->newCategoryId : null,
            'date' => $this->newDate,
            'description' => $this->newDescription,
            'amount' => (float) $this->newAmount,
            'source' => 'manual',
            'notes' => $this->newNotes ?: null,
        ]);

        $this->showModal = false;
        unset($this->transactions, $this->uncategorizedCount);

        $this->dispatch('toast', type: 'success', message: 'Transaction added.');
    }

    public function deleteSelected(array $ids): void
    {
        Transaction::whereIn('id', $ids)->delete();

        unset($this->transactions, $this->uncategorizedCount);

        $this->dispatch('toast', type: 'success', message: count($ids).' transaction(s) deleted.');
    }

    public function startEdit(int $transactionId): void
    {
        $transaction = Transaction::findOrFail($transactionId);
        $this->editTransactionId = $transaction->id;
        $this->editDate = $transaction->date->format('Y-m-d');
        $this->editDescription = $transaction->description;
        $this->editAmount = (string) $transaction->amount;
        $this->editAccountId = (string) $transaction->account_id;
        $this->editCategoryId = $transaction->category_id ? (string) $transaction->category_id : null;
        $this->editNotes = $transaction->notes ?? '';
        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function saveEdit(): void
    {
        $this->validate([
            'editDate' => ['required', 'date'],
            'editDescription' => ['required', 'string', 'max:255'],
            'editAmount' => ['required', 'numeric'],
            'editAccountId' => ['required', 'exists:accounts,id'],
            'editCategoryId' => ['nullable', 'exists:categories,id'],
            'editNotes' => ['nullable', 'string', 'max:1000'],
        ]);

        Transaction::findOrFail($this->editTransactionId)->update([
            'date' => $this->editDate,
            'description' => $this->editDescription,
            'amount' => (float) $this->editAmount,
            'account_id' => (int) $this->editAccountId,
            'category_id' => $this->editCategoryId ? (int) $this->editCategoryId : null,
            'notes' => $this->editNotes ?: null,
        ]);

        $this->showEditModal = false;
        unset($this->transactions, $this->uncategorizedCount);

        $this->dispatch('toast', type: 'success', message: 'Transaction updated.');
    }

    public function cancelEdit(): void
    {
        $this->showEditModal = false;
        $this->resetValidation();
    }

    public function openLinkModal(int $transactionId): void
    {
        $this->linkingTransactionId = $transactionId;
        unset($this->linkableTransactions);
        $this->showLinkModal = true;
    }

    public function closeLinkModal(): void
    {
        $this->showLinkModal = false;
        $this->linkingTransactionId = null;
    }

    public function linkRepayment(int $repaymentId): void
    {
        Transaction::findOrFail($repaymentId)->update([
            'parent_transaction_id' => $this->linkingTransactionId,
        ]);

        unset($this->transactions, $this->linkableTransactions);

        $this->dispatch('toast', type: 'success', message: 'Repayment linked.');
    }

    public function unlinkRepayment(int $repaymentId): void
    {
        Transaction::findOrFail($repaymentId)->update([
            'parent_transaction_id' => null,
        ]);

        unset($this->transactions);

        $this->dispatch('toast', type: 'success', message: 'Repayment unlinked.');
    }

    public function togglePendingReturn(int $transactionId): void
    {
        $transaction = Transaction::findOrFail($transactionId);
        $transaction->update(['is_pending_return' => ! $transaction->is_pending_return]);

        unset($this->transactions);

        $message = $transaction->is_pending_return
            ? 'Marked as pending return — excluded from totals.'
            : 'Pending return flag removed.';

        $this->dispatch('toast', type: 'info', message: $message);
    }

    public function openImportModal(): void
    {
        $this->reset(['importBank', 'importAccountId', 'importFile', 'importResult']);
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
        $this->reset(['importBank', 'importAccountId', 'importFile', 'importResult']);
        $this->resetValidation();
    }

    public function runImport(): void
    {
        $this->validate([
            'importBank' => ['required', 'in:rabobank,revolut,amex'],
            'importAccountId' => ['required', 'exists:accounts,id'],
            'importFile' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
        ]);

        $path = $this->importFile->getRealPath();

        $importer = match ($this->importBank) {
            'rabobank' => new RabobankImporter,
            'revolut' => new RevolutImporter,
            'amex' => new AmexImporter,
        };

        $result = $importer->import($path, (int) $this->importAccountId);

        $this->importResult = [
            'imported' => $result->imported,
            'skipped' => $result->skippedDuplicates,
            'errors' => $result->errors,
        ];

        unset($this->transactions, $this->uncategorizedCount);

        $this->dispatch('toast', type: 'success', message: "{$result->imported} transaction(s) imported.");
    }

    public function render(): View
    {
        return view('livewire.transactions', [
            'period' => $this->period,
            'accounts' => $this->accounts,
            'categories' => $this->categories,
            'transactions' => $this->transactions,
            'uncategorizedCount' => $this->uncategorizedCount,
            'linkableTransactions' => $this->linkableTransactions,
            'hasActiveFilters' => $this->hasActiveFilters,
        ]);
    }
}
