@props(['name'])
<input name="{{ $name }}" {{ $attributes->merge(['class' => 'form-control']) }}>