<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 text-center">
        <div class="text-6xl mb-4">❌</div>
        <h1 class="text-2xl font-bold text-red-600 mb-2">Payment Failed</h1>
        <p class="text-gray-500 mb-6">Your payment could not be completed. Please try again.</p>

        <a href="{{ url('/') }}"
            class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition">
            Try again
        </a>
    </div>

</body>
</html>
