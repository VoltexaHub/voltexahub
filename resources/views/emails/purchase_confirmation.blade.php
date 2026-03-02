<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Confirmed</title>
</head>
@php $forumName = \App\Models\ForumConfig::get('forum_name', 'Community Forums'); @endphp
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f3f4f6; padding: 40px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #7c3aed, #6d28d9); padding: 32px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700; letter-spacing: -0.5px;">{{ $forumName }}</h1>
                            <p style="margin: 4px 0 0; color: #c4b5fd; font-size: 14px;">Store</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 32px;">
                            <h2 style="margin: 0 0 16px; color: #111827; font-size: 22px;">Purchase Confirmed</h2>

                            <p style="margin: 0 0 24px; color: #4b5563; font-size: 15px; line-height: 1.6;">
                                Your purchase has been processed successfully. Here are the details:
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb;">
                                <tr>
                                    <td style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb;">
                                        <span style="color: #6b7280; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Item</span><br>
                                        <span style="color: #111827; font-size: 16px; font-weight: 600;">{{ $itemName }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb;">
                                        <span style="color: #6b7280; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Payment Method</span><br>
                                        <span style="color: #111827; font-size: 15px;">{{ $paymentMethod }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb;">
                                        <span style="color: #6b7280; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Amount</span><br>
                                        <span style="color: #111827; font-size: 15px; font-weight: 600;">{{ $amount }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <span style="color: #6b7280; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;">Delivery Status</span><br>
                                        <span style="color: {{ $deliveryStatus === 'Delivered' ? '#059669' : '#d97706' }}; font-size: 15px; font-weight: 600;">{{ $deliveryStatus }}</span>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 24px 0 0; color: #6b7280; font-size: 14px; line-height: 1.6;">
                                If you have any issues with your purchase, please contact our support team on the forums.
                            </p>

                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin: 24px auto 0;">
                                <tr>
                                    <td style="background-color: #7c3aed; border-radius: 6px;">
                                        <a href="{{ $forumUrl }}" style="display: inline-block; padding: 14px 32px; color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600;">
                                            View Your Purchases
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
                                &copy; {{ date('Y') }} {{ $forumName }}. This is a purchase confirmation email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
