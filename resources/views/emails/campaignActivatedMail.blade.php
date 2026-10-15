<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Your Boost Campaign is Live - Mind Unite</title>
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
            color: #333333;
            text-align: left;
            line-height: 1.6;
        }

        .content h2 {
            margin-top: 0;
            color: #043940;
            font-size: 24px;
            margin-bottom: 16px;
        }

        .content p {
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 20px;
            color: #475569;
        }

        .summary-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-left: 4px solid #00c2cb;
            border-radius: 8px;
            padding: 20px;
            margin: 25px 0;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table td {
            padding: 8px 4px;
            font-size: 14px;
            vertical-align: top;
        }

        .summary-table .label {
            color: #64748b;
            font-weight: 500;
            width: 38%;
        }

        .summary-table .value {
            color: #0f172a;
            font-weight: 600;
            text-align: right;
        }

        .status-badge {
            display: inline-block;
            background-color: #ecfdf5;
            color: #059669;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .btn-container {
            margin: 30px 0 20px 0;
            text-align: center;
        }

        .btn-primary {
            background-color: #00c2cb;
            color: #ffffff !important;
            padding: 13px 32px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            display: inline-block;
            box-shadow: 0 4px 6px -1px rgba(4, 57, 64, 0.15);
        }

        .info-note {
            font-size: 13px;
            color: #64748b;
            background-color: #f0fdfa;
            border-radius: 6px;
            padding: 12px 16px;
            margin-top: 25px;
            border: 1px dashed #00c2cb;
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

            .summary-table .label,
            .summary-table .value {
                font-size: 13px;
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
            <h2>Your Boost Campaign is Now Live!</h2>
            <p>
                Hello <strong>{{ $campaign->user->first_name ?? $campaign->user->name ?? 'Valued Advertiser' }}</strong>,
            </p>
            <p>
                Great news! Your product advertisement campaign has started successfully. Your product has received top-tier priority placement and is now reaching active users across Mind Unite.
            </p>

            <!-- Campaign Summary Card -->
            <div class="summary-card">
                <table class="summary-table">
                    <tr>
                        <td class="label">Product:</td>
                        <td class="value">
                            {{ $campaign->boostable->product_name ?? $campaign->boostable->name ?? 'Product' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Package / Duration:</td>
                        <td class="value">
                            {{ $campaign->boostPackage?->name ?? ($campaign->duration_days . ' Days Boost') }} ({{ $campaign->duration_days }} Days)
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Amount Paid:</td>
                        <td class="value">
                            ${{ number_format((float) $campaign->amount_paid, 2) }} {{ strtoupper($campaign->currency ?? 'USD') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Payment Method:</td>
                        <td class="value">
                            {{ $campaign->payment_platform === 'revenuecat' ? 'App Store (In-App Purchase)' : 'Credit Card (Stripe)' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Status:</td>
                        <td class="value">
                            <span class="status-badge">Active</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Start Date:</td>
                        <td class="value">
                            {{ $campaign->starts_at?->format('M d, Y h:i A') ?? now()->format('M d, Y') }}
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Scheduled Expiry:</td>
                        <td class="value">
                            {{ $campaign->ends_at?->format('M d, Y h:i A') ?? 'N/A' }}
                        </td>
                    </tr>
                    <tr>
                        <td class="label">Placements:</td>
                        <td class="value">
                            {{ is_array($campaign->placements) ? implode(', ', array_map('ucwords', $campaign->placements)) : 'Marketplace, Newsfeed' }}
                        </td>
                    </tr>
                </table>
            </div>

            <!-- CTA Button -->
            <div class="btn-container">
                <a href="https://mindunite.com" class="btn-primary" target="_blank">View on Mind Unite</a>
            </div>

            <div class="info-note">
                <strong>Campaign Control:</strong> You can pause and resume your campaign anytime from your advertising dashboard. Please note that campaigns left paused for 30 consecutive days will automatically conclude under our pause policy.
            </div>

            <p style="margin-top: 30px; margin-bottom: 0;">
                Best regards,<br>
                <strong>The Mind Unite Team</strong>
            </p>
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
