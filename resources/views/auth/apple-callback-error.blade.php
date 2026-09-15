<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Apple Sign In Error</title>
    <style>
        body { font-family: system-ui, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: #f5f5f5; }
        .container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; max-width: 400px; }
        .error { color: #dc3545; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Apple Sign In Failed</h2>
        <p class="error">{{ $message }}</p>
        <p>Please close this window and try again in the app.</p>
    </div>
</body>
</html>