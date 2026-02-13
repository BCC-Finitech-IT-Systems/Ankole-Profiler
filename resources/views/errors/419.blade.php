<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Expired</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 h-screen w-screen flex items-center justify-center">
    <div class="text-center">
        <h1 class="text-6xl font-bold text-gray-800">419</h1>
        <h2 class="text-2xl font-semibold text-gray-600 mt-4">Page Expired</h2>
        <p class="text-gray-500 mt-2">Sorry, your session has expired. Please refresh and try again.</p>
        @if(auth()->check())
            <a href="{{ route('dashboard') }}" class="mt-6 inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75">Go to Dashboard</a>
        @else
            <a href="{{ url('/') }}" class="mt-6 inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-opacity-75">Go to Homepage</a>
        @endif
    </div>
</body>
</html>
