<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Confirm Your Subscription - MindUnite</title>
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

        @media only screen and (max-width: 600px) {
            .responsive-logo {
                width: 300px !important;
                max-width: 300px !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header with Logo -->

        <div class="header" style="text-align: center; padding: 20px;">
            <a href="https://mindunite.com" target="_blank" style="text-decoration: none;">
                <img src="{{ asset('assets/img/logo.png') }}" alt="MindUnite Logo" class="responsive-logo"
                    style="width: 500px; max-width: 100%; height: auto; display: block; margin: 0 auto; border: 0;">
            </a>
        </div>

        <!-- Content Section -->
        <div class="content">
            <h2>Confirm Your Subscription</h2>
            <p>We received a request to start a subscription on your account. Use the following One-Time Password (OTP)
                to confirm your subscription and complete the payment.</p>

            <div class="otp-box">
                {{ $otp }}
            </div>

            <p class="expiry">This code will expire in 3 minutes.</p>

            <p>If you didn't request this subscription, you can safely ignore this email.</p>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            &copy; {{ date('Y') }} <a href="https://mindunite.com" target="_blank"
                style="text-decoration: none; color: #00c2cb; font-weight: bold;">MindUnite</a>. All rights
            reserved.<br>
            Psychology and Neuroscience Community.
        </div>
    </div>
</body>

</html>
