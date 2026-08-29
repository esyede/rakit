@props(['type' => 'info'])
<div class="alert alert-{{ $type }}" {{ $attributes }}>{{ $slot }}</div>