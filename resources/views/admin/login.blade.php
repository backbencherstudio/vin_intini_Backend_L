<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | MindUnite Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f1f5f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            background: #fff;
        }

        .brand-header {
            background-color: #1e293b;
            padding: 30px;
            text-align: center;
            color: #fff;
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #0ea5e9;
        }

        .btn-primary {
            background-color: #0ea5e9;
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: #0284c7;
        }
    </style>
</head>

<body>

    <div class="login-card shadow">
        <div class="brand-header">
            <h3 class="fw-bold mb-0"><i class="fa-solid fa-brain me-2 text-info"></i>MindUnite</h3>
            <small class="opacity-50">Admin Control Panel</small>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('admin.login') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">EMAIL ADDRESS</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        placeholder="admin@gmail.com" required autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">PASSWORD</label>
                    <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 shadow-sm">
                    Sign In to Dashboard
                </button>
            </form>
        </div>
    </div>

</body>

</html>
