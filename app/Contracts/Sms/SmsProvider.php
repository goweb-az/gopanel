<?php

declare(strict_types=1);

namespace App\Contracts\Sms;

/**
 * SMS provayderi (Lsim, Twilio, Atl...) - infrastruktur adapteri.
 *
 * NİYƏ `app/Contracts/` altındadır:
 * interfeys həm `App\Services\Sms\SmsService`-in, həm də törəmə layihələrdə
 * yazılan öz provayderlərinin müqaviləsidir. Onu implement edən sinif servis
 * qovluğuna baxmaq məcburiyyətində qalmasın deyə müqavilələr tək yerdə -
 * `app/Contracts/` altında saxlanılır.
 *
 * Provayder YALNIZ göndərməklə məşğuldur: jurnal, limit, mətn təmizləmə və
 * xəta siyasəti `SmsService`-dədir. Yeni provayder yazanda `send()` uğursuz
 * olarsa exception atmalıdır - `false` qaytarmaq gizli xəta yaradır.
 */
interface SmsProvider
{
    /**
     * @param  string  $phone    Beynəlxalq formatda nömrə (+994...)
     * @param  string  $message  Göndəriləcək mətn (artıq latına çevrilmiş)
     *
     * @throws \Throwable Göndəriş alınmadıqda
     */
    public function send($phone, $message);
}
