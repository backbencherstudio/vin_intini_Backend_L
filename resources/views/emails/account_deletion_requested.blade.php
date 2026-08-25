<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deletion Request</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
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
            padding: 40px 35px;
            color: #334155;
            text-align: left;
            line-height: 1.6;
        }

        .content h2 {
            color: #e11d48;
            font-size: 22px;
            margin: 0 0 16px 0;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .content p {
            font-size: 15px;
            color: #475569;
            margin-bottom: 20px;
        }

        /* --- Info Accent Box --- */
        .info-box {
            background-color: #f8fafc;
            border-left: 4px solid #00c2cb;
            border-radius: 6px;
            padding: 14px 18px;
            margin: 25px 0;
            color: #1e293b;
            font-size: 14px;
            line-height: 1.5;
        }

        .btn-container {
            margin: 28px 0;
            text-align: left;
        }

        .btn-login {
            background-color: #00c2cb;
            color: #ffffff !important;
            padding: 13px 26px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            display: inline-block;
            box-shadow: 0 4px 6px -1px rgba(4, 57, 64, 0.15);
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

            .content {
                padding: 30px 20px;
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
            <h2>Account Deletion Request Confirmation</h2>

            <p>Hello <strong>{{ $user->first_name }} {{ $user->last_name }}</strong>,</p>

            <p>We have received a request to delete your <strong>Mind Unite</strong> account.</p>

            <!-- Info Box with Left Border Accent -->
            <div class="info-box">
                Your account has been deactivated and is scheduled for permanent deletion on
                <strong>{{ \Carbon\Carbon::parse($permanentDeleteAt)->format('F d, Y') }}</strong> (30-day grace
                period).
            </div>

            <p><strong>Want to change your mind?</strong><br>
                If you wish to cancel this request and restore your account, simply log back into your account anytime
                before the scheduled deletion date.
            </p>

            <!-- Primary Action CTA Button -->
            <div class="btn-container">
                <a href="https://mindunite.com/login" class="btn-login">Log In to Cancel Deletion</a>
            </div>

            <p style="font-size: 14px; color: #64748b;">If you did not make this request, please contact our support
                team immediately or try logging in to secure your account.</p>

            <p style="margin-top: 30px; margin-bottom: 0;">Best regards,<br>
                <strong>The Mind Unite Team</strong>
            </p>
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
