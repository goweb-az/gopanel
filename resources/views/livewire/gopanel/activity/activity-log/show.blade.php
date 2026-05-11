<?php

use App\Models\Activity\Activity;
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
            ? Activity::with('causer')->find($this->recordId)
            : null;

        return ['record' => $record];
    }
}; ?>

<div>
    @if ($record)
        @php
            $properties = $record->properties ?? collect();
            $oldData = is_object($properties) ? ($properties->get('old', []) ?? []) : ($properties['old'] ?? []);
            $newData = is_object($properties) ? ($properties->get('attributes', []) ?? []) : ($properties['attributes'] ?? []);
            $alertIcon = match($record->event) {
                'created' => 'fas fa-plus-circle',
                'updated' => 'fas fa-edit',
                'deleted' => 'fas fa-trash-alt',
                default   => 'fas fa-info-circle',
            };
        @endphp

        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="text-muted small">{{ __('ID') }}</div>
                <div class="fw-semibold">#{{ $record->id }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">{{ __('Model') }}</div>
                <div>{!! $record->log_name_badge !!}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">{{ __('Əməliyyat') }}</div>
                <div>{!! $record->event_badge !!}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">{{ __('Tarix') }}</div>
                <div class="fw-semibold">{{ $record->created_at_formatted }}</div>
            </div>
            @if ($record->causer_name && $record->causer_name !== '-')
                <div class="col-md-4">
                    <div class="text-muted small">{{ __('Kim') }}</div>
                    <div class="fw-semibold">{{ $record->causer_name }}</div>
                </div>
            @endif
            @if ($record->subject_type)
                <div class="col-md-4">
                    <div class="text-muted small">{{ __('Hədəf') }}</div>
                    <div class="fw-semibold">{{ class_basename($record->subject_type) }} #{{ $record->subject_id }}</div>
                </div>
            @endif
        </div>

        <div class="alert d-flex align-items-start mb-3" style="background:#f8f9fa;">
            <i class="{{ $alertIcon }} fa-lg me-2 mt-1 text-primary"></i>
            <div class="flex-grow-1">
                <div class="fw-semibold mb-1">{{ __('Mesaj') }}</div>
                <div style="white-space: pre-wrap; word-break: break-word; font-family: monospace; font-size: 13px;">{{ $record->description }}</div>
            </div>
        </div>

        @if (! empty($oldData) || ! empty($newData))
            <div class="row g-2">
                @if (! empty($oldData))
                    <div class="col-md-6">
                        <div class="border rounded">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                                <strong><i class="fas fa-history me-1"></i> {{ __('Əvvəlki') }}</strong>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        x-on:click="navigator.clipboard.writeText($refs.oldJson.textContent)" title="{{ __('Kopyala') }}">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <pre class="m-0 p-3" style="max-height: 320px; overflow:auto; font-size:12px;" x-ref="oldJson">{{ json_encode($oldData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                @endif
                @if (! empty($newData))
                    <div class="col-md-6">
                        <div class="border rounded">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom bg-light">
                                <strong><i class="fas fa-edit me-1"></i> {{ __('Yeni') }}</strong>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                        x-on:click="navigator.clipboard.writeText($refs.newJson.textContent)" title="{{ __('Kopyala') }}">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                            <pre class="m-0 p-3" style="max-height: 320px; overflow:auto; font-size:12px;" x-ref="newJson">{{ json_encode($newData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    @else
        <div class="text-center text-muted py-4">{{ __('Tapılmadı') }}</div>
    @endif
</div>
