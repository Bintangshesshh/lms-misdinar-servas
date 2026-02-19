@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-misdinar-primary text-sm font-medium leading-5 text-misdinar-dark focus:outline-none focus:border-misdinar-dark transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-misdinar-primary hover:border-misdinar-200 focus:outline-none focus:text-misdinar-primary focus:border-misdinar-200 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
