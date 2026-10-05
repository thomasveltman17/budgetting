@props(['optional' => false])

<label {{ $attributes->class('mb-1.5 block text-[0.8125rem] font-medium text-ink-2') }}>
    {{ $slot }}@if ($optional)<span class="font-normal text-ink-3"> · optional</span>@endif
</label>
