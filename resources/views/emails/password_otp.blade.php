<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reset Your Password - MindUnite</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .header {
            background-color: #043940;
            padding: 40px 20px;
            text-align: center;
            color: #ffffff;
        }

        .content {
            padding: 40px 30px;
            text-align: center;
            color: #333333;
        }

        .content h2 {
            margin-top: 0;
            color: #043940;
            font-size: 24px;
        }

        .content p {
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .otp-box {
            display: inline-block;
            background-color: #f0fdfa;
            border: 2px dashed #00c2cb;
            padding: 15px 40px;
            font-size: 36px;
            font-weight: bold;
            color: #008b8b;
            letter-spacing: 10px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #999999;
        }

        .footer strong {
            color: #00c2cb;
        }

        .expiry {
            color: #ff4d4f;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header with Logo -->
        <div class="header">
            <a href="https://mindunite.com" target="_blank">
                <img src="{{ asset('assets/img/logo.svg') }}" alt="MindUnite Logo"
                    style="max-width: 300px; height: auto; margin-bottom: 0px;">
            </a>
        </div>

        <!-- Content Section -->
        <div class="content">
            <h2>Password Reset OTP</h2>
            <p>We received a request to reset your password. Use the following One-Time Password (OTP) to proceed with
                your password reset.</p>

            <div class="otp-box">
                {{ $otp }}
            </div>

            <p class="expiry">This code will expire in 3 minutes.</p>

            <p>If you didn't request a password reset, you can safely ignore this email.</p>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            &copy; {{ date('Y') }} <a href="https://mindunite.com" target="_blank"
                style="text-decoration: none; color: #00c2cb; font-weight: bold;">MindUnite</a>. All rights
            reserved.<br>
            Psychology and Neuroscience Community.
        </div>
        {{-- <div class="footer">
            &copy; {{ date('Y') }} <strong>MindUnite</strong>. All rights reserved.<br>
            Psychology and Neuroscience Community.
        </div> --}}
    </div>
</body>

</html>












{{-- <!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset OTP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family: Arial, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:20px 10px;">
    <tr>
        <td align="center">
            <table cellpadding="0" cellspacing="0"
                   style="background:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.05); max-width:420px; width:100%;">

                <!-- Header -->
                <tr>
                    <td style="background:#00c2cb; padding:18px; text-align:center;">
                        <h1 style="color:#ffffff; margin:0; font-size:18px;">MindUnite</h1>
                        <p style="color:#e0e7ff; margin:4px 0 0; font-size:12px;">Password Reset Verification</p>
                    </td>
                </tr>

                <!-- Body -->
                <tr>
                    <td style="padding:20px;">
                        <p style="font-size:13px; color:#374151; margin:0 0 10px;">Hello,</p>

                        <p style="font-size:13px; color:#374151; margin:0 0 16px;">
                            Use the following One-Time Password (OTP) to reset your password:
                        </p>

                        <div style="text-align:center; margin:20px 0;">
                            <span style="
                                display:inline-block;
                                background:#f1f5f9;
                                padding:12px 24px;
                                font-size:22px;
                                letter-spacing:4px;
                                font-weight:bold;
                                color:#111827;
                                border-radius:6px;
                            ">
                                {{ $otp }}
                            </span>
                        </div>

                        <p style="font-size:12px; color:#6b7280; margin:0 0 10px;">
                            This OTP expires in <strong>3 minutes</strong>.
                        </p>

                        <p style="font-size:12px; color:#6b7280; margin:0;">
                            If you didn’t request this, please ignore this email.
                        </p>
                    </td>
                </tr>

                <!-- Footer -->
                <tr>
                    <td style="background:#f9fafb; padding:14px; text-align:center;">
                        <p style="font-size:11px; color:#4b4b4b; margin:0;">
                            © {{ date('Y') }} MindUnite
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html> --}}
