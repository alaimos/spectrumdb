<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'SPECTRUM DB') }}</title>
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet"/>
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxStyles
    </head>

    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable
                      class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark"/>

            <flux:brand href="#" logo="https://fluxui.dev/img/demo/logo.png" name="SPECTRUM" class="px-2 dark:hidden"/>
            <flux:brand href="#" logo="https://fluxui.dev/img/demo/dark-mode-logo.png" name="SPECTRUM"
                        class="px-2 hidden dark:flex"/>

            <flux:navlist variant="outline">
                <flux:navlist.item icon="home" href="/" wire:navigate>Home</flux:navlist.item>
                <flux:navlist.item icon="face-smile" href="/playground" wire:navigate>Playground</flux:navlist.item>

                {{--<flux:navlist.group expandable heading="Favorites" class="hidden lg:grid">
                    <flux:navlist.item href="#">Marketing site</flux:navlist.item>
                    <flux:navlist.item href="#">Android app</flux:navlist.item>
                    <flux:navlist.item href="#">Brand guidelines</flux:navlist.item>
                </flux:navlist.group>--}}
            </flux:navlist>

            <flux:spacer/>

            <flux:button x-data x-on:click="$flux.dark = ! $flux.dark">Toggle</flux:button>

            <flux:navlist variant="outline">
                <livewire:components.layout.notification-button />
            </flux:navlist>

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:profile avatar="{{ Avatar::create(auth()->user()->name)->toBase64() }}"
                              name="{{ auth()->user()->name }}"/>

                <flux:menu>
                    <flux:menu.item href="{{ route('profile.edit') }}" wire:navigate icon="building-storefront">
                        Profile
                    </flux:menu.item>
                    <flux:menu.separator/>
                    <livewire:components.layout.logout-button/>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left"/>

            <flux:spacer/>

            <flux:dropdown position="top" alignt="start">
                <flux:profile avatar="{{ Avatar::create(auth()->user()->name)->toBase64() }}"/>

                <flux:menu>
                    <flux:menu.item href="{{ route('profile.edit') }}" wire:navigate icon="building-storefront">
                        Profile
                    </flux:menu.item>
                    <flux:menu.separator/>
                    <livewire:components.layout.logout-button/>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        <flux:main>
            {{ $slot }}
        </flux:main>
        @persist('toast')
        <flux:toast/>
        @endpersist
        @fluxScripts()
    </body>

</html>
