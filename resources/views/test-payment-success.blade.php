<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Result</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 text-center">
        @php
            $status = request('redirect_status', 'unknown');
        @endphp

        @if ($status === 'succeeded')
            <div class="text-6xl mb-4">✅</div>
            <h1 class="text-2xl font-bold text-green-600 mb-2">Payment Successful!</h1>
            <p class="text-gray-500 mb-6">Your subscription has been activated. Check your dashboard.</p>
        @else
            <div class="text-6xl mb-4">⚠️</div>
            <h1 class="text-2xl font-bold text-yellow-600 mb-2">Payment {{ ucfirst($status) }}</h1>
            <p class="text-gray-500 mb-6">Payment status: <code class="bg-gray-100 px-2 py-0.5 rounded">{{ $status }}</code></p>
        @endif

        <a href="{{ route('test.payment') }}"
            class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-6 rounded-lg transition">
            Try again
        </a>
    </div>

</body>
</html>
