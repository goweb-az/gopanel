@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">

        <!-- start page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Sistem Yeniləmələri</h4>
                </div>
            </div>
        </div>
        <!-- end page title -->

        <!-- Current Version Info -->
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                            <div>
                                <h5 class="card-title mb-1">
                                    Gopanel 
                                    <span class="badge bg-primary font-size-14" id="current-version">
                                        v{{ $localVersion['installed_version'] ?? '0.0.0' }}
                                    </span>
                                </h5>
                                <p class="text-muted mb-0" id="last-checked">
                                    @if($localVersion['last_checked_at_formatted'])
                                        Son yoxlama: {{ $localVersion['last_checked_at_formatted'] }}
                                    @else
                                        Heç vaxt yoxlanmayıb
                                    @endif
                                </p>
                            </div>
                            <div>
                                <button type="button" class="btn btn-primary" id="btn-check-updates" onclick="checkUpdates()">
                                    <i class="bx bx-refresh"></i> Yeniləmələri yoxla
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading -->
        <div class="row d-none" id="loading-section">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Yüklənir...</span>
                        </div>
                        <p class="text-muted mt-3 mb-0" id="loading-text">GitHub ilə əlaqə qurulur...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Updates -->
        <div class="row d-none" id="no-updates-section">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="avatar-sm mx-auto mb-3">
                            <span class="avatar-title rounded-circle bg-soft-success text-success font-size-24">
                                <i class="bx bx-check"></i>
                            </span>
                        </div>
                        <h5 class="text-success">Sistem güncəldir!</h5>
                        <p class="text-muted mb-0">Heç bir yeniləmə tapılmadı.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error -->
        <div class="row d-none" id="error-section">
            <div class="col-xl-12">
                <div class="alert alert-danger mb-0" id="error-message"></div>
            </div>
        </div>

        <!-- Updates List -->
        <div class="row d-none" id="updates-section">
            <div class="col-xl-12" id="updates-container">
                <!-- Dinamik olaraq doldurulacaq -->
            </div>
        </div>

        <!-- Apply Button -->
        <div class="row d-none" id="apply-section">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <span class="text-muted" id="selected-count">0 fayl seçilib</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success" id="btn-apply" onclick="applyUpdates()">
                                    <i class="bx bx-download"></i> Seçilmişləri yenilə
                                </button>
                            </div>
                        </div>
                        <!-- Progress bar -->
                        <div class="progress mt-3 d-none" id="apply-progress" style="height: 6px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rollback Section -->
        @if(!empty($localVersion['update_history']))
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Yeniləmə tarixçəsi</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Versiya</th>
                                        <th>Tarix</th>
                                        <th>Fayl sayı</th>
                                        <th>Əməliyyat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(array_reverse($localVersion['update_history']) as $history)
                                    <tr>
                                        <td><span class="badge bg-info">v{{ $history['version'] }}</span></td>
                                        <td>{{ $history['date_formatted'] }}</td>
                                        <td>{{ $history['files'] }} fayl</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-warning" 
                                                    onclick="rollback('{{ $history['backup_id'] }}')">
                                                <i class="bx bx-undo"></i> Geri al
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div> <!-- container-fluid -->
</div>
<!-- End Page-content -->

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
                            <strong class="text-danger"><i class="bx bx-file"></i> Lokal versiya</strong>
                        </div>
                        <pre class="p-3 mb-0 diff-pre" id="diff-local"></pre>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 bg-light border-bottom">
                            <strong class="text-success"><i class="bx bx-cloud-download"></i> Uzaq versiya (GitHub)</strong>
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

<style>
    .diff-pre {
        font-size: 12px;
        max-height: 500px;
        overflow: auto;
        background: #1e1e1e;
        color: #d4d4d4;
        margin: 0;
        white-space: pre-wrap;
        word-break: break-all;
    }
    .diff-pre .diff-line { display: block; padding: 0 4px; min-height: 20px; }
    .diff-pre .diff-added { background: rgba(46, 160, 67, 0.25); color: #7ee787; }
    .diff-pre .diff-removed { background: rgba(248, 81, 73, 0.25); color: #ffa198; }
    .diff-pre .diff-changed { background: rgba(210, 153, 34, 0.2); color: #e3b341; }
    .diff-pre .line-num { 
        display: inline-block; 
        width: 40px; 
        color: #6e7681; 
        text-align: right; 
        padding-right: 8px; 
        margin-right: 8px; 
        border-right: 1px solid #30363d;
        user-select: none; 
    }

    /* Fullscreen mode */
    .diff-fullscreen .modal-dialog {
        max-width: 100% !important;
        width: 100% !important;
        height: 100% !important;
        margin: 0 !important;
    }
    .diff-fullscreen .modal-content {
        height: 100vh;
        border-radius: 0;
    }
    .diff-fullscreen .modal-body {
        overflow: hidden;
    }
    .diff-fullscreen .diff-pre {
        max-height: calc(100vh - 130px) !important;
    }
    .diff-fullscreen .modal-body > .row {
        height: calc(100vh - 130px);
    }
    .diff-fullscreen .modal-body > .row > div {
        height: 100%;
        overflow: hidden;
    }
</style>

@endsection
@push('scripts')
<script src="/assets/gopanel/js/modules/updater.js?={{time()}}"></script>
@endpush
