<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>:-)</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet"/>

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="antialiased font-sans">
<div class="dark:bg-gray-950 text-gray-600 dark:text-white/50">

    <div
        class="relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">

        <div
            class="max-w-sm p-6 bg-white border border-gray-200 rounded-lg shadow-sm dark:bg-gray-800 dark:border-gray-500">
            <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Gegroet, aardlingen.</h5>
            <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">Ik kom in vrede.</p>
            @if (Route::has('login'))
                <livewire:welcome.navigation/>
            @endif
        </div>


    </div>
</div>
</body>
</html>
