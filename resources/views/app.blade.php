<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title inertia>{{ config('app.name', 'KORT School') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,300..800;1,14..32,300..800&display=swap" rel="stylesheet" />
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
    @routes
</head>
<body class="h-full font-sans antialiased">
    @inertia
</body>
</html>
