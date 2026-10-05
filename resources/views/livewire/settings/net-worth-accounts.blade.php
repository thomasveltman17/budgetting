<div class="rounded-xl border border-line bg-surface">
    <ul class="divide-y divide-line">
        @forelse ($netWorthAccounts as $account)
            @php
                $latestSnapshot = $account->latestSnapshot;
                $balance = (float) ($latestSnapshot?->balance ?? 0);
            @endphp

            <li class="px-4 py-3">
                @if ($editingId === $account->id)
                    <div class="space-y-3">
                        <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_10rem]">
                            <div>
                                <x-field-label for="edit-nw-name">Name</x-field-label>
                                <input id="edit-nw-name" type="text" wire:model="editName" class="field" autofocus>
                                <x-field-error name="editName" />
                            </div>
                            <div>
                                <x-field-label for="edit-nw-type">Type</x-field-label>
                                <select id="edit-nw-type" wire:model="editType" class="field">
                                    <option value="savings">Savings</option>
                                    <option value="investment">Investment</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <x-field-label for="edit-nw-notes" optional>Notes</x-field-label>
                            <input id="edit-nw-notes" type="text" wire:model="editNotes" class="field">
                        </div>
                        <div class="flex gap-2">
                            <button type="button" wire:click="saveEdit({{ $account->id }})" class="btn btn-primary btn-sm">Save</button>
                            <button type="button" wire:click="cancelEdit" class="btn btn-quiet btn-sm">Cancel</button>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3 {{ $account->is_active ? '' : 'text-ink-3' }}">
                        <x-provider-logo :name="$account->name" class="{{ $account->is_active ? '' : 'opacity-50 grayscale' }}" />
                        <div class="min-w-0 flex-1">
                            <p class="flex items-center gap-2">
                                <span class="truncate text-sm font-medium {{ $account->is_active ? 'text-ink' : '' }}">{{ $account->name }}</span>
                                @if (! $account->is_active)
                                    <span class="shrink-0 rounded border border-line-strong px-1.5 text-[0.6875rem] leading-[1.125rem]">Inactive</span>
                                @endif
                            </p>
                            <p class="mt-0.5 truncate text-xs text-ink-3">
                                {{ ucfirst($account->type) }}
                                <span class="px-0.5">·</span>
                                {{ $latestSnapshot?->recorded_at ? 'Updated '.$latestSnapshot->recorded_at->diffForHumans() : 'No balance recorded' }}
                                @if ($account->notes)
                                    <span class="px-0.5">·</span>{{ $account->notes }}
                                @endif
                            </p>
                        </div>

                        <x-money :amount="$balance" class="shrink-0 text-sm font-medium tabular-nums {{ $account->is_active ? 'text-ink' : '' }}" />

                        <button type="button" wire:click="toggleActive({{ $account->id }})"
                                role="switch" aria-checked="{{ $account->is_active ? 'true' : 'false' }}"
                                title="{{ $account->is_active ? 'Counts towards net worth' : 'Excluded from net worth' }}"
                                class="relative ml-1 h-5 w-9 shrink-0 rounded-full transition-colors {{ $account->is_active ? 'bg-accent' : 'bg-line-strong' }}">
                            <span class="absolute top-0.5 left-0.5 size-4 rounded-full bg-surface shadow-sm transition-transform {{ $account->is_active ? 'translate-x-4' : '' }}"></span>
                        </button>

                        <div class="flex shrink-0 items-center" x-data="{ confirming: false }">
                            <button type="button" x-show="!confirming" wire:click="startEdit({{ $account->id }})" title="Edit" class="icon-btn">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Z" /></svg>
                            </button>
                            <button type="button" x-show="!confirming" @click="confirming = true" title="{{ $account->snapshots_count > 0 ? 'Deactivate' : 'Delete' }}" class="icon-btn hover:text-over">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                            <div x-show="confirming" x-cloak class="flex items-center gap-1">
                                <button type="button" wire:click="removeAccount({{ $account->id }})" @click="confirming = false" class="btn btn-danger btn-sm">
                                    {{ $account->snapshots_count > 0 ? 'Deactivate' : 'Delete' }}
                                </button>
                                <button type="button" @click="confirming = false" class="btn btn-quiet btn-sm">Keep</button>
                            </div>
                        </div>
                    </div>
                @endif
            </li>
        @empty
            <li class="px-4 py-8 text-center text-sm text-ink-3">No accounts yet. Add your first one below.</li>
        @endforelse
    </ul>

    {{-- New account --}}
    <div class="rounded-b-xl border-t border-line bg-sunken/40 px-4 py-4">
        <p class="mb-2.5 text-xs text-ink-3">New account</p>
        <div class="grid gap-2 sm:grid-cols-2">
            <div>
                <input type="text" wire:model="newName" placeholder="Name" aria-label="New account name" class="field">
                <x-field-error name="newName" />
            </div>
            <select wire:model="newType" aria-label="New account type" class="field">
                <option value="savings">Savings</option>
                <option value="investment">Investment</option>
            </select>
            <div>
                <div class="relative">
                    <span class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-ink-3">€</span>
                    <input type="number" step="0.01" wire:model="newStartingBalance" placeholder="Starting balance (optional)" aria-label="Starting balance" class="field pl-7 tabular-nums">
                </div>
                <x-field-error name="newStartingBalance" />
            </div>
            <input type="text" wire:model="newNotes" placeholder="Notes (optional)" aria-label="Notes" class="field">
        </div>
        <button type="button" wire:click="addAccount" wire:loading.attr="disabled" wire:target="addAccount" class="btn btn-secondary mt-3">Add account</button>
    </div>
</div>
