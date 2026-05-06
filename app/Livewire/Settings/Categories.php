<?php

namespace App\Livewire\Settings;

use App\Models\Category;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Categories extends Component
{
    public ?int $editingId = null;

    public string $editName = '';

    public string $editColor = '#3b82f6';

    public string $newName = '';

    public string $newColor = '#3b82f6';

    public string $newType = 'transactional';

    #[Computed]
    public function categories(): Collection
    {
        return Category::orderBy('sort_order')->get();
    }

    public function startEdit(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $this->editingId = $categoryId;
        $this->editName = $category->name;
        $this->editColor = $category->color;
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->resetValidation();
    }

    public function saveEdit(int $categoryId): void
    {
        $this->validate([
            'editName' => ['required', 'string', 'max:100'],
            'editColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        Category::findOrFail($categoryId)->update([
            'name' => $this->editName,
            'color' => $this->editColor,
        ]);

        $this->editingId = null;
        unset($this->categories);

        $this->dispatch('toast', type: 'success', message: 'Category saved.');
    }

    public function toggleArchive(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $category->update(['is_archived' => ! $category->is_archived]);
        unset($this->categories);

        $message = $category->is_archived ? 'Category unarchived.' : 'Category archived.';
        $this->dispatch('toast', type: 'info', message: $message);
    }

    public function moveUp(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $previous = Category::where('sort_order', '<', $category->sort_order)
            ->orderByDesc('sort_order')
            ->first();

        if ($previous) {
            [$category->sort_order, $previous->sort_order] = [$previous->sort_order, $category->sort_order];
            $category->save();
            $previous->save();
            unset($this->categories);
        }
    }

    public function moveDown(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $next = Category::where('sort_order', '>', $category->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($next) {
            [$category->sort_order, $next->sort_order] = [$next->sort_order, $category->sort_order];
            $category->save();
            $next->save();
            unset($this->categories);
        }
    }

    public function addCategory(): void
    {
        $this->validate([
            'newName' => ['required', 'string', 'max:100'],
            'newColor' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'newType' => ['required', 'in:transactional,savings,investment'],
        ]);

        $maxSortOrder = Category::max('sort_order') ?? 0;

        Category::create([
            'name' => $this->newName,
            'color' => $this->newColor,
            'type' => $this->newType,
            'is_archived' => false,
            'sort_order' => $maxSortOrder + 1,
        ]);

        $this->reset(['newName', 'newType']);
        $this->newColor = '#3b82f6';
        $this->newType = 'transactional';
        unset($this->categories);

        $this->dispatch('toast', type: 'success', message: 'Category added.');
    }

    public function render(): View
    {
        return view('livewire.settings.categories', [
            'categories' => $this->categories,
        ]);
    }
}
