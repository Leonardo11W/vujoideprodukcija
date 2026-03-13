<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light"
    dir="{{ session()->has('dir') ? session()->get('dir') : 'ltr' }}"
    data-bs-theme-color={{ getCustomizationSetting('theme_color') }}>

<head>
    <script>
        (function() {

            const savedTheme = localStorage.getItem('data-bs-theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            if (savedTheme === 'dark') {
                document.documentElement.classList.add('dark-mode-preload');
            }
        })();
    </script>
    <style>
        .darkmode-logo {
            display: none;
        }

        html[data-bs-theme="dark"] .darkmode-logo {
            display: inline-block;
        }

        html[data-bs-theme="dark"] .light-logo {
            display: none;
        }
    </style>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="base-url" content="{{ url('/') }}">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Favicon - High Priority -->
    <link rel="shortcut icon" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/favicon.ico') }}" type="image/x-icon">
    <link rel="icon" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/favicon.ico') }}" type="image/x-icon">
    <link rel="icon" type="image/x-icon" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/favicon-16x16.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/favicon-96x96.png') }}">
    <link rel="apple-touch-icon" sizes="57x57" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/apple-icon-57x57.png') }}">
    <link rel="apple-touch-icon" sizes="60x60" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/apple-icon-60x60.png') }}">
    <link rel="apple-touch-icon" sizes="72x72" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/apple-icon-72x72.png') }}">
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/apple-icon-76x76.png') }}">
    <link rel="apple-touch-icon" sizes="114x114" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/apple-icon-114x114.png') }}">
    <link rel="apple-touch-icon" sizes="120x120" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/apple-icon-120x120.png') }}">
    <link rel="apple-touch-icon" sizes="144x144" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/apple-icon-144x144.png') }}">
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/apple-icon-152x152.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset(setting('favicon') ?: 'img/logo/favicon/apple-icon-180x180.png') }}">
    <link rel="manifest" href="{{ asset('img/logo/favicon/manifest.json') }}">
    
    <!-- Override any external favicon -->
    <script>
        // Force favicon override
        (function() {
            const faviconUrl = "{{ asset(setting('favicon') ?: 'img/logo/favicon/favicon.ico') }}";
            const link = document.createElement('link');
            link.type = 'image/x-icon';
            link.rel = 'shortcut icon';
            link.href = faviconUrl;
            document.getElementsByTagName('head')[0].appendChild(link);
        })();
    </script>

    <title>@yield('title', config('app.name', 'Laravel'))</title>

    <meta name="description" content="{{ $description ?? '' }}">
    <meta name="keywords" content="{{ $keywords ?? '' }}">
    <meta name="author" content="{{ $author ?? '' }}">
    <meta name="google" content="notranslate">


    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Kalam:wght@300;400;700&family=Lexend+Deca:wght@100..900&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="{{ asset('modules/frontend/style.css') }}">

    <link rel="stylesheet" href="{{ asset('iconly/css/style.css') }}">

    <!-- <link rel="stylesheet" href="{{ asset('iconly/css/style.css') }}"> -->
    <link rel="stylesheet" href="{{ asset('phosphor-icons/regular/style.css') }}">
    <link rel="stylesheet" href="{{ asset('phosphor-icons/fill/style.css') }}">

    <link rel="stylesheet" href="{{ asset('modules/frontend/style.css') }}">
    @include('frontend::components.partials.head.plugins')

</head>

<body class="">


    @yield('content')

    <script src="{{ asset('modules/frontend/script.js') }}"></script>
    @stack('scripts')


</body>

</html>
