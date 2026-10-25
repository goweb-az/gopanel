<?php

namespace App\Services\Mail\Templates;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Birdəfəlik təsdiq kodu məktubu (`emails.otp`).
 *
 * Konstruktor `BasicMail` ilə eyni imzadadır - `MailService` bütün
 * şablonları eyni cür qurur, brend datası (`config/custom/mail.php`) da
 * `$viewData`-nın içində gəlir.
 *
 * Kod mətnin içində ÇAP OLUNUR, amma heç vaxt jurnala yazılmır - `MailService`
 * jurnala yalnız mövzu/ünvan yazır.
 *
 * Mənbə: qrgate `OtpMail` - hardcode `noreply@...` və `env()` çağırışı çıxarılıb.
 */
class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;

    public $from_address;

    /** @var array<string, mixed> */
    public $viewData;

    /**
     * @param  array<string, mixed>  $viewData
     */
    public function __construct($subject, $viewData, $from_address = null)
    {
        $this->subject      = $subject;
        $this->viewData     = $viewData;
        $this->from_address = $from_address ?: config('mail.from.address');
    }

    public function build()
    {
        return $this->view('emails.otp')
            ->with(['data' => $this->viewData])
            ->from($this->from_address, config('mail.from.name'))
            ->subject($this->subject);
    }
}
