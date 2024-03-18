<!DOCTYPE html>
<html :class="{ 'dark': dark }" x-data="data()" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    @livewireStyles
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans ">
    <div class="flex h-screen bg-background dark:bg-gray-900" :class="{ 'overflow-hidden': isSideMenuOpen }">
        <x-sidebar  />
        <div class="flex flex-col flex-1 w-full">
            <x-header />
            <main class="h-full pb-16 overflow-y-auto">
            {{ $slot }}
            </main>
        </div>
    </div>
    @livewireScripts
    <script  src="{{ asset('js/init-alpine.js')}}" ></script>
    @vite(['resources/js/app.js' ])
</body>
</html>
