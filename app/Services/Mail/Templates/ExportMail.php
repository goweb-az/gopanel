<?php

declare(strict_types=1);

namespace App\Services\Mail\Templates;

use App\Support\Export\ExportBranding;
use App\Support\Export\ExportFile;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Paneldən göndərilən ixrac faylı (siyahı, hesabat, qaimə).
 *
 * NİYƏ qoşma, link yox: alıcı çox vaxt panelə girişi olmayan adamdır
 * (müştəri, mühasib) - link onun üçün açılmır. Qoşma həm də müddətsizdir,
 * saxlanılan faylın ömrünü izləmək lazım gəlmir.
 *
 * `MailService::send()` ilə göndərilir ki, mail jurnalı ortaq qalsın.
 */
final class ExportMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<string, string>  $details  mailin içində cədvəl kimi göstərilən qısa məlumat
     */
    public function __construct(
        public readonly string $title,
        public readonly string $body,
        private readonly ExportFile $file,
        public readonly array $details = [],
        public readonly ?string $note = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->title);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.export',
            with: [
                'title'    => $this->title,
                'body'     => $this->body,
                'details'  => $this->details,
                'note'     => $this->note,
                'fileName' => $this->file->name,
                'brand'    => ExportBranding::title(),
                'accent'   => ExportBranding::cssColor('accent'),
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->file->contents, $this->file->name)
                ->withMime($this->file->mime),
        ];
    }
}
