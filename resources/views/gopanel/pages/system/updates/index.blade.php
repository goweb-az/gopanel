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
                                        <th>Qeyd</th>
                                        <th>Əməliyyat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(array_reverse($localVersion['update_history']) as $hIndex => $history)
                                    <tr>
                                        <td><span class="badge bg-info">v{{ $history['version'] }}</span></td>
                                        <td>
                                            {{ $history['date_formatted'] }}
                                            @if(!empty($history['applied_by']))
                                                <br><small class="text-muted"><i class="bx bx-user"></i> {{ $history['applied_by'] }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $history['files'] }} fayl</td>
                                        <td>
                                            @if(!empty($history['description']))
                                                <small class="text-muted d-block mb-1">{{ $history['description'] }}</small>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">
                                            @if(!empty($history['file_details']))
                                            <button class="btn btn-outline-info me-1" 
                                                    onclick="toggleHistoryFiles('history-files-{{ $hIndex }}')"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Dəyişdirilmiş faylların siyahısını göstər">
                                                <i class="bx bx-show"></i>
                                            </button>
                                            @endif
                                            <button class="btn btn-outline-warning" 
                                                    onclick="rollback('{{ $history['backup_id'] }}')"
                                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Bütün faylları əvvəlki vəziyyətinə qaytar">
                                                <i class="bx bx-undo"></i> Geri al
                                            </button>
                                        </td>
                                    </tr>
                                    @if(!empty($history['file_details']))
                                    <tr class="d-none" id="history-files-{{ $hIndex }}">
                                        <td colspan="5" class="p-0">
                                            <div class="bg-light p-2">
                                                <table class="table table-sm mb-0" style="font-size: 12px;">
                                                    @foreach($history['file_details'] as $fd)
                                                    <tr>
                                                        <td class="border-0" style="width: 70px;">
                                                            @if($fd['action'] === 'added')
                                                                <span class="badge bg-success">Yeni</span>
                                                            @elseif($fd['action'] === 'deleted')
                                                                <span class="badge bg-danger">Silinib</span>
                                                            @else
                                                                <span class="badge bg-primary">Dəyişib</span>
                                                            @endif
                                                        </td>
                                                        <td class="border-0">
                                                            <code>{{ $fd['path'] }}</code>
                                                        </td>
                                                        <td class="border-0 text-end text-nowrap" style="width: 100px;">
                                                            <button class="btn btn-outline-secondary py-0 px-1"
                                                                    onclick="showHistoryDiff('{{ $history['backup_id'] }}', '{{ $fd['path'] }}')"
                                                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Əvvəlki və mövcud versiya arasındakı fərqi göstər">
                                                                <i class="bx bx-git-compare"></i>
                                                            </button>
                                                            <button class="btn btn-outline-warning py-0 px-1 ms-1"
                                                                    onclick="rollbackSingleFile('{{ $history['backup_id'] }}', '{{ $fd['path'] }}')"
                                                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Bu faylı backup-dan geri qaytar">
                                                                <i class="bx bx-undo"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
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

@include('gopanel.pages.system.updates._diff_modal')

@endsection
@push('scripts')
<script src="{{ asset('assets/gopanel/js/modules/updater.js') }}?v={{time()}}"></script>
@endpush
