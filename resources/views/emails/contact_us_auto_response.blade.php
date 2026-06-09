<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Thank You - MindUnite</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .header {
            background-color: #043940;
            padding: 40px 20px;
            text-align: center;
            color: #ffffff;
        }

        .header h1 {
            margin: 0;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .header p {
            margin: 8px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 35px;
            color: #444444;
            line-height: 1.8;
        }

        .content h2 {
            color: #008b8b;
            font-size: 24px;
            margin-top: 0;
        }

        .content p {
            font-size: 16px;
            margin-bottom: 20px;
        }

        .status-card {
            background-color: #f0fdfa;
            border: 1px solid #ccfbf1;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
            text-align: left;
        }

        .status-item {
            margin-bottom: 10px;
            font-size: 15px;
        }

        .status-label {
            font-weight: bold;
            color: #008b8b;
            /* min-width: 120px; */
            display: inline-block;
        }

        .highlight-text {
            color: #00c2cb;
            font-weight: bold;
        }

        .email-link {
            color: #00c2cb !important;
            text-decoration: none;
            font-weight: bold;
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
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <a href="https://mindunite.com" target="_blank">
                <img src="{{ asset('assets/img/logo.svg') }}" alt="MindUnite Logo"
                    style="max-width: 300px; height: auto; margin-bottom: 0px;">
            </a>
        </div>
        {{-- <div class="header">
            <h1>MindUnite</h1>
            <p>Connecting the minds shaping Psychology and Neuroscience.</p>
        </div> --}}

        <!-- Content -->
        <div class="content">
            <h2>Hello {{ $contactData->name }},</h2>
            <p>Thank you for reaching out to us. We have successfully received your inquiry and our team is already
                reviewing it.</p>

            <div class="status-card">
                <div class="status-item">
                    <span class="status-label">Subject:</span>
                    <span>{{ $contactData->subject ?? 'General Inquiry' }}</span>
                </div>
                <div class="status-item">
                    <span class="status-label">Response Time:</span>
                    <span class="highlight-text">Within 48 Hours</span>
                </div>
            </div>

            <p>We truly value your interest in the <strong>MindUnite</strong> community. One of our specialists will get
                back to you shortly with a detailed response.</p>

            <p>In the meantime, feel free to explore our platform or reply directly to this email at <a
                    href="mailto:contact@mindunite.com" class="email-link">contact@mindunite.com</a> if you have
                anything urgent to add.</p>

            <p style="margin-top: 30px;">Best Regards,<br>
                <strong>The MindUnite Team</strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            &copy; {{ date('Y') }} <strong>MindUnite</strong>. All rights reserved.<br>
            Bridging Psychology and Neuroscience.
        </div>
    </div>
</body>

</html>
