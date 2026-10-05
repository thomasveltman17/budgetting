@extends('layouts.app')

@section('title', 'Settings – Veltiq Budget')

@section('content')
    <div class="mx-auto max-w-[1180px] pb-16">
        <x-page-header title="Settings" />

        <div class="divide-y divide-line px-5 sm:px-8">
            <section id="categories" class="grid scroll-mt-6 gap-5 pb-10 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-12">
                <div>
                    <h2 class="text-[0.9375rem] font-semibold text-ink">Categories</h2>
                    <p class="mt-1 text-sm text-ink-3">Rename, recolour and reorder. Archived categories no longer appear in pickers or the budget.</p>
                </div>
                <livewire:settings.categories />
            </section>

            <section id="budget-targets" class="grid scroll-mt-6 gap-5 py-10 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-12">
                <div>
                    <h2 class="text-[0.9375rem] font-semibold text-ink">Budget targets</h2>
                    <p class="mt-1 text-sm text-ink-3">Soft targets for the selected period. The dashboard shows spending against them; nothing is blocked.</p>
                </div>
                <livewire:settings.budget-targets />
            </section>

            <section id="net-worth" class="grid scroll-mt-6 gap-5 py-10 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-12">
                <div>
                    <h2 class="text-[0.9375rem] font-semibold text-ink">Savings and investments</h2>
                    <p class="mt-1 text-sm text-ink-3">Accounts that make up your net worth. Every balance update is kept as a snapshot.</p>
                </div>
                <livewire:settings.net-worth-accounts />
            </section>
        </div>
    </div>
@endsection
