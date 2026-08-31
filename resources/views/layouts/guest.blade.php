<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Cavite Rental Platform')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <a href="{{ route('home') }}" class="text-2xl font-bold text-slate-900">Cavite<span class="text-blue-600">Rentals</span></a>
        </div>
        <div class="bg-white shadow-sm border border-slate-200 rounded-xl p-8">
            @yield('content')
        </div>
    </div>
</body>
</html>
