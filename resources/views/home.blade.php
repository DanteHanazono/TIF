<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => 'Timetable'])
    </head>
    <body class="min-h-screen bg-neutral-950 text-neutral-50 antialiased">
        <livewire:home />

        @fluxScripts
    </body>
</html>
