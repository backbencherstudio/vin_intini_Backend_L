<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Center - Mind Unite</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .card {
            background: #ffffff;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 450px;
            width: 95%;
            box-sizing: border-box;
        }

        .icon {
            font-size: 60px;
            margin-bottom: 20px;
            line-height: 1;
        }

        .success-icon {
            color: #10b981;
        }

        .alert-icon {
            color: #ef4444;
        }

        .expired-icon {
            color: #64748b;
        }

        h1 {
            color: #043940;
            font-size: 24px;
            margin: 0 0 10px 0;
            font-weight: 700;
        }

        p {
            color: #64748b;
            line-height: 1.6;
            margin: 0 0 25px 0;
            font-size: 15px;
        }

        /* --- Error Alert Style --- */
        .error-box {
            background: #fee2e2;
            color: #b91c1c;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: left;
            font-size: 13px;
            border: 1px solid #fecaca;
        }

        .error-box ul {
            margin: 0;
            padding-left: 20px;
        }

        /* --- Password Form Styles --- */
        .form-group {
            text-align: left;
            margin-bottom: 15px;
        }

        .password-field {
            position: relative;
        }

        .password-field input {
            padding-right: 46px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            padding: 0;
            border: none;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            border-radius: 6px;
        }

        .toggle-password:hover {
            color: #00c2cb;
        }

        .toggle-password .icon-hide {
            display: none;
        }

        .toggle-password.is-visible .icon-show {
            display: none;
        }

        .toggle-password.is-visible .icon-hide {
            display: block;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
            transition: 0.3s;
        }

        input:focus {
            border-color: #00c2cb;
            box-shadow: 0 0 0 3px rgba(0, 194, 203, 0.1);
        }

        .btn {
            background-color: #00c2cb;
            color: white !important;
            padding: 14px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            display: block;
            transition: 0.3s;
            border: none;
            width: 100%;
            cursor: pointer;
            font-size: 16px;
            margin-bottom: 10px;
            box-sizing: border-box;
        }

        .btn:hover {
            background-color: #39838d;
        }

        .btn-danger {
            background-color: #ef4444;
        }

        .btn-danger:hover {
            background-color: #b91c1c;
        }

        .btn-secondary {
            background-color: #f1f5f9;
            color: #475569 !important;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background-color: #e2e8f0;
        }

        .header {
            background-color: #043940;
            padding: 40px 20px;
            text-align: center;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="card">

        @if ($type === 'expired')
            <div class="icon expired-icon">⌛</div>
            <h1>Link Expired</h1>
            <p>This security link has already been used or has expired for your safety.</p>
            <a href="https://mindunite.com/login" class="btn">Go to Login</a>
        @elseif ($type === 'trust')
            <div class="icon success-icon">✔</div>
            <h1>Device Trusted</h1>
            <p>Thank you! This device has been added to your trusted list. You can now use it safely.</p>
            <a href="https://mindunite.com" class="btn">Go to Mind Unite</a>
        @elseif ($type === 'block')
            <div class="header" style="text-align: center; padding: 10px;">
                <a href="https://mindunite.com" target="_blank" style="text-decoration: none;">
                    <img src="{{ asset('assets/img/logo.png') }}" alt="Mind Unite Logo" class="responsive-logo"
                        style="width: 500px; max-width: 100%; height: auto; display: block; margin: 0 auto; border: 0;">
                </a>
            </div>
            {{-- <div class="icon alert-icon">⚠</div> --}}
            <h1 style="margin-top: 20px;">Secure Your Account</h1>
            <p>The suspicious device has been blocked. Set a <strong>strong new password</strong> to restore access.</p>

            @if (isset($errors) && $errors->any())
                <div class="error-box">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ $postUrl }}" method="POST">
                @csrf
                <input type="hidden" name="user_id" value="{{ $user_id }}">

                <div class="form-group">
                    <div class="password-field">
                        <input type="password" name="password" placeholder="New Password" required>
                        <button type="button" class="toggle-password" aria-label="Show password">
                            <svg class="icon-show" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg class="icon-hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <div class="password-field">
                        <input type="password" name="password_confirmation" placeholder="Confirm New Password" required>
                        <button type="button" class="toggle-password" aria-label="Show password">
                            <svg class="icon-show" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg class="icon-hide" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn">Update & Secure Account</button>
            </form>
            <a href="https://mindunite.com" class="btn btn-secondary">Cancel</a>
        @elseif ($type === 'success_reset')
            <div class="icon success-icon">🔒</div>
            <h1>Account Secured</h1>
            <p>Your password has been updated successfully. All other sessions have been signed out.</p>
            <a href="https://mindunite.com/login" class="btn">Login to Your Account</a>
        @endif

    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(function(button) {
            button.addEventListener('click', function() {
                var input = button.closest('.password-field').querySelector('input');
                if (!input) return;
                var showing = input.type === 'text';
                input.type = showing ? 'password' : 'text';
                button.classList.toggle('is-visible', !showing);
                button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
            });
        });
    </script>
</body>

</html>
