<!DOCTYPE html>
<html lang="nl" class="h-full dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Veltiq Budget')</title>
    {{-- Prevent theme flash --}}
    <script>(function(){var t=localStorage.getItem('theme');if(t==='light')document.documentElement.classList.remove('dark');else document.documentElement.classList.add('dark');})()</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex h-full overflow-hidden bg-canvas text-ink antialiased">

    @php
        $navItems = [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5h6v6.75h-6zM14.25 4.5h6v3.75h-6zM14.25 12h6v7.5h-6zM3.75 15h6v4.5h-6z" />'],
            ['route' => 'transactions', 'label' => 'Transactions', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h10.5" />'],
            ['route' => 'history', 'label' => 'History', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 1.5M20.25 12a8.25 8.25 0 1 1-16.5 0 8.25 8.25 0 0 1 16.5 0Z" />'],
            ['route' => 'settings', 'label' => 'Settings', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" />'],
        ];
    @endphp

    {{-- ── Sidebar ─────────────────────────────────────────────────────────── --}}
    <aside class="flex h-screen w-14 shrink-0 flex-col border-r border-rail-line bg-rail text-rail-ink-2 lg:w-60">

        {{-- Wordmark --}}
        <div class="flex h-16 items-center justify-center gap-2.5 px-5 lg:justify-start">
            <span class="grid size-6 shrink-0 place-items-center rounded-[5px] bg-rail-ink text-[0.8125rem] font-semibold text-rail" aria-hidden="true">V</span>
            <span class="hidden text-[0.9375rem] tracking-[-0.01em] lg:inline">
                <span class="font-semibold text-rail-ink">Veltiq</span>
                <span class="text-rail-ink-2">Budget</span>
            </span>
        </div>

        {{-- Period switcher --}}
        <div class="hidden border-y border-rail-line lg:block">
            <livewire:period-switcher />
        </div>

        <div class="relative border-y border-rail-line lg:hidden" x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">
            <button type="button" @click="open = !open" title="Switch period" class="flex h-12 w-full items-center justify-center hover:bg-white/5 hover:text-rail-ink" :class="open && 'bg-white/5 text-rail-ink'">
                <svg class="size-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5M5.25 5.25h13.5a1.5 1.5 0 0 1 1.5 1.5v12a1.5 1.5 0 0 1-1.5 1.5H5.25a1.5 1.5 0 0 1-1.5-1.5v-12a1.5 1.5 0 0 1 1.5-1.5Z" /></svg>
            </button>
            <div x-show="open" x-cloak x-transition.opacity.duration.100ms class="absolute top-0 left-full z-50 ml-2 w-64 overflow-hidden rounded-lg border border-rail-line bg-rail shadow-xl">
                <livewire:period-switcher key="period-switcher-compact" />
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 space-y-0.5 px-2 py-4 lg:px-3" aria-label="Main">
            @foreach ($navItems as $item)
                @php $isActive = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   title="{{ $item['label'] }}"
                   @if ($isActive) aria-current="page" @endif
                   class="flex h-9 items-center justify-center gap-3 rounded-md px-2.5 text-sm transition-colors lg:justify-start {{ $isActive ? 'bg-white/[0.08] font-medium text-rail-ink' : 'hover:bg-white/5 hover:text-rail-ink' }}"
                >
                    <svg class="size-[18px] shrink-0 {{ $isActive ? 'opacity-100' : 'opacity-80' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">{!! $item['icon'] !!}</svg>
                    <span class="hidden lg:inline">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        {{-- Theme toggle + sign out --}}
        <div class="space-y-0.5 border-t border-rail-line px-2 py-3 lg:px-3">
            <button
                type="button"
                onclick="var d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light')"
                title="Switch light or dark theme"
                class="flex h-9 w-full items-center justify-center gap-3 rounded-md px-2.5 text-sm transition-colors hover:bg-white/5 hover:text-rail-ink lg:justify-start"
            >
                <svg class="size-[18px] shrink-0 dark:hidden" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" /></svg>
                <svg class="hidden size-[18px] shrink-0 dark:block" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>
                <span class="hidden lg:inline">
                    <span class="dark:hidden">Use dark theme</span>
                    <span class="hidden dark:inline">Use light theme</span>
                </span>
            </button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Sign out" class="flex h-9 w-full items-center justify-center gap-3 rounded-md px-2.5 text-sm transition-colors hover:bg-white/5 hover:text-rail-ink lg:justify-start">
                    <svg class="size-[18px] shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                    <span class="hidden lg:inline">Sign out</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- ── Main content ────────────────────────────────────────────────────── --}}
    <main class="min-w-0 flex-1 overflow-y-auto">
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
        class="pointer-events-none fixed right-4 bottom-4 z-[200] flex w-[min(340px,calc(100vw-2rem))] flex-col gap-2"
        aria-live="polite"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="pointer-events-auto flex items-center gap-3 rounded-lg border border-rail-line bg-rail py-2.5 pr-2 pl-3.5 text-sm text-rail-ink shadow-lg"
            >
                <span class="size-1.5 shrink-0 rounded-full"
                      :class="{ 'bg-credit': toast.type === 'success', 'bg-over': toast.type === 'error', 'bg-accent': toast.type === 'info' }"></span>
                <span class="flex-1" x-text="toast.message"></span>
                <button type="button" @click="remove(toast.id)" class="grid size-6 place-items-center rounded text-rail-ink-2 hover:bg-white/10 hover:text-rail-ink" title="Dismiss">
                    <svg class="size-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
        </template>
    </div>

    @livewireScripts
</body>
</html>
