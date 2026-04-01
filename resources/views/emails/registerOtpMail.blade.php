<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Email Verification</title>
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
            background-color: #00c2cb;
            padding: 40px 20px;
            text-align: center;
            color: #ffffff;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .content {
            padding: 40px 30px;
            text-align: center;
            color: #333333;
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

        .expiry {
            color: #ff4d4f;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>MindUnite</h1>
            <p>Connecting the minds shaping Psychology and Neuroscience.</p>
        </div>

        <!-- Content -->
        <div class="content">
            <h2>Confirm your email</h2>
            <p>Thank you for joining MindUnite! Please use the following verification code to complete your
                registration.</p>

            <div class="otp-box">
                {{ $otp }}
            </div>

            <p class="expiry">This code will expire in 10 minutes.</p>

            <p>If you didn't request this, you can safely ignore this email.</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            &copy; {{ date('Y') }} MindUnite. All rights reserved.<br>
            Psychology and Neuroscience Community.
        </div>
    </div>
</body>

</html>
