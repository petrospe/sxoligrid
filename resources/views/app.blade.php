<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>SxoliGrid</title>
        <link rel="icon" href="/branding/mark.png" type="image/png">
        <link rel="apple-touch-icon" href="/branding/mark.png">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="bg-[#F4F5F7] text-sm text-[#172B4D] antialiased">
        @inertia
    </body>
</html>
