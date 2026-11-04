@props(['name', 'id'])

{{-- Erro exibido abaixo do campo; o input deve apontar para este id com aria-describedby. --}}
@error($name)
    <p id="{{ $id }}" {{ $attributes->class(['mt-1 text-xs text-rose-300']) }}>{{ $message }}</p>
@enderror
