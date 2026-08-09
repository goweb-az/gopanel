<?php

namespace App\Services\Sms;

/**
 * SMS provayderi (Lsim, Twilio, Atl...) - infrastruktur adapteri.
 *
 * Provayder YALNIZ göndərməklə məşğuldur: jurnal, limit, mətn təmizləmə və
 * xəta siyasəti `SmsService`-dədir. Yeni provayder yazanda `send()` uğursuz
 * olarsa exception atmalıdır - `false` qaytarmaq gizli xəta yaradır.
 */
interface ProviderInterface
{
    /**
     * @param  string  $phone    Beynəlxalq formatda nömrə (+994...)
     * @param  string  $message  Göndəriləcək mətn (artıq latına çevrilmiş)
     *
     * @throws \Throwable Göndəriş alınmadıqda
     */
    public function send($phone, $message);
}
