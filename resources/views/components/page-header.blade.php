@props(['title'])

<header {{ $attributes->class('flex flex-wrap items-end justify-between gap-x-6 gap-y-4 px-5 pt-7 pb-6 sm:px-8 sm:pt-9') }}>
    <div class="min-w-0">
        <h1 class="text-[1.375rem] leading-7 font-semibold tracking-[-0.012em] text-ink">{{ $title }}</h1>
        @isset($meta)
            <p class="mt-1 text-sm text-ink-3">{{ $meta }}</p>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</header>
