@props(['placeholder','type' => 'text','disabled' => false])

<input
    {{ $disabled ? 'disabled' : '' }} 
    {{ $attributes->merge([
        'type' => $type,
        'placeholder' =>  $placeholder ?? '',
        'class' => 'block w-full mt-1  appearance-none   text-sm rounded-lg p-2 border border-2 focus:ring-primary focus:border-primary focus:shadow-outline-primary focus:outline-none form-input',
    ]) }}
/>

