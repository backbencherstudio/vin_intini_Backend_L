<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 text-center">
        <div class="text-6xl mb-4">✅</div>
        <h1 class="text-2xl font-bold text-green-600 mb-2">Payment Successful!</h1>
        <p class="text-gray-500 mb-2">Your subscription has been activated.</p>
        @if (request('session_id'))
            <p class="text-xs text-gray-400 mb-6">Session: <code class="bg-gray-100 px-1 rounded">{{ request('session_id') }}</code></p>
        @else
            <p class="text-xs text-gray-400 mb-6">You can now access all premium features.</p>
        @endif

        <a href="{{ route('payment.success') }}"
            class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition">
            Go to dashboard
        </a>
    </div>

</body>
</html>
