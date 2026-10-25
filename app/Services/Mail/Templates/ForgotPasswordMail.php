<?php

namespace App\Services\Mail\Templates;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Şifrə bərpası linki məktubu (`emails.forgot-password`).
 *
 * Linkin özünü (token, imzalı URL) çağıran yer qurur - şablon yalnız çap
 * edir. Bu sayədə eyni məktub həm sayt istifadəçisi, həm admin, həm də API
 * (mobil deep-link) bərpası üçün işləyir.
 *
 * Mənbə: qrgate `ForgotPasswordMail` - hardcode `noreply@...` və `env()` çıxarılıb.
 */
class ForgotPasswordMail extends Mailable
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
        return $this->view('emails.forgot-password')
            ->with(['data' => $this->viewData])
            ->from($this->from_address, config('mail.from.name'))
            ->subject($this->subject);
    }
}
