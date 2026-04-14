<div class="mb-4">
    <div class="log-meta-grid">
        <div class="log-meta-item">
            <div class="label">ID</div>
            <div class="value">#{{ $item->id }}</div>
        </div>
        <div class="log-meta-item">
            <div class="label">Level</div>
            <div class="value">

                <span class="badge bg-{{ $item->level_color }}">{{ strtoupper($item->level) }}</span>
            </div>
        </div>
        <div class="log-meta-item">
            <div class="label">Kanal</div>
            <div class="value">{{ $item->channel ?? '-' }}</div>
        </div>
        <div class="log-meta-item">
            <div class="label">Tarix</div>
            <div class="value">{{ $item->created_at?->format('Y-m-d H:i:s') }}</div>
        </div>
        @if($item->admin)
        <div class="log-meta-item">
            <div class="label">Admin</div>
            <div class="value">{{ $item->admin_name }}</div>
        </div>
        @endif
        @if($item->user)
        <div class="log-meta-item">
            <div class="label">İstifadəçi</div>
            <div class="value">{{ $item->user_name }}</div>
        </div>
        @endif
    </div>
</div>

<!-- Mesaj -->
<div class="alert alert-{{ $item->level_color }} border-{{ $item->level_color }} mb-4 d-flex align-items-start" role="alert">
    <div class="flex-shrink-0 me-3 mt-1">
        <i class="{{ $item->level_icon }} fa-2x"></i>
    </div>
    <div class="flex-grow-1">
        <h6 class="alert-heading fw-bold mb-1">Log Mesajı ({{ strtoupper($item->level) }})</h6>
        <div style="white-space: pre-wrap; word-break: break-word; font-family: monospace; font-size: 14px;">{{ $item->message }}</div>
    </div>
</div>

<!-- Context JSON -->
<div class="json-viewer-section collapsed" id="context-section">
    <div class="section-header" onclick="$(this).closest('.json-viewer-section').toggleClass('collapsed')">
        <span><i class="fas fa-database me-2"></i> Context Data</span>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary copy-json-btn" onclick="event.stopPropagation(); copyJsonData('context');" title="Kopyala">
                <i class="fas fa-copy"></i>
            </button>
            <i class="fas fa-chevron-down toggle-icon"></i>
        </div>
    </div>
    <div class="section-body">
        <div id="json-context"></div>
    </div>
</div>

<!-- Log Details JSON -->
<div class="json-viewer-section collapsed" id="details-section">
    <div class="section-header" onclick="$(this).closest('.json-viewer-section').toggleClass('collapsed')">
        <span><i class="fas fa-info-circle me-2"></i> Log Details (HTTP/Route/Session)</span>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary copy-json-btn" onclick="event.stopPropagation(); copyJsonData('details');" title="Kopyala">
                <i class="fas fa-copy"></i>
            </button>
            <i class="fas fa-chevron-down toggle-icon"></i>
        </div>
    </div>
    <div class="section-body">
        <div id="json-details"></div>
    </div>
</div>

<script>
    window.logContextData = @json($item->context ?? []);
    window.logDetailsData = @json($item->log_details ?? []);
</script>
