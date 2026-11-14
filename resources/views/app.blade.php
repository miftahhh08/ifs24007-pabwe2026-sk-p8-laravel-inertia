<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="BlogVerse Pro adalah platform blog tempat para penulis menerbitkan artikel seputar teknologi, pendidikan, bisnis, dan banyak lagi.">
    <title inertia>{{ config('app.name', 'BlogVerse Pro') }}</title>
    <meta name="theme-color" content="#4338ca">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
    @inertia
    <noscript>Aplikasi ini membutuhkan JavaScript untuk ditampilkan.</noscript>
</body>
</html>
