<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Security Alert: New Login Detected</title>
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
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .header {
            background-color: #043940;
            padding: 40px 20px;
            text-align: center;
        }

        .content {
            padding: 40px 30px;
            color: #333333;
            text-align: center;
        }

        .content h2 {
            color: #e11d48;
            /* Red for Alert */
            font-size: 24px;
            margin: 0 0 10px 0;
            font-weight: 700;
        }

        .content p {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 30px;
        }

        /* --- Security Card Design --- */
        .security-card {
            background-color: #fcf8f8;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 30px 20px;
            margin: 0 auto 30px;
            max-width: 450px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            text-align: left;
        }

        .info-row {
            margin-bottom: 12px;
            font-size: 15px;
            color: #334155;
        }

        .info-label {
            font-weight: bold;
            color: #64748b;
            width: 100px;
            display: inline-block;
        }

        .btn-container {
            margin-top: 30px;
            text-align: center;
        }

        /* Green Button for Trust */
        .btn-trust {
            background-color: #10b981;
            color: #ffffff !important;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 15px;
            display: inline-block;
            margin-bottom: 10px;
        }

        /* Red Button for Block */
        .btn-block {
            background-color: #ef4444;
            color: #ffffff !important;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 15px;
            display: inline-block;
            margin-bottom: 10px;
        }

        .footer {
            background-color: #f9fafb;
            padding: 25px;
            text-align: center;
            font-size: 13px;
            color: #888888;
            border-top: 1px solid #eeeeee;
        }

        .footer strong {
            color: #00c2cb;
        }

        @media only screen and (max-width: 600px) {
            .responsive-logo {
                width: 300px !important;
            }

            .btn-trust,
            .btn-block {
                display: block;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <a href="https://mindunite.com" target="_blank" style="text-decoration: none;">
                <img src="{{ asset('assets/img/logo.png') }}" alt="Mind Unite Logo" class="responsive-logo"
                    style="width: 500px; max-width: 100%; height: auto; display: block; margin: 0 auto; border: 0;">
            </a>
        </div>

        <!-- Content -->
        <div class="content">
            <h2>New Login Detected</h2>
            <p>Hi <strong>{{ $activity->user->first_name }}</strong>, we noticed a login to your Mind Unite account from
                a device or location you don't usually use.</p>

            <!-- Modern Security Card -->
            <div class="security-card">
                <div class="info-row">
                    <span class="info-label">Device:</span> {{ $activity->device }}
                </div>
                <div class="info-row">
                    <span class="info-label">Browser:</span> {{ $activity->browser }}
                </div>
                <div class="info-row">
                    <span class="info-label">Location:</span> {{ $activity->location }}
                </div>
                <div class="info-row">
                    <span class="info-label">IP Address:</span> {{ $activity->ip_address }}
                </div>
                <div class="info-row">
                    <span class="info-label">Time:</span> {{ $activity->login_at->format('d M Y, h:i A') }}
                </div>

                <div class="btn-container">
                    <p style="color: #0f172a; font-weight: bold; margin-bottom: 20px;">Was this you?</p>

                    <a href="{{ $trustUrl }}" class="btn-trust">Yes, it was me</a>

                    <a href="{{ $blockUrl }}" class="btn-block" style="margin-left: 10px;">No, secure account</a>
                </div>
            </div>

            <p style="font-size: 14px; margin-top: 20px;">If this wasn't you, your password might be compromised. We
                highly recommend changing it immediately.</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            &copy; {{ date('Y') }} <a href="https://mindunite.com" target="_blank"
                style="text-decoration: none; color: #00c2cb; font-weight: bold;">Mind Unite</a>. All rights
            reserved.<br>
            Psychology and Neuroscience Community.
        </div>
    </div>
</body>

</html>
