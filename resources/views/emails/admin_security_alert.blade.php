<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #dc2626, #991b1b); padding: 32px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px; font-weight: 700; letter-spacing: -0.5px;">Security Alert</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 32px;">
                            <h2 style="margin: 0 0 16px; color: #111827; font-size: 20px;">{{ $subject }}</h2>

                            <p style="margin: 0 0 24px; color: #4b5563; font-size: 15px; line-height: 1.6;">
                                A security event was detected on your admin account.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #fef2f2; border-radius: 6px; margin: 0 0 24px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding: 6px 0; color: #6b7280; font-size: 14px; width: 100px; vertical-align: top;">Event</td>
                                                <td style="padding: 6px 0; color: #111827; font-size: 14px; font-weight: 600;">{{ $event }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 6px 0; color: #6b7280; font-size: 14px; vertical-align: top;">IP Address</td>
                                                <td style="padding: 6px 0; color: #111827; font-size: 14px; font-weight: 600;">{{ $ip }}</td>
                                            </tr>
                                            @if($location)
                                            <tr>
                                                <td style="padding: 6px 0; color: #6b7280; font-size: 14px; vertical-align: top;">Location</td>
                                                <td style="padding: 6px 0; color: #111827; font-size: 14px; font-weight: 600;">{{ $location }}</td>
                                            </tr>
                                            @endif
                                            <tr>
                                                <td style="padding: 6px 0; color: #6b7280; font-size: 14px; vertical-align: top;">Time</td>
                                                <td style="padding: 6px 0; color: #111827; font-size: 14px; font-weight: 600;">{{ $time }} UTC</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <div style="background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 16px 20px; border-radius: 0 6px 6px 0;">
                                <p style="margin: 0; color: #92400e; font-size: 14px; line-height: 1.5;">
                                    If you did not perform this action, please change your password immediately and contact support.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 24px 32px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #9ca3af; font-size: 13px; text-align: center;">
                                This is an automated admin security alert. Do not reply to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
