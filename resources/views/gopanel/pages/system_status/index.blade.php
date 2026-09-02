@extends('gopanel.layouts.main')
@section('content')
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-sm-0 font-size-18 text-uppercase">Sistem vəziyyəti</h4>
                            <p class="text-muted small mb-0">
                                Serverin anlıq göstəriciləri: prosessor, yaddaş, disk, növbə və planlaşdırıcı.
                                Səhifə özü yenilənir — heç nə saxlanılmır, hər dəfə canlı dəyər oxunur.
                            </p>
                        </div>

                        <div class="page-title-right d-flex align-items-center gap-3">
                            <span class="text-muted small">
                                Sonuncu yenilənmə: <b id="system-checked-at">{{ $status['checked_at'] }}</b>
                            </span>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="system-autorefresh" checked>
                                <label class="form-check-label small" for="system-autorefresh">Avtomatik</label>
                            </div>
                            <button type="button" class="btn btn-light border btn-sm" id="system-refresh">
                                <i class="bx bx-refresh"></i> Yenilə
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            {{-- Kartlarda `h-100` ilə birlikdə MÜTLƏQ `mb-0` olur: Skote-da `.card`
                 margin-bottom saxlayır, `h-100` isə hündürlüyü sütuna bərabərləşdirir —
                 ikisi birlikdə kartı sütundan kənara daşırır. --}}
            <div id="system-status"
                 data-url="{{ route('gopanel.system-status.data') }}"
                 data-refresh="{{ $refreshMs }}"
                 data-history="{{ $historyMax }}"
                 data-gauges="{{ json_encode($gauges) }}">

                {{-- ── Qrafiklər: blokun bu hissəsi yenilənmədə DƏYİŞMİR,
                     yalnız rəqəmləri yenilənir (ApexCharts obyektləri qalır) --}}
                <div class="row g-3 mb-3">
                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 mb-0">
                            <div class="card-body">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-microchip text-primary me-1"></i> Prosessor
                                </h5>
                                <div id="gauge-cpu" class="apex-charts gp-sys-gauge" dir="ltr"></div>
                                <div id="meta-cpu">
                                    @include('gopanel.pages.system_status.partials.gauge-meta', [
                                        'metric' => $status['cpu'],
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 mb-0">
                            <div class="card-body">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-memory text-primary me-1"></i> Yaddaş (RAM)
                                </h5>
                                <div id="gauge-memory" class="apex-charts gp-sys-gauge" dir="ltr"></div>
                                <div id="meta-memory">
                                    @include('gopanel.pages.system_status.partials.gauge-meta', [
                                        'metric' => $status['memory'],
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 mb-0">
                            <div class="card-body">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-hdd text-primary me-1"></i> Disk
                                </h5>
                                <div id="gauge-disk" class="apex-charts gp-sys-gauge" dir="ltr"></div>
                                <div id="meta-disk">
                                    @include('gopanel.pages.system_status.partials.gauge-meta', [
                                        'metric' => $status['disk'],
                                    ])
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card h-100 mb-0">
                            <div class="card-body">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-line text-primary me-1"></i> Canlı yük
                                </h5>
                                <div id="chart-live" class="apex-charts gp-sys-gauge" dir="ltr"></div>
                                <p class="text-muted small mb-0">
                                    Səhifə açıq olduğu müddətdə ölçülür — bağlananda sıfırlanır.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ── Canlı bloklar: yenilənmədə bütöv şəkildə serverdən gəlir --}}
                <div id="system-live">
                    @include('gopanel.pages.system_status.partials.live', ['status' => $status])
                </div>
            </div>

            {{-- ── Crontab: yalnız səhifə ilk açılanda oxunur ─────────────── --}}
            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-terminal text-primary me-1"></i> Serverin crontab qeydləri
                            </h5>

                            @if ($crontab['available'])
                                <pre class="gp-sys-cron mb-0">{{ implode("\n", $crontab['lines']) }}</pre>
                            @else
                                <p class="text-muted small mb-0">{{ $crontab['note'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        </div> <!-- container-fluid -->
    </div>
    <!-- End Page-content -->
@endsection

@push('scripts')
    <script src="{{ asset('assets/gopanel/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/gopanel/js/modules/system-status.js') }}"></script>
@endpush
