<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Message</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #4f46e5;">New Message from {{ $senderName }}</h2>
        
        <p>You have received a new message in <strong>KORT School Management System</strong>.</p>
        
        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0;"><strong>From:</strong> {{ $senderName }}</p>
            <p style="margin: 10px 0 0 0;"><strong>Message Preview:</strong></p>
            <p style="margin: 5px 0 0 0; color: #666;">{{ $messagePreview }}...</p>
        </div>

        <p>
            <a href="{{ $chatUrl }}" style="display: inline-block; background-color: #4f46e5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px;">
                View Message
            </a>
        </p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">
        
        <p style="font-size: 12px; color: #999;">
            This is an automated message from {{ config('app.name') }}. Please do not reply to this email.
        </p>
    </div>
</body>
</html>
