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
            padding: 30px 25px;
            margin: 0 auto 30px;
            max-width: 450px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            text-align: left;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding-bottom: 12px;
            vertical-align: top;
            font-size: 15px;
            color: #334155;
        }

        .info-label {
            font-weight: bold;
            color: #64748b;
            width: 100px;
        }

        .info-value {
            font-weight: 500;
        }

        .tz-text {
            font-size: 13px;
            color: #94a3b8;
            display: block;
            margin-top: 2px;
        }

        .btn-container {
            margin-top: 5px;
            text-align: center;
            border-top: 1px solid;
            padding-top: 5px;
        }

        .btn-trust {
            background-color: #10b981;
            color: #ffffff !important;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
            margin: 5px;
        }

        .btn-block {
            background-color: #ef4444;
            color: #ffffff !important;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
            margin: 5px;
        }

        .footer {
            background-color: #f9fafb;
            padding: 25px;
            text-align: center;
            font-size: 13px;
            color: #888888;
            border-top: 1px solid #eeeeee;
        }

        @media only screen and (max-width: 600px) {
            .responsive-logo {
                width: 250px !important;
            }
            .security-card {
                padding: 20px 15px;
            }
            .btn-trust, .btn-block {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header" style="text-align: center; padding: 20px;">
            <a href="https://mindunite.com" target="_blank" style="text-decoration: none;">
                <img src="{{ asset('assets/img/logo.png') }}" alt="Mind Unite Logo" class="responsive-logo"
                    style="width: 500px; max-width: 100%; height: auto; display: block; margin: 0 auto; border: 0;">
            </a>
        </div>

        <!-- Content -->
        <div class="content">
            <h2>New Login Detected</h2>
            <p>Hi <strong>{{ $activity->user->first_name }}</strong>, we noticed a login to your account from a device or location you don't usually use.</p>

            <!-- Modern Security Card using Table for Perfect Alignment -->
            <div class="security-card">
                <table class="info-table" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td class="info-label">Device:</td>
                        <td class="info-value">{{ $activity->device }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Browser:</td>
                        <td class="info-value">{{ $activity->browser }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Location:</td>
                        <td class="info-value">{{ $activity->location }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">IP Address:</td>
                        <td class="info-value">{{ $activity->ip_address }}</td>
                    </tr>
                    <tr>
                        <td class="info-label">Time:</td>
                        <td class="info-value">
                            {{ $localTime }}
                            <span class="tz-text">({{ $userTimezone }})</span>
                        </td>
                    </tr>
                </table>

                <div class="btn-container">
                    <p style="color: #0f172a; font-weight: bold; margin-bottom: 15px; font-size: 15px;">Was this you?</p>
                    <a href="{{ $trustUrl }}" class="btn-trust">Yes, it was me</a>
                    <a href="{{ $blockUrl }}" class="btn-block">No, secure account</a>
                </div>
            </div>

            <p style="font-size: 14px; margin-top: 20px; color: #64748b;">If this wasn't you, your password might be compromised. We highly recommend changing it immediately.</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            &copy; {{ date('Y') }} <a href="https://mindunite.com" target="_blank"
                style="text-decoration: none; color: #00c2cb; font-weight: bold;">Mind Unite</a>. All rights reserved.<br>
            Psychology and Neuroscience Community.
        </div>
    </div>
</body>

</html>
