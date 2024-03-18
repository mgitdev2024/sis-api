@props(['name'])

<button
    {{ $attributes->merge([
        'type' => 'submit',
        'class' => '
            block w-full px-4 py-2 mt-4 py-4 text-md font-medium leading-5 text-center text-white transition-colors duration-150
            bg-cta border-transparent rounded-lg active:opacity-90 hover:opacity-90 focus:outline-none',
    ]) }}>
    {{ $name ?? $slot }}
</button>