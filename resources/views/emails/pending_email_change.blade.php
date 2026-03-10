<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Email Change</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #7c3aed, #6d28d9); padding: 32px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: -0.5px;">{{ $forumName }}</h1>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 32px;">
                            <h2 style="margin: 0 0 16px; color: #111827; font-size: 22px;">Email Change Request</h2>
                            <p style="margin: 0 0 16px; color: #4b5563; font-size: 15px; line-height: 1.6;">
                                Hi {{ $username }}, a request was made to change your email address to <strong>{{ $newEmail }}</strong>.
                            </p>
                            <p style="margin: 0 0 24px; color: #4b5563; font-size: 15px; line-height: 1.6;">
                                Click the button below to confirm this change. This link expires in 24 hours.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="background-color: #7c3aed; border-radius: 6px;">
                                        <a href="{{ $confirmUrl }}" style="display: inline-block; padding: 14px 32px; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600;">
                                            Confirm Email Change
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 24px 0 0; color: #9ca3af; font-size: 13px; line-height: 1.6;">
                                If you did not request this change, you can safely ignore this email. Your email will remain unchanged.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f9fafb; padding: 24px 32px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #9ca3af; font-size: 13px; text-align: center;">
                                &copy; {{ date('Y') }} {{ $forumName }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
