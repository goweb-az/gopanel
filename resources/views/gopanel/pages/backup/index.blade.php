@extends('gopanel.layouts.main')
@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-sm-0 font-size-18 text-uppercase">Backup</h4>
                            <p class="text-muted small mb-0">
                                Arxivlər serverdə <code>storage/app/backups</code> qovluğundadır —
                                birbaşa ünvanla açıla bilmir, yalnız buradan endirilir.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            {{-- Kartlarda `h-100` ilə birlikdə MÜTLƏQ `mb-0` olmalıdır:
                 Skote-da `.card` margin-bottom saxlayır, `h-100` isə hündürlüyü
                 sütuna bərabərləşdirir — ikisi birlikdə kartı sütundan kənara
                 daşırır və aşağıdakı blokla üst-üstə salır. --}}
            <div class="row g-3 mb-4" id="backup-actions"
                 data-start-url="{{ route('gopanel.backup.start') }}"
                 data-status-url="{{ route('gopanel.backup.status') }}"
                 data-in-progress="{{ $summary['in_progress'] ? 1 : 0 }}">

                <div class="col-xl-4 col-md-6">
                    <div class="card h-100 mb-0">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-database text-primary me-1"></i> Baza
                            </h5>

                            <p class="text-muted small mb-3">
                                Bütün məzmun, tərcümələr, istifadəçilər və ayarlar.
                                Arxiv <code>.sql.gz</code> formatındadır.
                            </p>

                            <ul class="list-unstyled text-muted small mb-3">
                                <li>Sonuncu: <b>{{ $summary['database_date'] ?? 'hələ çıxarılmayıb' }}</b></li>
                                @if ($summary['database_size'])
                                    <li>Ölçü: <b>{{ $summary['database_size'] }}</b></li>
                                @endif
                            </ul>

                            @if ($can['add'])
                                <button type="button" class="btn btn-primary w-100" data-backup-start="database">
                                    <i class="fas fa-play me-1"></i> Baza backup-ı çıxar
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card h-100 mb-0">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-images text-primary me-1"></i> Fayllar
                            </h5>

                            <p class="text-muted small mb-3">
                                Paneldən yüklənən şəkil, sənəd və videolar.
                                @if ($summary['files_has_base'])
                                    Arxivə <b>yalnız sonuncu backup-dan sonra əlavə olunanlar</b> düşür.
                                @else
                                    İlk arxiv tam olacaq, sonrakılara yalnız yeni fayllar düşəcək.
                                @endif
                            </p>

                            <ul class="list-unstyled text-muted small mb-3">
                                <li>Sonuncu: <b>{{ $summary['files_date'] ?? 'hələ çıxarılmayıb' }}</b></li>
                                <li>Qovluğun ölçüsü: <b>{{ $summary['source_size'] }}</b></li>
                            </ul>

                            @if ($can['add'])
                                <button type="button" class="btn btn-primary w-100" data-backup-start="files">
                                    <i class="fas fa-play me-1"></i> Fayl backup-ı çıxar
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 col-md-6">
                    <div class="card h-100 mb-0">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-hdd text-primary me-1"></i> Disk
                            </h5>

                            <ul class="list-unstyled text-muted small mb-0">
                                <li>Boş yer: <b>{{ $summary['free_space'] }}</b></li>
                                <li>Backup-lar: <b>{{ $summary['total_size'] }}</b></li>
                            </ul>

                            <p class="text-muted small mb-0 mt-3">
                                Köhnə arxivlər avtomatik silinmir. Disk dolmasın deyə
                                lazımsız olanları siyahıdan silin.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="alert alert-info small" role="alert">
                                <b>Bərpa:</b> baza arxivi tək fayldır.
                                Fayl arxivləri isə artımlıdır — bərpa üçün
                                <b>bütün fayl arxivləri köhnədən yeniyə doğru</b> eyni qovluğa açılmalıdır.
                                Siyahıdan bir fayl arxivi silinsə, ondakı fayllar növbəti backup-a yenidən düşür.
                            </div>

                            @include('gopanel.component.datatable', [
                                '__datatableName' => 'gopanel.backup.backup',
                                '__datatableId'   => 'backups',
                            ])
                        </div>
                    </div>
                </div><!--end col-->
            </div>

        </div> <!-- container-fluid -->
    </div>
    <!-- End Page-content -->
@endsection
@push('scripts')
    <script src="{{ asset('assets/gopanel/js/modules/backup.js') }}"></script>
@endpush
