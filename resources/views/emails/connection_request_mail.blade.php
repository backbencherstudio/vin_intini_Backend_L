<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>New Connection Request</title>
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

        /* Original Header */
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
            color: #008b8b;
            font-size: 24px;
            margin: 0 0 10px 0;
            font-weight: 700;
        }

        .content p {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 30px;
        }

        /* --- Profile Card Design --- */
        .profile-card {
            background-color: #fcf8f8;
            border: 1px solid #008b8b;
            border-radius: 16px;
            padding: 30px 20px;
            margin: 0 auto 30px;
            max-width: 400px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        /* Profile Image Style */
        .profile-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #00c2cb;
            margin-bottom: 15px;
        }

        /* Fallback Avatar Style (When Image is missing) */
        .avatar-fallback {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #00c2cb 0%, #008b8b 100%);
            color: #ffffff;
            border-radius: 50%;
            display: inline-block;
            line-height: 100px;
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 15px;
            text-transform: uppercase;
            text-align: center;
        }

        .sender-name {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
            margin: 0 0 4px 0;
        }

        .sender-title {
            font-size: 15px;
            color: #008b8b;
            font-weight: 600;
            margin: 0 0 15px 0;
            display: block;
        }

        .status-tag {
            display: inline-block;
            background-color: #f0fdfa;
            color: #0d9488;
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #ccfbf1;
        }

        .btn-container {
            margin-top: 25px;
        }

        .btn {
            background-color: #00c2cb;
            color: #ffffff !important;
            padding: 14px 35px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            display: inline-block;
        }

        /* Original Footer */
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
                max-width: 300px !important;
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
            <h2>New Connection Request</h2>
            <p>Hi <strong>{{ $receiver->first_name }} {{ $receiver->last_name }}</strong>, you have a new request to connect on Mind Unite.</p>

            <!-- Modern Profile Card -->
            <div class="profile-card">

                {{-- Profile Image logic with Fallback Avatar --}}
                @if($sender->profile_image_url)
                    <img src="{{ $sender->profile_image_url }}" class="profile-img" alt="{{ $sender->first_name }}">
                @else
                    <div class="avatar-fallback">
                        {{ substr($sender->first_name, 0, 1) }}{{ substr($sender->last_name, 0, 1) }}
                    </div>
                @endif

                <h3 class="sender-name">{{ $sender->first_name }} {{ $sender->last_name }}</h3>

                @if($sender->title)
                    <span class="sender-title">{{ $sender->title }}</span>
                @endif

                <div class="status-tag">Wants to Connect</div>

                <div class="btn-container">
                    <a href="https://mindunite.com/" class="btn">View & Respond</a>
                </div>
            </div>
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
