<div class="mb-4">
    <div class="log-meta-grid">
        <div class="log-meta-item">
            <div class="label">ID</div>
            <div class="value">#{{ $history->id }}</div>
        </div>
        <div class="log-meta-item">
            <div class="label">Model</div>
            <div class="value">{!! $history->log_name_badge !!}</div>
        </div>
        <div class="log-meta-item">
            <div class="label">Əməliyyat</div>
            <div class="value">{!! $history->event_badge !!}</div>
        </div>
        <div class="log-meta-item">
            <div class="label">Tarix</div>
            <div class="value">{{ $history->created_at_formatted }}</div>
        </div>
        @if($history->causer_name && $history->causer_name !== '-')
        <div class="log-meta-item">
            <div class="label">Kim tərəfindən</div>
            <div class="value">{{ $history->causer_name }}</div>
        </div>
        @endif
        @if($history->subject_type)
        <div class="log-meta-item">
            <div class="label">Hədəf</div>
            <div class="value">{{ class_basename($history->subject_type) }} #{{ $history->subject_id }}</div>
        </div>
        @endif
    </div>
</div>

<!-- Mesaj -->
@php
    $alertIcon = match($history->event) {
        'created' => 'fas fa-plus-circle',
        'updated' => 'fas fa-edit',
        'deleted' => 'fas fa-trash-alt',
        default   => 'fas fa-info-circle',
    };
@endphp
<div class="alert alert-{{ $history->event_color }} border-{{ $history->event_color }} mb-4 d-flex align-items-start" role="alert">
    <div class="flex-shrink-0 me-3 mt-1">
        <i class="{{ $alertIcon }} fa-2x"></i>
    </div>
    <div class="flex-grow-1">
        <h6 class="alert-heading fw-bold mb-1">Mesaj ({{ $history->event_label }})</h6>
        <div style="white-space: pre-wrap; word-break: break-word; font-family: monospace; font-size: 14px;">{{ $history->description }}</div>
    </div>
</div>

@php
    $properties = $history->properties ?? collect();
    $oldData = $properties->get('old', []);
    $newData = $properties->get('attributes', []);
@endphp

<!-- Əvvəlki vəziyyət (Old) -->
<div class="json-viewer-section" id="old-section">
    <div class="section-header" onclick="$(this).closest('.json-viewer-section').toggleClass('collapsed')">
        <span><i class="fas fa-history me-2"></i> Əvvəlki Vəziyyət (Old)</span>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary copy-json-btn" onclick="event.stopPropagation(); copyJsonData('old');" title="Kopyala">
                <i class="fas fa-copy"></i>
            </button>
            <i class="fas fa-chevron-down toggle-icon"></i>
        </div>
    </div>
    <div class="section-body">
        <div id="json-old"></div>
    </div>
</div>

<!-- Yeni vəziyyət (Attributes) -->
<div class="json-viewer-section" id="new-section">
    <div class="section-header" onclick="$(this).closest('.json-viewer-section').toggleClass('collapsed')">
        <span><i class="fas fa-edit me-2"></i> Yeni Vəziyyət (Attributes)</span>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary copy-json-btn" onclick="event.stopPropagation(); copyJsonData('new');" title="Kopyala">
                <i class="fas fa-copy"></i>
            </button>
            <i class="fas fa-chevron-down toggle-icon"></i>
        </div>
    </div>
    <div class="section-body">
        <div id="json-new"></div>
    </div>
</div>

<script>
    window.activityOldData = @json($oldData);
    window.activityNewData = @json($newData);
</script>
