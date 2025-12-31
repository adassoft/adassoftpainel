<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Instalador' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#06b6d4',
                    }
                }
            }
        }
    </script>
    @livewireStyles
</head>

<body class="h-full">
    <div class="min-h-full flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            {{-- Logo --}}
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Instalação do Sistema
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Configure seu ambiente em poucos passos
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-xl">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                {{ $slot }}
            </div>
        </div>
    </div>
    @livewireScripts
</body>

</html>