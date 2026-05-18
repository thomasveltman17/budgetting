<div class="rounded-2xl overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border);">

    {{-- Category rows --}}
    @foreach ($categories as $index => $category)
        @php $isLast = $index === $categories->count() - 1; @endphp

        <div class="flex items-center gap-3 px-4 py-3.5 {{ $category->is_archived ? 'opacity-50' : '' }}"
             style="{{ $isLast && $editingId !== $category->id ? '' : 'border-bottom: 1px solid var(--c-border-subtle);' }}">

            @if ($editingId === $category->id)
                {{-- Edit mode --}}
                <input
                    type="color"
                    wire:model="editColor"
                    class="w-8 h-8 rounded-full cursor-pointer shrink-0 p-0.5"
                    style="border: 2px solid var(--c-border-medium); background: transparent;"
                    title="Pick color"
                >
                <input
                    type="text"
                    wire:model="editName"
                    wire:keydown.enter="saveEdit({{ $category->id }})"
                    wire:keydown.escape="cancelEdit"
                    class="th-input flex-1 min-w-0 rounded-lg px-3 py-1.5 text-sm"
                    style="border-color: var(--c-brand);"
                    autofocus
                >
                <select wire:model="editType" class="th-select rounded-lg px-3 py-1.5 text-sm shrink-0">
                    <option value="transactional">Transactional</option>
                    <option value="savings">Savings</option>
                    <option value="investment">Investment</option>
                    <option value="income">Income</option>
                </select>
                @error('editName')
                    <span class="text-xs shrink-0" style="color: var(--c-expense);">{{ $message }}</span>
                @enderror
                @error('editColor')
                    <span class="text-xs shrink-0" style="color: var(--c-expense);">{{ $message }}</span>
                @enderror
                <div class="flex items-center gap-1.5 shrink-0">
                    <button
                        wire:click="saveEdit({{ $category->id }})"
                        class="px-3 py-1.5 text-xs font-semibold text-white rounded-lg transition-all"
                        style="background: linear-gradient(135deg, #7C6FF7, #5B4FD4);"
                    >Save</button>
                    <button
                        wire:click="cancelEdit"
                        class="th-btn-secondary px-3 py-1.5 text-xs font-medium rounded-lg transition-colors"
                    >Cancel</button>
                </div>

            @else
                {{-- View mode --}}
                <span class="w-4 h-4 rounded-full shrink-0" style="background-color: {{ $category->color }}; box-shadow: 0 0 0 1px rgba(0,0,0,0.2);"></span>

                <span class="flex-1 text-sm font-medium min-w-0 truncate" style="color: var(--c-text-1);">{{ $category->name }}</span>

                <span class="text-xs font-medium px-2 py-0.5 rounded-full shrink-0"
                    style="{{ match($category->type) {
                        'transactional' => 'background-color: var(--c-rabo-bg); color: var(--c-rabo-text);',
                        'savings'       => 'background-color: var(--c-income-bg); color: var(--c-income);',
                        'investment'    => 'background-color: var(--c-revolut-bg); color: var(--c-revolut-text);',
                        'income'        => 'background-color: rgba(245,158,11,0.12); color: #D97706;',
                        default         => 'background-color: var(--c-border); color: var(--c-text-2);',
                    } }}">
                    {{ ucfirst($category->type) }}
                </span>

                @if ($category->is_archived)
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full shrink-0" style="background-color: var(--c-border); color: var(--c-text-3);">Archived</span>
                @endif

                {{-- Sort arrows --}}
                <div class="flex items-center gap-0.5 shrink-0">
                    <button
                        wire:click="moveUp({{ $category->id }})"
                        class="th-btn th-btn-ghost p-1 rounded disabled:opacity-30 disabled:pointer-events-none"
                        @if ($index === 0) disabled @endif
                        title="Move up"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                        </svg>
                    </button>
                    <button
                        wire:click="moveDown({{ $category->id }})"
                        class="th-btn th-btn-ghost p-1 rounded disabled:opacity-30 disabled:pointer-events-none"
                        @if ($isLast) disabled @endif
                        title="Move down"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                </div>

                {{-- Archive toggle --}}
                <button
                    wire:click="toggleArchive({{ $category->id }})"
                    title="{{ $category->is_archived ? 'Unarchive' : 'Archive' }}"
                    class="th-btn th-btn-ghost p-1.5 rounded-lg shrink-0"
                >
                    @if ($category->is_archived)
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                    @else
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                    @endif
                </button>

                {{-- Edit button --}}
                <button
                    wire:click="startEdit({{ $category->id }})"
                    class="th-btn th-btn-brand p-1.5 rounded-lg shrink-0"
                    title="Edit"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                    </svg>
                </button>
            @endif
        </div>
    @endforeach

    {{-- Add category form --}}
    <div class="px-4 py-4" style="border-top: 1px solid var(--c-border); background-color: var(--c-footer);">
        <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--c-text-3);">Add Category</p>
        <div class="flex items-start gap-3">
            <input
                type="color"
                wire:model="newColor"
                class="w-9 h-9 rounded-lg cursor-pointer shrink-0 p-0.5 mt-0.5"
                style="border: 2px solid var(--c-border-medium); background: transparent;"
                title="Pick color"
            >
            <div class="flex-1 grid grid-cols-2 gap-2">
                <div>
                    <input
                        type="text"
                        wire:model="newName"
                        placeholder="Category name"
                        class="th-input w-full rounded-lg px-3 py-2 text-sm"
                    >
                    @error('newName')
                        <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <select
                        wire:model="newType"
                        class="th-select w-full rounded-lg px-3 py-2 text-sm"
                    >
                        <option value="transactional">Transactional</option>
                        <option value="savings">Savings</option>
                        <option value="investment">Investment</option>
                        <option value="income">Income</option>
                    </select>
                </div>
            </div>
            <button
                wire:click="addCategory"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-70 cursor-not-allowed"
                wire:target="addCategory"
                class="shrink-0 px-4 py-2 text-sm font-semibold text-white rounded-lg transition-all"
                style="background: linear-gradient(135deg, #7C6FF7, #5B4FD4);"
            >Add</button>
        </div>
    </div>

</div>
