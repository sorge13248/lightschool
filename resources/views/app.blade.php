<!DOCTYPE html>
<html>
<head>
    @inertiaHead

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('img/logo.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/logo.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('img/logo.png') }}">
    <meta name="msapplication-TileImage" content="{{ asset('img/logo.png') }}">

    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/lightschool-base.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/fra-notifications.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/placeholder-loading.min.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/lightschool-my.css') }}"/>
    <link rel="stylesheet" href="{{ asset('css/theme/dark.css') }}"/>

    @include('layouts.partials.accent')

    <link rel="stylesheet" href="{{ asset('css/fra-context-menu.css') }}"/>
    @vite(['resources/svelte/entries/app.ts'])

    <script src="{{ url('/lang/' . app()->getLocale() . '.js') }}"></script>

    <script type="text/javascript">
        function apiFetch(url, method, body) {
            const opts = {
                method: (method || 'GET').toUpperCase(),
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            };
            if (body) {
                opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
                opts.body = body;
            }
            return fetch(url, opts).then(function(r) {
                if (r.status === 429) {
                    const msg = (typeof LANGUAGE !== 'undefined' && LANGUAGE['too-many-requests'])
                        ? LANGUAGE['too-many-requests']
                        : 'Too many requests. Please wait a moment and try again.';
                    return { response: 'error', text: msg };
                }
                return r.json();
            });
        }
    </script>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1e6ad3">
    <meta name="msapplication-navbutton-color" content="#1e6ad3">
</head>
<body>
@inertia
</body>
</html>
