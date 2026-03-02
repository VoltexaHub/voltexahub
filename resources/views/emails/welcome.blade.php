<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ $forumName }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #7c3aed, #6d28d9); padding: 32px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: -0.5px;">{{ $forumName }}</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 32px;">
                            <h2 style="margin: 0 0 16px; color: #111827; font-size: 22px;">Welcome, {{ $username }}!</h2>

                            <p style="margin: 0 0 16px; color: #4b5563; font-size: 15px; line-height: 1.6;">
                                Thanks for joining the {{ $forumName }} community. Your account is all set up and ready to go.
                            </p>

                            <div style="background-color: #f5f3ff; border-left: 4px solid #7c3aed; padding: 16px 20px; border-radius: 0 6px 6px 0; margin: 24px 0;">
                                <p style="margin: 0; color: #6d28d9; font-size: 15px; font-weight: 600;">
                                    You earned 50 credits for joining!
                                </p>
                                <p style="margin: 4px 0 0; color: #7c3aed; font-size: 13px;">
                                    Spend them in the store on ranks, cosmetics, and more.
                                </p>
                            </div>

                            <p style="margin: 24px 0 24px; color: #4b5563; font-size: 15px; line-height: 1.6;">
                                Start exploring the forums, introduce yourself, and connect with other players.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 0 auto;">
                                <tr>
                                    <td style="background-color: #7c3aed; border-radius: 6px;">
                                        <a href="{{ $forumUrl }}" style="display: inline-block; padding: 14px 32px; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600;">
                                            Visit the Forums
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 24px 32px; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #9ca3af; font-size: 13px; text-align: center;">
                                &copy; {{ date('Y') }} {{ $forumName }}. You received this email because you registered an account.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
