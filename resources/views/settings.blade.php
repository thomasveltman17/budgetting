@extends('layouts.app')

@section('title', 'Settings – Veltiq Budget')

@section('content')
    <div class="px-6 py-8 space-y-10 max-w-3xl">

        <section>
            <div class="mb-4">
                <h2 class="text-sm font-bold" style="color: var(--c-text-1);">Categories</h2>
                <p class="text-xs mt-0.5" style="color: var(--c-text-3);">Manage names, colors, ordering, and archiving.</p>
            </div>
            <livewire:settings.categories />
        </section>

        <section>
            <div class="mb-4">
                <h2 class="text-sm font-bold" style="color: var(--c-text-1);">Budget Targets</h2>
                <p class="text-xs mt-0.5" style="color: var(--c-text-3);">Set spending targets per category for the selected period.</p>
            </div>
            <livewire:settings.budget-targets />
        </section>

        <section>
            <div class="mb-4">
                <h2 class="text-sm font-bold" style="color: var(--c-text-1);">Savings & Investment Accounts</h2>
                <p class="text-xs mt-0.5" style="color: var(--c-text-3);">Track your savings and investment balances over time.</p>
            </div>
            <livewire:settings.net-worth-accounts />
        </section>

    </div>
@endsection
