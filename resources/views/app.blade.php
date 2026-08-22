<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <script>
            (function() {
                try {
                    var path = window.location.pathname;
                    var isAuthOrLanding = path === '/' || path.startsWith('/admin/login') || path.startsWith('/psb/login') || path.startsWith('/psb/register') || path.startsWith('/psb/forgot-password');

                    if (isAuthOrLanding) {
                        document.documentElement.classList.remove('dark');
                    } else {
                        var theme = localStorage.getItem('theme');
                        var isDark = theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
                        if (isDark) {
                            document.documentElement.classList.add('dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                        }
                    }
                } catch (e) {}
            })();
        </script>

        <link rel="icon" href="/image/logos/logo-1.png" type="image/png">
        <link rel="apple-touch-icon" href="/image/logos/logo-1.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>Darullughah Wadda'wah Perwakilan KalBar</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
