<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} — {{ config('app.name') }}</title>
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-white text-neutral-900 dark:bg-neutral-950 dark:text-neutral-100">
        <main class="mx-auto max-w-2xl px-6 py-16">
            <a href="{{ url('/') }}" class="text-sm text-neutral-500 hover:text-neutral-900 dark:hover:text-neutral-100">&larr; Back</a>
            <h1 class="mt-6 text-3xl font-semibold tracking-tight">{{ $title }}</h1>
            <p class="mt-2 text-sm text-neutral-500">Last updated: {{ now()->format('F Y') }}</p>

            <div class="prose prose-neutral dark:prose-invert mt-8 max-w-none">
                {{ $slot }}
            </div>
        </main>
    </body>
</html>
