<?php

namespace App\Livewire\Settings;

use App\Models\BudgetTarget;
use App\Models\Category;
use App\Models\Period;
use App\Models\Transaction;
use App\Services\PeriodService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BudgetTargets extends Component
{
    /** @var array<string, string> */
    public array $targetAmounts = [];

    public function mount(): void
    {
        $this->loadTargets();
    }

    #[Computed]
    public function period(): Period
    {
        return app(PeriodService::class)->getSelectedPeriod();
    }

    #[Computed]
    public function transactionalCategories(): Collection
    {
        return Category::where('is_archived', false)
            ->where('type', 'transactional')
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function categorySpend(): array
    {
        return Transaction::where('period_id', $this->period->id)
            ->where('amount', '<', 0)
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->pluck('total', 'category_id')
            ->map(fn ($total) => abs((float) $total))
            ->toArray();
    }

    public function saveTarget(int $categoryId): void
    {
        $key = (string) $categoryId;

        $this->validate([
            "targetAmounts.{$key}" => ['nullable', 'numeric', 'min:0'],
        ]);

        $amount = $this->targetAmounts[$key] ?? '';

        if ($amount === '' || $amount === null) {
            BudgetTarget::where('category_id', $categoryId)
                ->where('period_id', $this->period->id)
                ->delete();

            $this->dispatch('toast', type: 'info', message: 'Budget target cleared.');
        } else {
            BudgetTarget::updateOrCreate(
                ['category_id' => $categoryId, 'period_id' => $this->period->id],
                ['amount' => (float) $amount],
            );

            $this->dispatch('toast', type: 'success', message: 'Budget target saved.');
        }

        unset($this->categorySpend);
    }

    public function render(): View
    {
        return view('livewire.settings.budget-targets', [
            'period' => $this->period,
            'transactionalCategories' => $this->transactionalCategories,
            'categorySpend' => $this->categorySpend,
        ]);
    }

    private function loadTargets(): void
    {
        $periodId = $this->period->id;
        $targets = BudgetTarget::where('period_id', $periodId)->get()->keyBy('category_id');

        foreach ($this->transactionalCategories as $category) {
            $key = (string) $category->id;
            $this->targetAmounts[$key] = $targets->has($category->id)
                ? number_format((float) $targets->get($category->id)->amount, 2, '.', '')
                : '';
        }
    }
}
