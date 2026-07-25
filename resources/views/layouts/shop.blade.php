<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
        <flux:header container sticky class="border-b border-zinc-200 bg-white/80 backdrop-blur-sm dark:border-zinc-800 dark:bg-zinc-900/80">
            <x-app-logo href="{{ route('shop.index') }}" wire:navigate />

            <flux:spacer />

            @auth
                <flux:button :href="route('dashboard')" wire:navigate size="sm" variant="ghost">{{ __('Dashboard') }}</flux:button>
            @else
                <flux:button :href="route('login')" wire:navigate size="sm" variant="ghost">{{ __('Log in') }}</flux:button>
            @endauth
        </flux:header>

        <flux:main container>
            {{ $slot }}
        </flux:main>

        <footer class="col-span-full border-t border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <x-app-logo href="{{ route('shop.index') }}" wire:navigate />
                    <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
                        &copy; {{ now()->year }} {{ config('app.name') }}. {{ __('All rights reserved.') }}
                    </flux:text>
                </div>
            </div>
        </footer>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
