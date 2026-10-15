<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Confirm Your Boost Campaign - Mind Unite</title>
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

        .expiry {
            color: #ff4d4f;
            font-weight: bold;
            font-size: 14px;
        }

        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #999999;
            border-top: 1px solid #eeeeee;
        }

        .footer strong {
            color: #00c2cb;
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
                <img src="{{ asset('assets/img/logo.png') }}" alt="Mind Unite Logo" class="responsive-logo"
                    style="width: 500px; max-width: 100%; height: auto; display: block; margin: 0 auto; border: 0;">
            </a>
        </div>

        <!-- Content Section -->
        <div class="content">
            <h2>Confirm Your Boost Campaign</h2>
            <p>We received a request to boost your product on Mind Unite. Please use the following One-Time Password (OTP) to confirm and complete your payment.</p>

            <div class="otp-box">
                {{ $otp }}
            </div>

            <p class="expiry">This code will expire in 3 minutes.</p>

            <p style="font-size: 14px; color: #777777;">If you didn't request this boost, you can safely ignore this email.</p>
        </div>

        <!-- Footer Section -->
        <div class="footer">
            &copy; {{ date('Y') }} <a href="https://mindunite.com" target="_blank"
                style="text-decoration: none; color: #00c2cb; font-weight: bold;">Mind Unite</a>. All rights
            reserved.<br>
            Psychology and Neuroscience Community.
        </div>
    </div>
</body>

</html>
