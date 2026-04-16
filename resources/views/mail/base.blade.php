<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style="padding:48px 16px;">
                <table cellpadding="0" cellspacing="0" style="width:100%;max-width:560px;border-radius:10px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.08);">
                    <tr>
                        <td align="center" style="background:#1E6BC9;padding:28px 40px;">
                            <span style="font-size:22px;font-weight:700;color:#fff;">{{ config('app.name') }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#fff;padding:36px 40px;">
                            <h1 style="margin:0 0 20px;color:#1a2b4a;font-size:20px;">{{ $heading }}</h1>
                            <div style="color:#445568;font-size:15px;line-height:1.7;">{!! $body !!}</div>
                            @if(!empty($ctaUrl) && !empty($ctaText))
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:32px 0 24px;">
                                    <tr>
                                        <td align="center">
                                            <a href="{{ $ctaUrl }}" style="display:inline-block;background-color:#1E6BC9;color:#fff;font-family:Arial,sans-serif;font-size:15px;font-weight:700;padding:14px 36px;border-radius:6px;text-decoration:none;">{{ $ctaText }}</a>
                                        </td>
                                    </tr>
                                </table>
                                <p style="font-size:12px;color:#8896a5;text-align:center;">{{ __('email-button-not-working') }} <a href="{{ $ctaUrl }}" style="color:#1E6BC9;word-break:break-all;">{{ $ctaUrl }}</a></p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#f8fafc;padding:20px 40px;text-align:center;">
                            <p style="margin:0;font-size:12px;color:#8896a5;">&copy; {{ config('app.name') }} &mdash; {{ __('email-automated') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
