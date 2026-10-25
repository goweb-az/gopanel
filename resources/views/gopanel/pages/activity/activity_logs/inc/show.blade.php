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
<div class="alert alert-{{ $history->event_color }} border-{{ $history->event_color }} mb-4 d-flex align-items-start" role="alert">
    <div class="flex-shrink-0 me-3 mt-1">
        <i class="{{ $alertIcon }} fa-2x"></i>
    </div>
    <div class="flex-grow-1">
        <h6 class="alert-heading fw-bold mb-1">Mesaj ({{ $history->event_label }})</h6>
        <div style="white-space: pre-wrap; word-break: break-word; font-family: monospace; font-size: 14px;">{{ $history->description }}</div>
    </div>
</div>

{{-- Kontekst: modelin `activityContext()` verdiyi əlavə məlumat (bax: ActivityLogHelper::attachContext) --}}
@if(!empty($context))
<div class="log-meta-grid mb-4">
    @foreach($context as $row)
    <div class="log-meta-item">
        <div class="label">{{ $row['label'] }}</div>
        <div class="value">{{ $row['value'] }}</div>
    </div>
    @endforeach
</div>
@endif

{{-- Nə dəyişdi: ActivityChangeFormatter ilə oxunaqlı fərq --}}
@if(!empty($changes))
<div class="table-responsive mb-4">
    <table class="table table-sm table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th>Sahə</th>
                <th>Əvvəl</th>
                <th>Sonra</th>
            </tr>
        </thead>
        <tbody>
            @foreach($changes as $row)
            <tr>
                <td class="fw-semibold">{{ $row['label'] }}</td>
                <td class="text-muted">{{ $row['has_old'] ? $row['old'] : '—' }}</td>
                <td>{{ $row['new'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div id="activity-log-data" class="d-none"
     data-old="{{ json_encode((object) $oldData, JSON_UNESCAPED_UNICODE) }}"
     data-new="{{ json_encode((object) $newData, JSON_UNESCAPED_UNICODE) }}"></div>

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
