{{-- Sistem vəziyyətinin CANLI hissəsi.
     Səhifə yeniləndikcə bu blok bütöv şəkildə serverdən gəlir və yerinə
     qoyulur — beləliklə formatlaşdırma yalnız blade-də qalır, JS-də deyil
     (bax: 01-umumi.md § 3). Qrafiklər bu blokun XARİCİNDƏDİR. --}}

{{-- ── Növbə (queue) ────────────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-xl-3 col-md-6">
        <div class="card gp-sys-stat h-100 mb-0">
            <div class="card-body">
                <p class="text-muted mb-2">Növbədə gözləyən</p>
                <h4 class="mb-0">{{ $status['queue']['pending'] }}</h4>
                <span class="gp-sys-stat-icon text-warning"><i class="bx bx-time-five"></i></span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card gp-sys-stat h-100 mb-0">
            <div class="card-body">
                <p class="text-muted mb-2">İcra olunan</p>
                <h4 class="mb-0">{{ $status['queue']['running'] }}</h4>
                <span class="gp-sys-stat-icon text-info"><i class="bx bx-loader-circle"></i></span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card gp-sys-stat h-100 mb-0">
            <div class="card-body">
                <p class="text-muted mb-2">Uğursuz işlər</p>
                <h4 class="mb-0 {{ $status['queue']['failed'] > 0 ? 'text-danger' : '' }}">
                    {{ $status['queue']['failed'] }}
                </h4>
                <span class="gp-sys-stat-icon text-danger"><i class="bx bx-error-circle"></i></span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card gp-sys-stat h-100 mb-0">
            <div class="card-body">
                <p class="text-muted mb-2">Ən uzun gözləmə</p>
                <h4 class="mb-0 font-size-18">{{ $status['queue']['oldest_text'] }}</h4>
                <span class="gp-sys-stat-icon text-primary"><i class="bx bx-hourglass"></i></span>
            </div>
        </div>
    </div>
</div>

@if ($status['queue']['warning'])
    <div class="alert alert-warning small" role="alert">
        <i class="bx bx-error me-1"></i> {{ $status['queue']['warning'] }}
    </div>
@endif

{{-- ── İşlər ────────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="card h-100 mb-0">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-tasks text-primary me-1"></i> Növbədəki işlər
                    <span class="text-muted font-size-12">({{ $status['queue']['driver'] }})</span>
                </h5>

                <div class="table-responsive gp-sys-table">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>İş</th>
                                <th>Növbə</th>
                                <th>Vəziyyət</th>
                                <th>Gözləmə</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($status['pending'] as $job)
                                <tr>
                                    <td>{{ $job['name'] }}</td>
                                    <td>{{ $job['queue'] }}</td>
                                    <td><span class="badge bg-{{ $job['tone'] }}">{{ $job['state'] }}</span></td>
                                    <td>{{ $job['waiting'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        Növbə boşdur.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($status['queue']['queues'])
                    <div class="table-responsive gp-sys-table mt-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Növbə adı</th>
                                    <th>Gözləyən</th>
                                    <th>İcrada</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($status['queue']['queues'] as $queue)
                                    <tr>
                                        <td>{{ $queue['queue'] }}</td>
                                        <td>{{ $queue['pending'] }}</td>
                                        <td>{{ $queue['running'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card h-100 mb-0">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-exclamation-triangle text-danger me-1"></i> Uğursuz işlər
                </h5>

                <div class="table-responsive gp-sys-table">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>İş</th>
                                <th>Səbəb</th>
                                <th>Tarix</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($status['failed'] as $job)
                                <tr>
                                    <td>{{ $job['name'] }}</td>
                                    <td class="text-muted font-size-12">{{ $job['reason'] }}</td>
                                    <td class="text-nowrap">{{ $job['failed_at'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">
                                        Uğursuz iş yoxdur.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Planlaşdırıcı (cron) ─────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-xl-4">
        <div class="card h-100 mb-0">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-clock text-primary me-1"></i> Planlaşdırıcı (cron)
                </h5>

                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-{{ $status['scheduler']['tone'] }} font-size-12">
                        {{ $status['scheduler']['state_text'] }}
                    </span>
                    <span class="text-muted small">{{ $status['scheduler']['ago_text'] }}</span>
                </div>

                <ul class="list-unstyled gp-sys-meta mb-0">
                    <li>
                        <span>Sonuncu siqnal</span>
                        <b>{{ $status['scheduler']['last_text'] }}</b>
                    </li>
                </ul>

                @if ($status['scheduler']['hint'])
                    <p class="text-muted small mb-0 mt-3">
                        {{ $status['scheduler']['hint'] }}
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card h-100 mb-0">
            <div class="card-body">
                <h5 class="card-title mb-3">
                    <i class="fas fa-list-ul text-primary me-1"></i> Planlaşdırılmış işlər
                </h5>

                <div class="table-responsive gp-sys-table">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>İş</th>
                                <th>Cədvəl</th>
                                <th>Növbəti icra</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($status['scheduler']['events'] as $event)
                                <tr>
                                    <td>{{ $event['name'] }}</td>
                                    <td><code>{{ $event['expression'] }}</code></td>
                                    <td class="text-nowrap">{{ $event['next'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">
                                        Planlaşdırılmış iş yoxdur.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Server, PHP, baza ────────────────────────────────────────────── --}}
<div class="row g-3 mb-3">
    <div class="col-xl-4 col-md-6">
        @include('gopanel.pages.system_status.partials.info-card', ['card' => $status['server']])
    </div>
    <div class="col-xl-4 col-md-6">
        @include('gopanel.pages.system_status.partials.info-card', ['card' => $status['php']])
    </div>
    <div class="col-xl-4 col-md-6">
        @include('gopanel.pages.system_status.partials.info-card', ['card' => $status['database']])
    </div>
</div>

{{-- ── Storage və arxivlər ──────────────────────────────────────────── --}}
<div class="row g-3">
    <div class="col-xl-6">
        @include('gopanel.pages.system_status.partials.info-card', ['card' => $status['storage']])
    </div>
    <div class="col-xl-6">
        @include('gopanel.pages.system_status.partials.info-card', ['card' => $status['backup']])
    </div>
</div>
