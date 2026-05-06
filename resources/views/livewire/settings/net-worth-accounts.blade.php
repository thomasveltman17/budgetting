<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">

    @forelse ($netWorthAccounts as $index => $account)
        @php
            $isLast = $index === $netWorthAccounts->count() - 1;
            $latestSnapshot = $account->latestSnapshot;
            $balance = (float) ($latestSnapshot?->balance ?? 0);
            $typeBadge = match ($account->type) {
                'savings'    => 'bg-emerald-50 text-emerald-700',
                'investment' => 'bg-violet-50 text-violet-700',
                default      => 'bg-gray-100 text-gray-600',
            };
        @endphp

        <div class="px-4 py-4 {{ $isLast ? '' : 'border-b border-gray-50' }} {{ ! $account->is_active ? 'opacity-60' : '' }}">

            @if ($editingId === $account->id)
                {{-- Edit mode --}}
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Name</label>
                            <input
                                type="text"
                                wire:model="editName"
                                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                                autofocus
                            >
                            @error('editName')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Type</label>
                            <select
                                wire:model="editType"
                                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-gray-900 bg-white focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                            >
                                <option value="savings">Savings</option>
                                <option value="investment">Investment</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 mb-1">Notes <span class="font-normal text-gray-400">(optional)</span></label>
                        <input
                            type="text"
                            wire:model="editNotes"
                            placeholder="Optional description…"
                            class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                        >
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <button
                            wire:click="saveEdit({{ $account->id }})"
                            class="px-4 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
                        >Save</button>
                        <button
                            wire:click="cancelEdit"
                            class="px-4 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors"
                        >Cancel</button>
                    </div>
                </div>

            @else
                {{-- View mode --}}
                <div class="flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900">{{ $account->name }}</span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $typeBadge }}">
                                {{ ucfirst($account->type) }}
                            </span>
                            @if (! $account->is_active)
                                <span class="text-xs font-medium text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">Inactive</span>
                            @endif
                        </div>
                        @if ($account->notes)
                            <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $account->notes }}</p>
                        @endif
                        @if ($latestSnapshot?->recorded_at)
                            <p class="text-xs text-gray-400 mt-0.5">Updated {{ $latestSnapshot->recorded_at->diffForHumans() }}</p>
                        @else
                            <p class="text-xs text-gray-400 mt-0.5">No balance recorded</p>
                        @endif
                    </div>

                    <span class="text-base font-bold tabular-nums text-gray-900 shrink-0">
                        €&thinsp;{{ number_format($balance, 2, ',', '.') }}
                    </span>

                    {{-- Active toggle --}}
                    <button
                        wire:click="toggleActive({{ $account->id }})"
                        title="{{ $account->is_active ? 'Deactivate' : 'Activate' }}"
                        class="relative shrink-0"
                    >
                        <div class="w-8 h-4 {{ $account->is_active ? 'bg-blue-500' : 'bg-gray-200' }} rounded-full transition-colors"></div>
                        <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow-sm transition-transform {{ $account->is_active ? 'translate-x-4' : '' }}"></div>
                    </button>

                    {{-- Edit button --}}
                    <button
                        wire:click="startEdit({{ $account->id }})"
                        class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors shrink-0"
                        title="Edit"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                        </svg>
                    </button>

                    {{-- Delete / deactivate --}}
                    <div x-data="{ confirming: false }" class="flex items-center gap-1 shrink-0">
                        <button
                            x-show="!confirming"
                            x-on:click="confirming = true"
                            title="{{ $account->snapshots_count > 0 ? 'Deactivate' : 'Delete' }}"
                            class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                        <div x-show="confirming" x-cloak class="flex items-center gap-1">
                            <button
                                wire:click="removeAccount({{ $account->id }})"
                                x-on:click="confirming = false"
                                class="px-2 py-0.5 text-xs font-semibold text-white bg-red-500 hover:bg-red-600 rounded-md transition-colors"
                            >{{ $account->snapshots_count > 0 ? 'Deactivate' : 'Delete' }}</button>
                            <button
                                x-on:click="confirming = false"
                                class="px-2 py-0.5 text-xs font-medium text-gray-500 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                            >No</button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

    @empty
        <div class="px-5 py-8 text-center">
            <p class="text-sm text-gray-500">No accounts yet. Add one below.</p>
        </div>
    @endforelse

    {{-- Add account form --}}
    <div class="px-4 py-4 bg-gray-50 border-t border-gray-100">
        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Add Account</p>
        <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <input
                        type="text"
                        wire:model="newName"
                        placeholder="Account name"
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
                        <option value="savings">Savings</option>
                        <option value="investment">Investment</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <div class="flex">
                        <span class="flex items-center px-2.5 text-sm text-gray-500 bg-gray-50 border border-r-0 border-gray-200 rounded-l-lg select-none">€</span>
                        <input
                            type="number"
                            wire:model="newStartingBalance"
                            step="0.01"
                            placeholder="Starting balance (optional)"
                            class="flex-1 min-w-0 border border-gray-200 rounded-r-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                        >
                    </div>
                    @error('newStartingBalance')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <input
                        type="text"
                        wire:model="newNotes"
                        placeholder="Notes (optional)"
                        class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    >
                </div>
            </div>
            <div>
                <button
                    wire:click="addAccount"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-70 cursor-not-allowed"
                    wire:target="addAccount"
                    class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shadow-sm"
                >Add Account</button>
            </div>
        </div>
    </div>

</div>
