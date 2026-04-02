<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="bumblebee">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Oh Beehive') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="drawer lg:drawer-open min-h-screen">
            <input id="main-drawer" type="checkbox" class="drawer-toggle" />

            {{-- Main content area --}}
            <div class="drawer-content flex flex-col bg-base-200">

                {{-- Mobile top bar --}}
                <div class="navbar bg-base-100 border-b border-base-300 lg:hidden sticky top-0 z-10">
                    <label for="main-drawer" aria-label="open sidebar" class="btn btn-ghost btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </label>
                    <span class="font-bold ml-1">🐝 Oh Beehive</span>
                </div>

                {{-- Page content --}}
                <main class="flex-1 p-6 lg:p-8">
                    {{ $slot }}
                </main>
            </div>

            {{-- Sidebar --}}
            <div class="drawer-side z-20">
                <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
                <livewire:layout.navigation />
            </div>
        </div>
    </body>
</html>
