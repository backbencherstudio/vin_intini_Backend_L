<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>New Contact Message Received</title>
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

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .header p {
            margin: 5px 0 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 40px 30px;
            color: #333333;
        }

        .content h2 {
            text-align: center;
            color: #008b8b;
            font-size: 22px;
            margin-bottom: 25px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .info-table td {
            padding: 10px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 15px;
        }

        .label {
            font-weight: bold;
            color: #555555;
            width: 30%;
        }

        .value {
            color: #333333;
        }

        .message-box {
            background-color: #f0fdfa;
            border-left: 4px solid #00c2cb;
            padding: 20px;
            font-size: 16px;
            line-height: 1.6;
            color: #333333;
            margin-top: 10px;
            border-radius: 4px;

            text-align: justify;
            text-justify: inter-word;
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
            <h2>New Inquiry Received</h2>
            <p style="text-align: center;">Hello Admin, you have received a new message through the website contact form.
            </p>

            <table class="info-table">
                <tr>
                    <td class="label">Name:</td>
                    <td class="value">{{ $contactData->name }}</td>
                </tr>
                {{-- <tr>
                    <td class="label">Email:</td>
                    <td class="value">{{ $contactData->email }}</td>
                </tr> --}}
                <tr>
                    <td class="label">Email:</td>
                    <td class="value">
                        <a href="mailto:{{ $contactData->email }}"
                            style="color: #00c2cb; text-decoration: none; font-weight: bold;">
                            {{ $contactData->email }}
                        </a>
                    </td>
                </tr>
                <tr>
                    <td class="label">Phone:</td>
                    <td class="value">{{ $contactData->phone ?? 'Not provided' }}</td>
                </tr>
                <tr>
                    <td class="label">Address:</td>
                    <td class="value">{{ $contactData->address ?? 'Not provided' }}</td>
                </tr>
                <tr>
                    <td class="label">Subject:</td>
                    <td class="value">{{ $contactData->subject ?? 'No Subject' }}</td>
                </tr>
            </table>

            <p style="font-weight: bold; margin-bottom: 5px;">Message Details:</p>
            <div class="message-box">
                {{ $contactData->message ?? 'No message content.' }}
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            &copy; {{ date('Y') }} <strong>MindUnite</strong>. All rights reserved.<br>
            Psychology and Neuroscience Community.
        </div>
    </div>
</body>

</html>
