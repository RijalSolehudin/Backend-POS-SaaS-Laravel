@props(['title'])

<!doctype html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex,nofollow">
    <meta name="color-scheme" content="light">
    <title>{{ $title }} · POS Platform</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{ $slot }}
</html>
