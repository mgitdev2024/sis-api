@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'mt-1 text-sm text-red-600 dark:text-red-400 space-y-3']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
