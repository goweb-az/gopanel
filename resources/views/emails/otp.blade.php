{{--
    Birdəfəlik təsdiq kodu (MailService::sendOtpEmail → OtpMail).

    Data: otp, user_name (boş ola bilər), ttl_minutes, intro (məqsədə görə mətn).
--}}
@extends('emails.layouts.branded')

@section('mail-content')
    <div style="font-size:20px; font-weight:bold; color:#2c3e50; margin:0 0 8px 0;">
        Salam{{ !empty($data['user_name']) ? ', ' . $data['user_name'] : '' }}
    </div>
    <div style="margin:0 0 20px 0;">
        {{ $data['intro'] ?? 'Təsdiq kodunuz aşağıdadır.' }} Bu kodu heç kimlə paylaşmayın.
    </div>
    <div style="text-align:center; margin:0 0 16px 0;">
        <span style="display:inline-block; background:#f5f8fb; border:1px solid #d6e4f0; border-radius:8px; padding:14px 28px; font-size:30px; font-weight:bold; letter-spacing:6px; color:#556ee6;">
            {{ $data['otp'] ?? '' }}
        </span>
    </div>
    <div style="font-size:13px; color:#7b8794;">
        Kod {{ $data['ttl_minutes'] ?? 5 }} dəqiqə ərzində etibarlıdır.
        Bu sorğunu siz etməmisinizsə, məktubu nəzərə almayın.
    </div>
@endsection
