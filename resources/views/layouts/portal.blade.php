<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon-inka-32.svg') }}?v={{ filemtime(public_path('favicon-inka-32.svg')) }}">
    <title>{{ $title ?? 'Portal Utama' }} | INKA Talent Management System</title>
    <x-layout.open-graph :title="$title ?? 'Portal Utama'" />
    @stack('meta')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-full bg-gray-50 text-gray-900">
    @yield('content')
    @stack('scripts')
</body>
</html>
