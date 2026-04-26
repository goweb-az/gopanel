<!-- Diff Modal -->
<div class="modal fade" id="diffModal" tabindex="-1" aria-labelledby="diffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" id="diffModalDialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="diffModalLabel">Fayl müqayisəsi</h5>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-diff-fullscreen" onclick="toggleDiffFullscreen()">
                        <i class="fas fa-expand"></i>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Bağla"></button>
                </div>
            </div>
            <div class="modal-body p-0">
                <div class="row g-0" id="diff-content">
                    <div class="col-md-6 border-end">
                        <div class="p-2 bg-light border-bottom">
                            <strong class="text-danger" id="diff-label-left"><i class="bx bx-file"></i> Lokal versiya</strong>
                        </div>
                        <pre class="p-3 mb-0 diff-pre" id="diff-local"></pre>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 bg-light border-bottom">
                            <strong class="text-success" id="diff-label-right"><i class="bx bx-cloud-download"></i> Uzaq versiya (GitHub)</strong>
                        </div>
                        <pre class="p-3 mb-0 diff-pre" id="diff-remote"></pre>
                    </div>
                </div>
                <div class="text-center py-4 d-none" id="diff-loading">
                    <div class="spinner-border text-primary spinner-border-sm"></div>
                    <span class="ms-2 text-muted">Yüklənir...</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bağla</button>
            </div>
        </div>
    </div>
</div>
