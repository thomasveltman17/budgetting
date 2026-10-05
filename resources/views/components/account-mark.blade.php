@props(['account', 'short' => false])
@php
    $label = $short && $account->name === 'amex' ? 'Amex' : $account->label;
@endphp
<span {{ $attributes->class('inline-flex items-center gap-2 whitespace-nowrap') }}>
    <x-provider-logo :provider="$account->name" size="xs" />
    <span>{{ $label }}</span>
</span>
