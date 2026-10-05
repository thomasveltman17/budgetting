@props(['name'])

@error($name)
    <p {{ $attributes->class('mt-1.5 text-[0.8125rem] text-over') }}>{{ $message }}</p>
@enderror
