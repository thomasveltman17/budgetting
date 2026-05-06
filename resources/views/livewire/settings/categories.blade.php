<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">

    {{-- Category rows --}}
    @foreach ($categories as $index => $category)
        @php $isLast = $index === $categories->count() - 1; @endphp

        <div class="flex items-center gap-3 px-4 py-3.5 {{ $isLast && $editingId !== $category->id ? '' : 'border-b border-gray-50' }}
            {{ $category->is_archived ? 'opacity-60' : '' }}">

            @if ($editingId === $category->id)
                {{-- Edit mode --}}
                <input
                    type="color"
                    wire:model="editColor"
                    class="w-8 h-8 rounded-full border-2 border-gray-200 cursor-pointer shrink-0 p-0.5"
                    title="Pick color"
                >
                <input
                    type="text"
                    wire:model="editName"
                    wire:keydown.enter="saveEdit({{ $category->id }})"
                    wire:keydown.escape="cancelEdit"
                    class="flex-1 min-w-0 border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    autofocus
                >
                @error('editName')
                    <span class="text-xs text-red-500 shrink-0">{{ $message }}</span>
                @enderror
                @error('editColor')
                    <span class="text-xs text-red-500 shrink-0">{{ $message }}</span>
                @enderror
                <div class="flex items-center gap-1.5 shrink-0">
                    <button
                        wire:click="saveEdit({{ $category->id }})"
                        class="px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                    >Save</button>
                    <button
                        wire:click="cancelEdit"
                        class="px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                    >Cancel</button>
                </div>

            @else
                {{-- View mode --}}
                <span class="w-4 h-4 rounded-full shrink-0 ring-1 ring-black/10" style="background-color: {{ $category->color }}"></span>

                <span class="flex-1 text-sm font-medium text-gray-900 min-w-0 truncate">{{ $category->name }}</span>

                <span class="text-xs font-medium px-2 py-0.5 rounded-full shrink-0
                    {{ match($category->type) {
                        'transactional' => 'bg-blue-50 text-blue-700',
                        'savings'       => 'bg-emerald-50 text-emerald-700',
                        'investment'    => 'bg-violet-50 text-violet-700',
                        default         => 'bg-gray-100 text-gray-600',
                    } }}">
                    {{ ucfirst($category->type) }}
                </span>

                @if ($category->is_archived)
                    <span class="text-xs font-medium text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full shrink-0">Archived</span>
                @endif

                {{-- Sort arrows --}}
                <div class="flex items-center gap-0.5 shrink-0">
                    <button
                        wire:click="moveUp({{ $category->id }})"
                        class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-colors disabled:opacity-30"
                        @if ($index === 0) disabled @endif
                        title="Move up"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                        </svg>
                    </button>
                    <button
                        wire:click="moveDown({{ $category->id }})"
                        class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition-colors disabled:opacity-30"
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
                    class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors shrink-0"
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
                    class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors shrink-0"
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
    <div class="px-4 py-4 bg-gray-50 border-t border-gray-100">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Add Category</p>
        <div class="flex items-start gap-3">
            <input
                type="color"
                wire:model="newColor"
                class="w-9 h-9 rounded-lg border-2 border-gray-200 cursor-pointer shrink-0 p-0.5 mt-0.5"
                title="Pick color"
            >
            <div class="flex-1 grid grid-cols-2 gap-2">
                <div>
                    <input
                        type="text"
                        wire:model="newName"
                        placeholder="Category name"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    >
                    @error('newName')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <select
                        wire:model="newType"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    >
                        <option value="transactional">Transactional</option>
                        <option value="savings">Savings</option>
                        <option value="investment">Investment</option>
                    </select>
                </div>
            </div>
            <button
                wire:click="addCategory"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-70 cursor-not-allowed"
                wire:target="addCategory"
                class="shrink-0 px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shadow-sm"
            >Add</button>
        </div>
    </div>

</div>
