@extends('layouts.app')

@section('title', 'Settings – Budgeting')

@section('content')
    <div class="px-6 py-6 space-y-10">

        <section>
            <div class="mb-3">
                <h2 class="text-sm font-bold text-gray-900">Categories</h2>
                <p class="text-xs text-gray-500 mt-0.5">Manage names, colors, ordering, and archiving.</p>
            </div>
            <livewire:settings.categories />
        </section>

        <section>
            <div class="mb-3">
                <h2 class="text-sm font-bold text-gray-900">Budget Targets</h2>
                <p class="text-xs text-gray-500 mt-0.5">Set spending targets per category for the selected period.</p>
            </div>
            <livewire:settings.budget-targets />
        </section>

        <section>
            <div class="mb-3">
                <h2 class="text-sm font-bold text-gray-900">Savings & Investment Accounts</h2>
                <p class="text-xs text-gray-500 mt-0.5">Track your savings and investment balances over time.</p>
            </div>
            <livewire:settings.net-worth-accounts />
        </section>

    </div>
@endsection
