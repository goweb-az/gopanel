<?php

use App\Models\Activity\FileLog;
use Livewire\Attributes\Lazy;
use Livewire\Component;

new
#[Lazy]
class extends Component {
    public ?int $recordId = null;

    public function placeholder(): string
    {
        return '<div class="text-center py-4 text-muted"><i class="fas fa-spinner fa-spin"></i> ' . e(__('Yüklənir...')) . '</div>';
    }

    public function with(): array
    {
        $record = $this->recordId
            ? FileLog::with(['admin', 'user'])->find($this->recordId)
            : null;

        return ['record' => $record];
    }
}; ?>

<div>
    @if ($record)
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="text-muted small">{{ __('ID') }}</div>
                <div class="fw-semibold">#{{ $record->id }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">{{ __('Səviyyə') }}</div>
                <div>{!! $record->level_badge !!}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">{{ __('Kanal') }}</div>
                <div class="fw-semibold"><code>{{ $record->channel }}</code></div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">{{ __('Tarix') }}</div>
                <div class="fw-semibold">{{ $record->created_at_formatted }}</div>
            </div>
            @if ($record->admin_name)
                <div class="col-md-4">
                    <div class="text-muted small">{{ __('Admin') }}</div>
                    <div class="fw-semibold">{{ $record->admin_name }}</div>
                </div>
            @endif
            @if ($record->user_name)
                <div class="col-md-4">
                    <div class="text-muted small">{{ __('İstifadəçi') }}</div>
                    <div class="fw-semibold">{{ $record->user_name }}</div>
                </div>
            @endif
        </div>

        <div class="alert d-flex align-items-start mb-3" style="background:#f8f9fa;">
            <i class="fas fa-info-circle fa-lg me-2 mt-1 text-primary"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold mb-1">{{ __('Mesaj') }}</div>
                <div style="white-space: pre-wrap; word-break: break-word; font-family: monospace; font-size: 13px;">{{ $record->message }}</div>
            </div>
        </div>

        <div class="row g-2">
            @if (! empty($record->context))
                <div class="col-md-6">
                    <div class="border rounded">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                            <strong><i class="fas fa-cogs me-1"></i> {{ __('Kontekst') }}</strong>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    x-on:click="navigator.clipboard.writeText($refs.ctxJson.textContent)" title="{{ __('Kopyala') }}">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <pre class="m-0 p-3" style="max-height: 320px; overflow:auto; font-size:12px;" x-ref="ctxJson">{{ json_encode($record->context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            @endif
            @if (! empty($record->log_details))
                <div class="col-md-6">
                    <div class="border rounded">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                            <strong><i class="fas fa-list me-1"></i> {{ __('Detallar') }}</strong>
                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                    x-on:click="navigator.clipboard.writeText($refs.detJson.textContent)" title="{{ __('Kopyala') }}">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <pre class="m-0 p-3" style="max-height: 320px; overflow:auto; font-size:12px;" x-ref="detJson">{{ json_encode($record->log_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </div>
            @endif
        </div>
    @else
        <div class="text-center text-muted py-4">{{ __('Tapılmadı') }}</div>
    @endif
</div>
