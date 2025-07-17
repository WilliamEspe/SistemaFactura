<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Estilo embebido: grises y azules UX -->
        <style>
            body {
                background-color: #f1f5f9;
                font-family: 'Figtree', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                color: #1e293b;
            }

            .auth-container {
                background-color: #ffffff;
                padding: 2rem;
                border-radius: 0.75rem;
                box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
                transition: all 0.3s ease;
            }

            .auth-container:hover {
                box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
            }

            .dark .auth-container {
                background-color: #1e293b;
                color: #e2e8f0;
                box-shadow: 0 6px 16px rgba(255, 255, 255, 0.06);
            }

            .text-blue {
                color: #3b82f6;
            }

            .bg-blue {
                background-color: #3b82f6;
            }

            .bg-blue:hover {
                background-color: #2563eb;
            }

            .btn {
                background-color: #3b82f6;
                color: white;
                padding: 0.5rem 1rem;
                border-radius: 0.375rem;
                transition: background-color 0.3s ease;
            }

            .btn:hover {
                background-color: #2563eb;
            }
        </style>
    </head>

    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-900">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 auth-container">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
