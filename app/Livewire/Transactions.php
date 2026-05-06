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

    public string $filterAccount = '';

    public string $filterCategory = '';

    #[Url]
    public bool $uncategorizedOnly = false;

    public bool $showModal = false;

    public string $newDate = '';

    public string $newDescription = '';

    public string $newAmount = '';

    public string $newAccountId = '';

    public ?string $newCategoryId = null;

    public string $newNotes = '';

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
        return Transaction::with(['account', 'category'])
            ->where('period_id', $this->period->id)
            ->when($this->filterAccount !== '', fn ($q) => $q->where('account_id', (int) $this->filterAccount))
            ->when($this->uncategorizedOnly, fn ($q) => $q->whereNull('category_id'))
            ->when(! $this->uncategorizedOnly && $this->filterCategory !== '', fn ($q) => $q->where('category_id', (int) $this->filterCategory))
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn ($t) => $t->date->format('Y-m-d'));
    }

    #[Computed]
    public function uncategorizedCount(): int
    {
        return Transaction::where('period_id', $this->period->id)
            ->whereNull('category_id')
            ->count();
    }

    public function updatedUncategorizedOnly(): void
    {
        if ($this->uncategorizedOnly) {
            $this->filterCategory = '';
        }
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
        ]);
    }
}
