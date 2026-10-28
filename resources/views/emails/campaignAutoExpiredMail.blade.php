<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Boost Campaign Expired - Mind Unite</title>
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
            margin-bottom: 25px;
            color: #475569;
        }

        .alert-box {
            background-color: #fef2f2;
            border: 2px solid #fecaca;
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            color: #991b1b;
            font-size: 16px;
            font-weight: 600;
        }

        .btn-container {
            margin: 28px 0;
            text-align: center;
        }

        .btn-action {
            background-color: #00c2cb;
            color: #ffffff !important;
            padding: 13px 30px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            display: inline-block;
            box-shadow: 0 4px 6px -1px rgba(4, 57, 64, 0.15);
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

            .content {
                padding: 30px 20px;
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
            <h2>Boost Campaign Expired</h2>
            <div class="alert-box">
                Your campaign has reached the {{ $maxPauseDays ?? 30 }}-day maximum pause limit.
            </div>
            <p>
                The advertising boost campaign for <strong>{{ $campaign->boostable->product_name ?? $campaign->boostable->name ?? 'your product' }}</strong> remained paused for {{ $maxPauseDays ?? 30 }} consecutive days and has now automatically concluded.
            </p>
            <p>
                You can start a new boost campaign at any time from your product listing dashboard to regain top-tier visibility.
            </p>

            <div class="btn-container">
                <a href="https://mindunite.com" class="btn-action" target="_blank">Start a New Boost</a>
            </div>
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
