@props(['provider' => null, 'name' => null, 'size' => 'md', 'circle' => false])
@php
    $directory = app(\App\Services\ProviderDirectory::class);
    $key = $directory->has($provider) ? $provider : $directory->detect($name);
    $logo = $key ? $directory->logo($key) : null;

    $sizeClass = match ($size) {
        'xs' => 'size-4 rounded-[4px] text-[0.5625rem]',
        'badge' => 'size-[18px] rounded-[5px] text-[0.5625rem]',
        'sm' => 'size-6 rounded-md text-[0.6875rem]',
        'lg' => 'size-10 rounded-[11px] text-base',
        'avatar' => 'size-9 rounded-[10px] text-sm',
        default => 'size-8 rounded-[9px] text-[0.8125rem]',
    };
    $tileClass = match ($key) {
        'rabobank' => 'bg-white',
        'revolut' => 'bg-white',
        'amex' => 'bg-[#016fd0]',
        'trading212' => 'bg-black',
        default => 'bg-sunken text-ink-2',
    };
    $positionClass = str_contains((string) $attributes->get('class'), 'absolute') ? '' : 'relative';
    if ($circle) {
        $sizeClass = preg_replace('/rounded-\S+/', 'rounded-full', $sizeClass);
    }
    $initial = mb_strtoupper(mb_substr(preg_replace('/[^\p{L}\p{N}]/u', '', (string) $name), 0, 1)) ?: '·';
@endphp
<span {{ $attributes->class([$positionClass, 'inline-grid shrink-0 place-items-center overflow-hidden font-semibold after:pointer-events-none after:absolute after:inset-0 after:rounded-[inherit] after:ring-1 after:ring-black/[0.07] after:ring-inset dark:after:ring-white/10', $sizeClass, $tileClass]) }}>
    @if ($logo)
        <img src="{{ asset($logo) }}" alt="{{ $directory->label($key) }}" class="size-full object-contain {{ $key === 'rabobank' ? 'scale-[0.8]' : '' }}" decoding="async">
    @else
        <span aria-hidden="true">{{ $initial }}</span>
    @endif
</span>
