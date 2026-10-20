@props(['transaction', 'size' => 'avatar'])
@php
    $directory = app(\App\Services\ProviderDirectory::class);
    $merchant = $transaction->merchant_name;
    $merchantProvider = $directory->detect($merchant);
    $initial = mb_strtoupper(mb_substr(preg_replace('/[^\p{L}\p{N}]/u', '', $merchant), 0, 1)) ?: '·';
    $hue = crc32(mb_strtolower($merchant)) % 360;
    $boxClass = $size === 'sm' ? 'size-6 text-[0.6875rem]' : 'size-9 text-sm';
@endphp
<span {{ $attributes->class('relative inline-block shrink-0') }}>
    @if ($merchantProvider)
        <x-provider-logo :provider="$merchantProvider" :size="$size" />
    @else
        <span class="grid place-items-center rounded-full font-semibold {{ $boxClass }} bg-[hsl(var(--hue)_42%_91%)] text-[hsl(var(--hue)_38%_32%)] dark:bg-[hsl(var(--hue)_24%_22%)] dark:text-[hsl(var(--hue)_55%_78%)]"
              style="--hue: {{ $hue }}" aria-hidden="true">{{ $initial }}</span>
    @endif
    <x-provider-logo :provider="$transaction->account->name" :size="$size === 'sm' ? 'xs' : 'badge'" circle
                     class="absolute -right-1 -bottom-1 shadow-[0_0_0_2px_var(--c-surface)]" />
</span>
