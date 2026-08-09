<?php

namespace App\Services\Mail\Templates;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Standart qəlibli məktub: başlıq + loqo + `content` mətni + altlıq.
 *
 * Yeni şablon yazarkən nümunə budur - `emails.<ad>` blade-i yaradılır və
 * konstruktor eyni imzanı saxlayır ki, `MailService` onu asanlıqla çağıra bilsin.
 */
class BasicMail extends Mailable
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
        return $this->view('emails.basic')
            ->with(['data' => $this->viewData])
            ->from($this->from_address, config('mail.from.name'))
            ->subject($this->subject);
    }
}
