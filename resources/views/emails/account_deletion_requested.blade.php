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
            text-align: left;
            line-height: 1.6;
        }

        .content h2 {
            color: #e11d48;
            font-size: 22px;
            margin: 0 0 20px 0;
            font-weight: 700;
        }

        .content p {
            font-size: 15px;
            color: #475569;
            margin-bottom: 16px;
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
        <div class="header">
            <a href="https://mindunite.com" target="_blank" style="text-decoration: none;">
                <img src="{{ asset('assets/img/logo.png') }}" alt="Mind Unite Logo" class="responsive-logo"
                    style="width: 500px; max-width: 100%; height: auto; display: block; margin: 0 auto; border: 0;">
            </a>
        </div>

        <!-- Content -->
        <div class="content">
            <h2>Hello, {{ $user->first_name }} {{ $user->last_name }}</h2>

            <p>We have received a request to <strong>Delete</strong> your <strong>Mind Unite</strong> account.</p>

            <p>Your account has been deactivated and is scheduled for permanent deletion on
                <strong>{{ \Carbon\Carbon::parse($permanentDeleteAt)->format('F d, Y') }}</strong> (after 30 days).
            </p>

            <p><strong>Want to change your mind?</strong><br>
                If you wish to cancel this request and restore your account, simply
                <a href="https://mindunite.com/login" style="color: #00c2cb; font-weight: bold; text-decoration: underline;">log back into your account</a>
                anytime before the permanent deletion date.
            </p>


            <p>If you did not make this request, please contact our support team immediately or try logging in to secure
                your account.</p>

            <p style="margin-top: 25px; margin-bottom: 0;">Best regards,<br>
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
