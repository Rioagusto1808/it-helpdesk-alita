@props(['priority'])
<span {{ $attributes->class(['badge', $priority->badgeClass()]) }}>{{ $priority->label() }}</span>
