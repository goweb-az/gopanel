<?php

namespace App\Services\Mail\Templates;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Hazır HTML məktub - məzmun olduğu kimi göndərilir, qəlib əlavə edilmir.
 *
 * İstifadə yeri: redaktordan (kampaniya/bildiriş şablonu) gələn tam HTML.
 * Kənardan gələn HTML olduğu üçün məzmun MÜTLƏQ etibarlı mənbədən olmalıdır.
 */
class HtmlMail extends Mailable
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
        return $this->view('emails.html')
            ->with(['data' => $this->viewData])
            ->from($this->from_address, config('mail.from.name'))
            ->subject($this->subject);
    }
}
