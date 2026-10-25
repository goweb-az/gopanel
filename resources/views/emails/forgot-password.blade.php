{{--
    Şifrə bərpası linki (MailService::sendForgotPasswordEmail → ForgotPasswordMail).

    Data: action_url (bərpa linki), user_name (boş ola bilər), expires_minutes (boş ola bilər).
--}}
@extends('emails.layouts.branded')

@section('mail-content')
    <div style="font-size:20px; font-weight:bold; color:#2c3e50; margin:0 0 8px 0;">
        Salam{{ !empty($data['user_name']) ? ', ' . $data['user_name'] : '' }}
    </div>
    <div style="margin:0 0 20px 0;">
        Hesabınızın şifrəsini yeniləmək üçün sorğu göndərilib. Yeni şifrə təyin etmək üçün aşağıdakı düyməyə klikləyin.
    </div>
    <div style="text-align:center; margin:0 0 20px 0;">
        <a href="{{ $data['action_url'] ?? ($data['site_url'] ?? url('/')) }}"
           style="display:inline-block; background:#556ee6; color:#ffffff; text-decoration:none; border-radius:6px; padding:12px 24px; font-size:15px; font-weight:bold;">
            Şifrəni yenilə
        </a>
    </div>
    @if (!empty($data['expires_minutes']))
        <div style="font-size:13px; color:#7b8794; margin:0 0 8px 0;">
            Link {{ $data['expires_minutes'] }} dəqiqə ərzində etibarlıdır.
        </div>
    @endif
    <div style="font-size:13px; color:#7b8794;">
        Düymə işləmirsə, bu ünvanı brauzerə köçürün:<br>
        <span style="word-break:break-all;">{{ $data['action_url'] ?? '' }}</span>
    </div>
    <div style="font-size:13px; color:#7b8794; margin-top:12px;">
        Bu sorğunu siz etməmisinizsə, məktubu nəzərə almayın - şifrəniz dəyişməyəcək.
    </div>
@endsection
