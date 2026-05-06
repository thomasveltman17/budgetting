<!DOCTYPE html>
<html lang="nl" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Veltiq Budget')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full flex overflow-hidden bg-gray-50 antialiased">

    {{-- ── Sidebar ─────────────────────────────────────────────────────────── --}}
    <aside class="flex flex-col w-14 lg:w-64 shrink-0 h-screen bg-slate-900 overflow-y-auto">

        {{-- App name --}}
        <div class="flex items-center gap-2.5 px-3 lg:px-5 py-5 border-b border-slate-700/50 justify-center lg:justify-start">
            <div class="w-7 h-7 rounded-lg bg-blue-600 flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33" />
                </svg>
            </div>
            <span class="hidden lg:block text-base font-bold text-white tracking-tight">Veltiq Budget</span>
        </div>

        {{-- Period switcher (desktop only) --}}
        <div class="hidden lg:block">
            <livewire:period-switcher />
        </div>

        {{-- Period icon (tablet only) --}}
        <div class="lg:hidden flex items-center justify-center py-3 border-b border-slate-700/50">
            <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" title="Period switcher available on larger screens">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
            </svg>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-2 lg:px-3 py-4 space-y-0.5">
            <a href="{{ route('dashboard') }}"
               title="Dashboard"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors justify-center lg:justify-start
                   {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
                <span class="hidden lg:inline">Dashboard</span>
            </a>

            <a href="{{ route('transactions') }}"
               title="Transactions"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors justify-center lg:justify-start
                   {{ request()->routeIs('transactions') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                <span class="hidden lg:inline">Transactions</span>
            </a>

            <a href="{{ route('history') }}"
               title="History"
               class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors justify-center lg:justify-start
                   {{ request()->routeIs('history') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                <span class="hidden lg:inline">History</span>
            </a>

            <div class="pt-4 mt-4 border-t border-slate-700/50">
                <a href="{{ route('settings') }}"
                   title="Settings"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors justify-center lg:justify-start
                       {{ request()->routeIs('settings') ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800 hover:text-slate-200' }}">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <span class="hidden lg:inline">Settings</span>
                </a>
            </div>
        </nav>

        {{-- Logout --}}
        <div class="px-2 lg:px-3 pb-4">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    title="Sign out"
                    class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition-colors justify-center lg:justify-start"
                >
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                    </svg>
                    <span class="hidden lg:inline">Sign out</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- ── Main content ────────────────────────────────────────────────────── --}}
    <main class="flex-1 overflow-y-auto">
        @hasSection('content')
            @yield('content')
        @else
            {{ $slot ?? '' }}
        @endif
    </main>

    {{-- ── Toast notifications ─────────────────────────────────────────────── --}}
    <div
        x-data="{
            toasts: [],
            add(type, message) {
                const id = Date.now();
                this.toasts.push({ id, type, message });
                setTimeout(() => this.remove(id), 4000);
            },
            remove(id) {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }
        }"
        @toast.window="add($event.detail.type, $event.detail.message)"
        class="fixed top-4 right-4 z-[200] flex flex-col gap-2 pointer-events-none"
        style="width: 320px"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-show="true"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-x-4"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-x-0"
                x-transition:leave-end="opacity-0 translate-x-4"
                class="pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl shadow-lg border"
                :class="{
                    'bg-white border-gray-200': toast.type === 'success' || toast.type === 'info',
                    'bg-red-50 border-red-200': toast.type === 'error'
                }"
            >
                <div class="shrink-0 mt-0.5">
                    <template x-if="toast.type === 'success'">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                    </template>
                </div>
                <span
                    class="text-sm font-medium"
                    :class="{
                        'text-gray-800': toast.type === 'success' || toast.type === 'info',
                        'text-red-800': toast.type === 'error'
                    }"
                    x-text="toast.message"
                ></span>
                <button
                    @click="remove(toast.id)"
                    class="ml-auto shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>

    @livewireScripts
</body>
</html>
