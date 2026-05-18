<div>
    <p class="text-xs mb-4" style="color: var(--c-text-3);">
        Period: <span class="font-semibold" style="color: var(--c-text-2);">{{ $period->start_date->format('j M') }} – {{ $period->end_date->format('j M Y') }}</span>
    </p>

    <div class="rounded-2xl overflow-hidden" style="background-color: var(--c-card); border: 1px solid var(--c-border);">

    @forelse ($netWorthAccounts as $index => $account)
        @php
            $isLast = $index === $netWorthAccounts->count() - 1;
            $latestSnapshot = $account->latestSnapshot;
            $balance = (float) ($latestSnapshot?->balance ?? 0);
            $typeBadgeStyle = match ($account->type) {
                'savings'    => 'background-color: var(--c-income-bg); color: var(--c-income);',
                'investment' => 'background-color: var(--c-revolut-bg); color: var(--c-revolut-text);',
                default      => 'background-color: var(--c-border); color: var(--c-text-2);',
            };
            $periodRecord = $account->netWorthAccountPeriods?->first();
            $isArchivedForPeriod = $periodRecord?->is_archived ?? false;
        @endphp

        <div class="px-4 py-4 {{ ! $account->is_active ? 'opacity-50' : '' }}"
             style="{{ $isLast ? '' : 'border-bottom: 1px solid var(--c-border-subtle);' }}">

            @if ($editingId === $account->id)
                {{-- Edit mode --}}
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--c-text-3);">Name</label>
                            <input
                                type="text"
                                wire:model="editName"
                                class="th-input w-full rounded-lg px-3 py-1.5 text-sm"
                                style="border-color: var(--c-brand);"
                                autofocus
                            >
                            @error('editName')
                                <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color: var(--c-text-3);">Type</label>
                            <select
                                wire:model="editType"
                                class="th-select w-full rounded-lg px-3 py-1.5 text-sm"
                            >
                                <option value="savings">Savings</option>
                                <option value="investment">Investment</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color: var(--c-text-3);">Notes <span class="font-normal" style="color: var(--c-text-3);">(optional)</span></label>
                        <input
                            type="text"
                            wire:model="editNotes"
                            placeholder="Optional description…"
                            class="th-input w-full rounded-lg px-3 py-1.5 text-sm"
                        >
                    </div>
                    <div class="flex items-center gap-2 pt-1">
                        <button
                            wire:click="saveEdit({{ $account->id }})"
                            class="px-4 py-1.5 text-xs font-semibold text-white rounded-lg transition-all"
                            style="background: linear-gradient(135deg, #7C6FF7, #5B4FD4);"
                        >Save</button>
                        <button
                            wire:click="cancelEdit"
                            class="th-btn-secondary px-4 py-1.5 text-xs font-medium rounded-lg transition-colors"
                        >Cancel</button>
                    </div>
                </div>

            @else
                {{-- View mode --}}
                <div class="flex items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold" style="color: var(--c-text-1);">{{ $account->name }}</span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full" style="{{ $typeBadgeStyle }}">
                                {{ ucfirst($account->type) }}
                            </span>
                            @if (! $account->is_active)
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full" style="background-color: var(--c-border); color: var(--c-text-3);">Globally Inactive</span>
                            @endif
                            @if ($isArchivedForPeriod)
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full" style="background-color: rgba(245,158,11,0.15); color: #F59E0B;">Archived this period</span>
                            @endif
                        </div>
                        @if ($account->notes)
                            <p class="text-xs mt-0.5 truncate" style="color: var(--c-text-3);">{{ $account->notes }}</p>
                        @endif
                        @if ($latestSnapshot?->recorded_at)
                            <p class="text-xs mt-0.5" style="color: var(--c-text-3);">Updated {{ $latestSnapshot->recorded_at->diffForHumans() }}</p>
                        @else
                            <p class="text-xs mt-0.5" style="color: var(--c-text-3);">No balance recorded</p>
                        @endif
                    </div>

                    <span class="text-base font-bold tabular-nums shrink-0" style="color: var(--c-text-1);">
                        €&thinsp;{{ number_format($balance, 2, ',', '.') }}
                    </span>

                    {{-- Active toggle --}}
                    <button
                        wire:click="toggleActive({{ $account->id }})"
                        title="{{ $account->is_active ? 'Deactivate' : 'Activate' }}"
                        class="relative shrink-0"
                    >
                        <div class="w-8 h-4 rounded-full transition-colors" style="{{ $account->is_active ? 'background-color: var(--c-brand);' : 'background-color: var(--c-deep);' }}"></div>
                        <div class="absolute top-0.5 left-0.5 w-3 h-3 bg-white rounded-full shadow-sm transition-transform {{ $account->is_active ? 'translate-x-4' : '' }}"></div>
                    </button>

                    {{-- Archive for this period --}}
                    <button
                        wire:click="toggleArchiveForPeriod({{ $account->id }})"
                        title="{{ $isArchivedForPeriod ? 'Unarchive for this period' : 'Archive for this period' }}"
                        class="p-1.5 rounded-lg transition-colors shrink-0"
                        style="{{ $isArchivedForPeriod ? 'color: #F59E0B; background-color: rgba(245,158,11,0.12);' : 'color: var(--c-text-3);' }}"
                        onmouseover="if (!{{ $isArchivedForPeriod ? 'true' : 'false' }}) { this.style.color='#F59E0B'; this.style.backgroundColor='rgba(245,158,11,0.12)'; }"
                        onmouseout="if (!{{ $isArchivedForPeriod ? 'true' : 'false' }}) { this.style.color='var(--c-text-3)'; this.style.backgroundColor=''; }"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.5v2.25m3-6v6M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125V4.875c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                        </svg>
                    </button>

                    {{-- Edit button --}}
                    <button
                        wire:click="startEdit({{ $account->id }})"
                        class="th-btn th-btn-brand p-1.5 rounded-lg shrink-0"
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
                            class="th-btn th-btn-danger p-1.5 rounded-lg"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                        <div x-show="confirming" x-cloak class="flex items-center gap-1">
                            <button
                                wire:click="removeAccount({{ $account->id }})"
                                x-on:click="confirming = false"
                                class="px-2 py-0.5 text-xs font-semibold text-white rounded-md transition-colors"
                                style="background-color: var(--c-expense);"
                                onmouseover="this.style.opacity='0.85'"
                                onmouseout="this.style.opacity='1'"
                            >{{ $account->snapshots_count > 0 ? 'Deactivate' : 'Delete' }}</button>
                            <button
                                x-on:click="confirming = false"
                                class="th-btn-secondary px-2 py-0.5 text-xs font-medium rounded-md transition-colors"
                            >No</button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

    @empty
        <div class="px-5 py-8 text-center">
            <p class="text-sm" style="color: var(--c-text-3);">No accounts yet. Add one below.</p>
        </div>
    @endforelse

    {{-- Add account form --}}
    <div class="px-4 py-4" style="border-top: 1px solid var(--c-border); background-color: var(--c-footer);">
        <p class="text-xs font-bold uppercase tracking-widest mb-3" style="color: var(--c-text-3);">Add Account</p>
        <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <input
                        type="text"
                        wire:model="newName"
                        placeholder="Account name"
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
                        <option value="savings">Savings</option>
                        <option value="investment">Investment</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <div class="flex">
                        <span class="flex items-center px-2.5 text-sm rounded-l-lg select-none" style="background-color: var(--c-deep); border: 1px solid var(--c-border-medium); border-right: none; color: var(--c-text-3);">€</span>
                        <input
                            type="number"
                            wire:model="newStartingBalance"
                            step="0.01"
                            placeholder="Starting balance (optional)"
                            class="th-input flex-1 min-w-0 rounded-r-lg rounded-l-none px-3 py-2 text-sm"
                            style="border-left: none;"
                        >
                    </div>
                    @error('newStartingBalance')
                        <p class="mt-1 text-xs" style="color: var(--c-expense);">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <input
                        type="text"
                        wire:model="newNotes"
                        placeholder="Notes (optional)"
                        class="th-input w-full rounded-lg px-3 py-2 text-sm"
                    >
                </div>
            </div>
            <div>
                <button
                    wire:click="addAccount"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-70 cursor-not-allowed"
                    wire:target="addAccount"
                    class="px-4 py-2 text-sm font-semibold text-white rounded-lg transition-all"
                    style="background: linear-gradient(135deg, #7C6FF7, #5B4FD4);"
                >Add Account</button>
            </div>
        </div>
    </div>

    </div>
</div>
