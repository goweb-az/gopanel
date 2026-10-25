{{--
    Paneldən göndərilən ixrac faylının məktubu - `App\Services\Mail\Templates\ExportMail`.

    Email klientləri xarici CSS və flexbox dəstəkləmir - table layout və
    inline stil işlədilir (blade-də stil qadağasının email İSTİSNASI).

    Düymə/keçid yoxdur, fayl qoşmadadır: alıcı çox vaxt panelə girişi
    olmayan adamdır.

    Dəyişənlər: $title, $body, $details, $note, $fileName, $brand, $accent
--}}
<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#2b2f38;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                   style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="padding:24px 28px;border-bottom:1px solid #eceef2;">
                        <span style="font-size:18px;font-weight:700;color:{{ $accent }};">{{ $brand }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px;">
                        <h1 style="margin:0 0 16px;font-size:20px;line-height:1.4;">{{ $title }}</h1>

                        <div style="font-size:15px;line-height:1.6;color:#4a5061;">
                            {!! nl2br(e($body)) !!}
                        </div>

                        @if (!empty($note))
                            <div style="margin:18px 0 0;padding:12px 14px;background:#f8fafc;border-left:3px solid {{ $accent }};font-size:14px;line-height:1.6;color:#4a5061;">
                                {!! nl2br(e($note)) !!}
                            </div>
                        @endif

                        {{-- Qoşmanın adı mətndə də yazılır: bəzi poçt proqramları
                             qoşmanı aşağıda, gözdən uzaq yerdə göstərir. --}}
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                               style="margin:20px 0 0;border:1px solid #eceef2;border-radius:8px;">
                            <tr>
                                <td style="padding:12px 14px;font-size:14px;color:#2b2f38;">
                                    Qoşma: <strong>{{ $fileName }}</strong>
                                </td>
                            </tr>
                        </table>

                        @if (!empty($details))
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                                   style="margin:14px 0 0;border:1px solid #eceef2;border-radius:8px;">
                                @foreach ($details as $label => $value)
                                    <tr>
                                        <td style="padding:9px 14px;font-size:13px;color:#8b909c;border-bottom:1px solid #f2f3f6;width:45%;">{{ $label }}</td>
                                        <td style="padding:9px 14px;font-size:13px;color:#2b2f38;border-bottom:1px solid #f2f3f6;">{{ $value }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 28px;background:#fafbfc;border-top:1px solid #eceef2;font-size:12px;color:#8b909c;">
                        {{ $brand }} · Bu məktub avtomatik göndərilib.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
