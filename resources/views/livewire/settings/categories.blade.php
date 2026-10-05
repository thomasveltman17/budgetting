@php
    $typeLabels = ['transactional' => 'Spending', 'savings' => 'Savings', 'investment' => 'Investment', 'income' => 'Income'];
    $swatchClasses = 'shrink-0 cursor-pointer rounded-md border border-line-strong bg-surface p-[3px] [&::-webkit-color-swatch]:rounded-[3px] [&::-webkit-color-swatch]:border-0 [&::-webkit-color-swatch-wrapper]:p-0';
@endphp

<div class="rounded-xl border border-line bg-surface">
    <ul class="divide-y divide-line">
        @foreach ($categories as $index => $category)
            @php $isLast = $index === $categories->count() - 1; @endphp

            <li class="flex min-h-12 items-center gap-3 px-4 py-2 {{ $category->is_archived ? 'text-ink-3' : '' }}">
                @if ($editingId === $category->id)
                    <input type="color" wire:model="editColor" title="Colour" class="size-8 {{ $swatchClasses }}">
                    <div class="min-w-0 flex-1">
                        <input type="text" wire:model="editName"
                               wire:keydown.enter="saveEdit({{ $category->id }})"
                               wire:keydown.escape="cancelEdit"
                               aria-label="Category name"
                               class="field h-8" autofocus>
                        <x-field-error name="editName" />
                        <x-field-error name="editColor" />
                    </div>
                    <button type="button" wire:click="saveEdit({{ $category->id }})" class="btn btn-primary btn-sm">Save</button>
                    <button type="button" wire:click="cancelEdit" class="btn btn-quiet btn-sm">Cancel</button>
                @else
                    <span class="size-3 shrink-0 rounded-[3px] {{ $category->is_archived ? 'opacity-50' : '' }}" style="background-color: {{ $category->color }}"></span>
                    <span class="min-w-0 flex-1 truncate text-sm {{ $category->is_archived ? '' : 'text-ink' }}">{{ $category->name }}</span>

                    @if ($category->is_archived)
                        <span class="shrink-0 rounded border border-line-strong px-1.5 text-[0.6875rem] leading-[1.125rem]">Archived</span>
                    @endif
                    <span class="hidden w-20 shrink-0 text-[0.8125rem] text-ink-3 sm:block">{{ $typeLabels[$category->type] ?? ucfirst($category->type) }}</span>

                    <div class="flex shrink-0 items-center">
                        <button type="button" wire:click="moveUp({{ $category->id }})" @disabled($index === 0) title="Move up" class="icon-btn disabled:pointer-events-none disabled:opacity-30">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 15.75 7.5-7.5 7.5 7.5" /></svg>
                        </button>
                        <button type="button" wire:click="moveDown({{ $category->id }})" @disabled($isLast) title="Move down" class="icon-btn disabled:pointer-events-none disabled:opacity-30">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <button type="button" wire:click="toggleArchive({{ $category->id }})" title="{{ $category->is_archived ? 'Restore' : 'Archive' }}" class="icon-btn">
                            @if ($category->is_archived)
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                            @else
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>
                            @endif
                        </button>
                        <button type="button" wire:click="startEdit({{ $category->id }})" title="Edit" class="icon-btn">
                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                        </button>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>

    {{-- New category --}}
    <div class="rounded-b-xl border-t border-line bg-sunken/40 px-4 py-4">
        <p class="mb-2.5 text-xs text-ink-3">New category</p>
        <div class="flex flex-wrap items-start gap-2">
            <input type="color" wire:model="newColor" title="Colour" class="size-9 {{ $swatchClasses }}">
            <div class="min-w-40 flex-1">
                <input type="text" wire:model="newName" wire:keydown.enter="addCategory" placeholder="Name" aria-label="New category name" class="field">
                <x-field-error name="newName" />
            </div>
            <select wire:model="newType" aria-label="New category type" class="field w-36">
                <option value="transactional">Spending</option>
                <option value="savings">Savings</option>
                <option value="investment">Investment</option>
            </select>
            <button type="button" wire:click="addCategory" wire:loading.attr="disabled" wire:target="addCategory" class="btn btn-secondary">Add category</button>
        </div>
    </div>
</div>
