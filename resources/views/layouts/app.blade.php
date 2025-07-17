<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'FactuSys') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Estilos personalizados -->
    <style>
        body {
            background-color: #f1f5f9;
            color: #1e293b;
            font-family: 'Figtree', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        aside {
            width: 250px;
            background-color: #ffffff;
            border-right: 1px solid #e2e8f0;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 50;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            font-weight: 500;
            font-size: 0.95rem;
            color: #1e293b;
            transition: background 0.3s, color 0.3s;
        }

        .sidebar-link:hover {
            background-color: #e5e7eb;
            color: #2563eb;
        }

        .sidebar-section {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            padding: 0.5rem 1rem;
            color: #6b7280;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

        .dark body {
            background-color: #0f172a;
            color: #e2e8f0;
        }

        .dark aside {
            background-color: #1e293b;
            border-color: #374151;
        }

        .dark .sidebar-link {
            color: #e2e8f0;
        }

        .dark .sidebar-link:hover {
            background-color: #334155;
        }

        .dark .sidebar-section {
            color: #94a3b8;
        }
    </style>
</head>

<body class="font-sans antialiased">
    <div class="min-h-screen flex">
        @include('layouts.navigation')

        <div class="main-content w-full bg-gray-100 dark:bg-gray-900">
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-xl font-semibold text-gray-800 dark:text-gray-100">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="py-4">
                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>
