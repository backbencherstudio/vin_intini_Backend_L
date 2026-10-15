<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Confirm Your Boost Campaign - MindUnite</title>
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
            background-color: #f9fbfb;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #888888;
            border-top: 1px solid #eeeeee;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>MindUnite</h1>
        </div>
        <div class="content">
            <h2>Confirm Your Boost Campaign</h2>
            <p>Please enter the following 4-digit verification code to confirm and activate your product advertisement boost campaign:</p>
            <div class="otp-box">
                {{ $otp }}
            </div>
            <p style="font-size: 14px; color: #777777;">This code is valid for 3 minutes. If you did not request this boost, please ignore this email or contact support.</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} MindUnite. All rights reserved.
        </div>
    </div>
</body>

</html>
