<?php

namespace App\Services\Mail;

use App\Services\Activity\LogService;
use App\Services\Mail\Templates\BasicMail;
use App\Services\Mail\Templates\HtmlMail;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Email göndərişinin TƏK giriş nöqtəsi.
 *
 * Nə üçün servis: Laravel-in `Mail::to()->send()` çağırışı hər yerdə təkrar
 * yazılsa, altlıq/loqo/queue/jurnal siyasəti də hər yerdə təkrarlanır. Burada
 * bir dəfə qurulur:
 *
 *  - şablonlara ötürülən ORTAQ data (loqo, altlıq, əlaqə, sayt ünvanı) -
 *    `config/custom/mail.php` → `branding`;
 *  - queue-ya yönləndirmə - `config/custom/mail.php` → `queue`;
 *  - hər göndərişin `logs/mail/` jurnalına düşməsi.
 *
 * MODELƏ BAĞLI DEYİL. Layihədə tənzimləmə cədvəli varsa (`mail_settings` və s.)
 * dəyərləri `addData()` ilə üstələ:
 *
 * ```php
 * (new MailService('Xoş gəldiniz'))
 *     ->addData(['logo_header' => $settings->logo_url])
 *     ->enableQueue()
 *     ->sendBasicEmail($user->email, 'Qeydiyyatınız tamamlandı.');
 * ```
 */
class MailService
{
    protected LogService $logging;

    protected ?string $subject;

    protected string $fromAddress;

    /** @var array<string, mixed> Şablonlara ötürülən data */
    protected array $data = [];

    /** @var array<int, string> */
    protected array $recipients = [];

    protected bool $queue = false;

    protected ?Mailable $content = null;

    public function __construct(?string $subject = null)
    {
        $this->logging = new LogService('mail');
        $this->subject = $subject;

        $this->initializeSettings();
    }

    /**
     * Ortaq brend datası - bütün şablonlar üçün eyni.
     */
    protected function initializeSettings(): void
    {
        $branding = (array) config('custom.mail.branding', []);

        $this->fromAddress = (string) ($branding['from_address'] ?? config('mail.from.address'));
        $this->subject     = $this->subject ?: (string) ($branding['title'] ?? config('app.name'));

        $this->data = [
            'subject'      => $this->subject,
            'title'        => $branding['title']        ?? config('app.name'),
            'footer_title' => $branding['footer_title'] ?? '',
            'description'  => $branding['description']  ?? '',
            'logo_header'  => $branding['logo_header']  ?? '',
            'logo_footer'  => $branding['logo_footer']  ?? '',
            'info_email'   => $branding['info_email']   ?? '',
            'phone'        => $branding['phone']        ?? '',
            'office'       => $branding['address']      ?? '',
            'site_url'     => url('/'),
            'today'        => now()->format('d.m.Y'),
            'unsubscribe'  => '',
            'content'      => '',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Qurulma (chain)                                                      */
    /* ------------------------------------------------------------------ */

    public function setRecipient(string $recipient): self
    {
        $this->recipients[] = $recipient;

        return $this;
    }

    public function setSubject(string $subject): self
    {
        $this->subject         = $subject;
        $this->data['subject'] = $subject;

        return $this;
    }

    public function setFrom(string $address): self
    {
        $this->fromAddress = $address;

        return $this;
    }

    /** Queue aktiv olanda mail `config/custom/mail.php` → `queue` ilə göndərilir. */
    public function enableQueue(bool $queue = true): self
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addData(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    public function setContent(Mailable $content): self
    {
        $this->content = $content;

        return $this;
    }

    /** Şablonun brauzerdə önizləməsi (route-dan qaytarmaq üçün). */
    public function preview(string $view = 'emails.basic')
    {
        return view($view, ['data' => $this->data]);
    }

    /* ------------------------------------------------------------------ */
    /* Göndəriş                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Tək alıcıya göndərir.
     *
     * Queue aktivdirsə mailable mərkəzi `connection`/`queue` ilə yönləndirilir -
     * bu sayədə bütün mail-lər (hansı metoddan gəlirsə-gəlsin) eyni növbəyə
     * düşür, driver-in default növbəsinə yox.
     *
     * @throws \InvalidArgumentException  Məzmun təyin edilməyibsə
     */
    public function send(string $recipient, ?Mailable $content = null): void
    {
        $content = $content ?? $this->content;

        if (!$content) {
            throw new \InvalidArgumentException('Mail məzmunu göndərişdən əvvəl təyin edilməlidir.');
        }

        try {
            if ($this->queue) {
                $connection = config('custom.mail.queue.connection');
                $queueName  = config('custom.mail.queue.name');

                if (!empty($connection)) {
                    $content->onConnection($connection);
                }

                if (!empty($queueName)) {
                    $content->onQueue($queueName);
                }

                Mail::to($recipient)->queue($content);
            } else {
                Mail::to($recipient)->send($content);
            }

            $this->logging->info("Mail göndərildi: {$recipient}", $this->logData());
        } catch (Throwable $e) {
            $this->logging->error("Mail göndərilmədi: {$recipient} - {$e->getMessage()}", $this->logData());

            throw $e;
        }
    }

    /**
     * Çox alıcıya göndərir. Bir alıcının xətası qalanları DAYANDIRMIR.
     *
     * @param  array<int, string>  $recipients
     * @return array{sent: int, failed: int}
     */
    public function sendBulk(array $recipients = [], ?Mailable $content = null): array
    {
        $recipients = array_values(array_unique(array_merge($this->recipients, $recipients)));

        $sent = 0;
        $failed = 0;

        foreach ($recipients as $recipient) {
            try {
                $this->send($recipient, $content);
                $sent++;
            } catch (Throwable) {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }

    /* ------------------------------------------------------------------ */
    /* Hazır şablonlar                                                      */
    /* ------------------------------------------------------------------ */

    /** Sadə mətn məzmunu standart qəlibin içində (`emails.basic`). */
    public function sendBasicEmail(string $recipient, string $content, ?string $subject = null): void
    {
        $this->addData(['content' => $content]);

        $this->content = new BasicMail($subject ?? $this->subject, $this->data, $this->fromAddress);

        $this->send($recipient);
    }

    /** Hazır HTML (məs. redaktordan gələn) - qəlib əlavə edilmir (`emails.html`). */
    public function sendHtmlEmail(string $recipient, string $html, ?string $subject = null): void
    {
        $this->addData(['html' => $html]);

        $this->content = new HtmlMail($subject ?? $this->subject, $this->data, $this->fromAddress);

        $this->send($recipient);
    }

    /**
     * Abunəlikdən çıxma linki əlavə edir. Route yoxdursa sükutla ötürülür -
     * bir marketinq detalı üstündən mail göndərişi dayanmamalıdır.
     */
    public function addUnsubscribeLink(string $recipient, string $routeName = 'email.unsubscribe'): self
    {
        if (app('router')->has($routeName)) {
            $this->data['unsubscribe'] = route($routeName, ['email' => $recipient]);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function logData(array $extra = []): array
    {
        return array_merge([
            'subject'     => $this->subject,
            'queue'       => $this->queue,
            'fromAddress' => $this->fromAddress,
        ], $extra);
    }
}
