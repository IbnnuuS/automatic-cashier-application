<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100 antialiased selection:bg-indigo-500 selection:text-white">
    <div class="max-w-md mx-auto min-h-screen bg-white dark:bg-zinc-800 shadow-xl relative pb-20">
        {{ $slot }}
    </div>
    @fluxScripts
</body>
</html>
