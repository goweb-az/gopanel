{{--
    Brendli email karkası - başlıq (loqo) + @yield('mail-content') + altlıq.

    `emails.basic` ilə eyni görünüşdür; ayrıca layout ona görədir ki, yeni
    şablonlar (OTP, şifrə bərpası) başlıq/altlığı kopyalamasın, `basic`-in
    özü isə törəmə layihələrdə dəyişməz qalsın.

    Email klientləri xarici CSS və flexbox dəstəkləmir - table layout +
    inline stil (blade-də stil qadağasının İSTİSNASI).

    Data `MailService`-dən gəlir: title, logo_header, footer_title,
    description, office, phone, info_email, site_url, today, subject.
--}}
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $data['subject'] ?? ($data['title'] ?? config('app.name')) }}</title>
</head>
<body style="margin:0; padding:0; background:#f4f5f7; font-family:Arial, Helvetica, sans-serif; color:#2c3e50;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="max-width:600px; background:#ffffff; border-radius:8px; overflow:hidden;">

                <tr>
                    <td align="center" style="padding:28px 24px 12px 24px;">
                        @if (!empty($data['logo_header']))
                            <img src="{{ $data['logo_header'] }}" alt="{{ $data['title'] ?? '' }}"
                                 style="max-width:180px; height:auto; border:0;">
                        @else
                            <span style="font-size:20px; font-weight:bold; color:#2c3e50;">
                                {{ $data['title'] ?? config('app.name') }}
                            </span>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="padding:8px 32px 28px 32px; font-size:15px; line-height:1.7; color:#3d4852;">
                        @yield('mail-content')
                    </td>
                </tr>

                <tr>
                    <td style="background:#f7f8fa; padding:20px 32px; font-size:12px; line-height:1.6; color:#7b8794;">
                        @if (!empty($data['footer_title']))
                            <div style="font-weight:bold; color:#3d4852; margin-bottom:4px;">{{ $data['footer_title'] }}</div>
                        @endif
                        @if (!empty($data['description']))
                            <div style="margin-bottom:8px;">{{ $data['description'] }}</div>
                        @endif
                        @if (!empty($data['office']))
                            <div>{{ $data['office'] }}</div>
                        @endif
                        @if (!empty($data['phone']))
                            <div>Tel: {{ $data['phone'] }}</div>
                        @endif
                        @if (!empty($data['info_email']))
                            <div><a href="mailto:{{ $data['info_email'] }}" style="color:#7b8794;">{{ $data['info_email'] }}</a></div>
                        @endif
                        <div style="margin-top:12px;">
                            <a href="{{ $data['site_url'] ?? url('/') }}" style="color:#556ee6; text-decoration:none;">
                                {{ $data['site_url'] ?? url('/') }}
                            </a>
                        </div>
                    </td>
                </tr>
            </table>

            <div style="max-width:600px; padding:12px 8px; font-size:11px; color:#9aa5b1;">
                {{ $data['today'] ?? '' }} &middot; {{ $data['title'] ?? config('app.name') }}
            </div>
        </td>
    </tr>
</table>
</body>
</html>
