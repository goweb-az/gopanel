<?php

use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[\Livewire\Attributes\Lazy]
#[Layout('gopanel.layouts.main')]
class extends Component
{
    public bool $isRunning = false;

    public function mount(): void
    {
        $this->checkStatus();
    }

    public function checkStatus(): void
    {
        $output = shell_exec('pgrep -f mailpit');
        $this->isRunning = ! empty(trim($output ?? ''));
    }

    public function startMailpit(): void
    {
        $smtpPort = config('mail.mailers.smtp.port', 1025);
        $binary = base_path('resources/views/livewire/gopanel/dev/mailpit/mailpit');
        shell_exec(escapeshellarg($binary) . " --smtp 0.0.0.0:{$smtpPort} > /dev/null 2>&1 &");
        sleep(1);
        $this->checkStatus();
    }

    public function stopMailpit(): void
    {
        shell_exec('pkill -f mailpit');
        sleep(1);
        $this->checkStatus();
    }

    public function sendTestMail(): void
    {
        $body    = __('Bu test e-poçt :time tarixində göndərilib.', ['time' => now()->toDateTimeString()]);
        $subject = __('Test e-poçt — Mailpit');

        Mail::mailer('smtp')->raw($body, function ($message) use ($subject): void {
            $message->to('test@example.com')->subject($subject);
        });

        $this->dispatch('notify', type: 'success', message: __('Test e-poçt göndərildi'));
    }
}; ?>

<div class="page-content">
    <div class="container-fluid">
        <div class="d-flex flex-column overflow-hidden rounded border bg-white" style="height: calc(100vh - 8rem);">
            <div class="d-flex align-items-center justify-content-between border-bottom px-3 py-2">
                <h3 class="font-size-13 fw-medium text-dark mb-0">{{ __('Mail test serveri — Mailpit SMTP') }}</h3>
                <div class="d-flex align-items-center gap-2">
                    @if ($isRunning)
                        <button type="button" class="btn btn-primary btn-sm" wire:click="sendTestMail" wire:loading.attr="disabled" wire:target="sendTestMail">
                            <span wire:loading.remove wire:target="sendTestMail"><i class="fas fa-paper-plane me-1"></i> {{ __('Test e-poçt') }}</span>
                            <span wire:loading wire:target="sendTestMail"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Göndərilir...') }}</span>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm" wire:click="stopMailpit" wire:loading.attr="disabled" wire:target="stopMailpit">
                            <span wire:loading.remove wire:target="stopMailpit">{{ __('Dayandır') }}</span>
                            <span wire:loading wire:target="stopMailpit"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Dayandırılır...') }}</span>
                        </button>
                    @else
                        <button type="button" class="btn btn-success btn-sm" wire:click="startMailpit" wire:loading.attr="disabled" wire:target="startMailpit">
                            <span wire:loading.remove wire:target="startMailpit">{{ __('Başlat') }}</span>
                            <span wire:loading wire:target="startMailpit"><i class="fas fa-spinner fa-spin me-1"></i> {{ __('Başladılır...') }}</span>
                        </button>
                    @endif
                    <button type="button" class="btn btn-light btn-sm" wire:click="checkStatus" wire:loading.attr="disabled" wire:target="checkStatus">
                        <span wire:loading.remove wire:target="checkStatus">{{ __('Yenilə') }}</span>
                        <span wire:loading wire:target="checkStatus">{{ __('Yenilənir...') }}</span>
                    </button>
                </div>
            </div>

            <div class="flex-grow-1">
                @if ($isRunning)
                    <iframe src="http://127.0.0.1:8025" class="h-100 w-100 border-0" title="Mailpit"></iframe>
                @else
                    <div class="py-5 text-center">
                        <p class="text-muted small mb-0">{{ __('Mailpit işləmir. Test serverini başlatmaq üçün "Başlat" düyməsinə klikləyin.') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
