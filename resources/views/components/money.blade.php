@props(['amount', 'signed' => false])
@php
    $amount = round((float) $amount, 2);
    $sign = match (true) {
        $amount < 0 => '−',
        $signed && $amount > 0 => '+',
        default => '',
    };
@endphp
<span {{ $attributes->class('whitespace-nowrap') }}>{{ $sign }}€&nbsp;{{ number_format(abs($amount), 2, ',', '.') }}</span>
