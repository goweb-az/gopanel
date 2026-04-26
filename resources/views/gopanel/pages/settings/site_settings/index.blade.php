@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">

        <!-- Page title -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Sayt Tənzimləmələri</h4>
                </div>
            </div>
        </div>

        <form method="POST" action="{{route("gopanel.settings.site-settings.save.form", $item)}}" id="static-form" enctype="multipart/form-data">

            <div class="row">
                <!-- Sol tərəf: Status & SEO -->
                <div class="col-xl-8">

                    <!-- Ümumi Status Tənzimləmələri -->
                    <div class="card">
                        <div class="card-header bg-transparent border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-toggle-left text-primary me-2"></i>Ümumi Status Tənzimləmələri
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="siteStatusSelect" class="form-label fw-semibold">Sayt Statusu</label>
                                    <select id="siteStatusSelect" class="form-select" name="site_status">
                                        <option value="1" @selected(isset($item->id) && $item->site_status == 1)>
                                            ✅ Açıq
                                        </option>
                                        <option value="0" @selected(isset($item->id) && $item->site_status == 0)>
                                            ❌ Bağlı
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="loginStatusSelect" class="form-label fw-semibold">Login Statusu</label>
                                    <select id="loginStatusSelect" class="form-select" name="login_status">
                                        <option value="1" @selected(isset($item->id) && $item->login_status == 1)>
                                            ✅ Açıq
                                        </option>
                                        <option value="0" @selected(isset($item->id) && $item->login_status == 0)>
                                            ❌ Bağlı
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="registerStatusSelect" class="form-label fw-semibold">Qeydiyyat Statusu</label>
                                    <select id="registerStatusSelect" class="form-select" name="register_status">
                                        <option value="1" @selected(isset($item->id) && $item->register_status == 1)>
                                            ✅ Açıq
                                        </option>
                                        <option value="0" @selected(isset($item->id) && $item->register_status == 0)>
                                            ❌ Bağlı
                                        </option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="paymentStatusSelect" class="form-label fw-semibold">Ödəniş Statusu</label>
                                    <select id="paymentStatusSelect" class="form-select" name="payment_status">
                                        <option value="1" @selected(isset($item->id) && $item->payment_status == 1)>
                                            ✅ Açıq
                                        </option>
                                        <option value="0" @selected(isset($item->id) && $item->payment_status == 0)>
                                            ❌ Bağlı
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SEO & Təhlükəsizlik -->
                    <div class="card">
                        <div class="card-header bg-transparent border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-shield-quarter text-success me-2"></i>SEO & Təhlükəsizlik
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Site Redirect -->
                                <div class="col-md-4">
                                    <div class="border rounded-3 p-3 h-100">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bx bx-link text-info font-size-20 me-2"></i>
                                            <h6 class="mb-0">Yönləndirmələr</h6>
                                        </div>
                                        <p class="text-muted small mb-3">
                                            SEO yönləndirmə qaydalarını aktiv/deaktiv edir.
                                        </p>
                                        <div class="form-check form-switch form-switch-lg">
                                            <input class="form-check-input" type="checkbox" id="siteRedirectStatus"
                                                   name="site_redirect_status" value="1"
                                                   @checked(old('site_redirect_status', $item->site_redirect_status ?? true))>
                                            <label class="form-check-label" for="siteRedirectStatus">
                                                {{ ($item->site_redirect_status ?? true) ? 'Aktiv' : 'Deaktiv' }}
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Analytics -->
                                <div class="col-md-4">
                                    <div class="border rounded-3 p-3 h-100">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bx bx-bar-chart-alt-2 text-warning font-size-20 me-2"></i>
                                            <h6 class="mb-0">Analitika</h6>
                                        </div>
                                        <p class="text-muted small mb-3">
                                            Saytda ziyarətçi izləmə (analytics) sistemini aktivləşdirir.
                                        </p>
                                        <div class="form-check form-switch form-switch-lg">
                                            <input class="form-check-input" type="checkbox" id="siteAnalytics"
                                                   name="site_analytics" value="1"
                                                   @checked(old('site_analytics', $item->site_analytics ?? false))>
                                            <label class="form-check-label" for="siteAnalytics">
                                                {{ ($item->site_analytics ?? false) ? 'Aktiv' : 'Deaktiv' }}
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bot Blocking -->
                                <div class="col-md-4">
                                    <div class="border rounded-3 p-3 h-100">
                                        <div class="d-flex align-items-center mb-2">
                                            <i class="bx bx-bot text-danger font-size-20 me-2"></i>
                                            <h6 class="mb-0">Bot Bloklaması</h6>
                                        </div>
                                        <p class="text-muted small mb-3">
                                            Zərərli bot-ları bloklamaq üçün JS cookie yoxlamasını aktivləşdirir.
                                        </p>
                                        <div class="form-check form-switch form-switch-lg">
                                            <input class="form-check-input" type="checkbox" id="blockBadBots"
                                                   name="block_bad_bots" value="1"
                                                   @checked(old('block_bad_bots', $item->block_bad_bots ?? false))>
                                            <label class="form-check-label" for="blockBadBots">
                                                {{ ($item->block_bad_bots ?? false) ? 'Aktiv' : 'Deaktiv' }}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Meta Data Tabs -->
                    <div class="card">
                        <div class="card-header bg-transparent border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-code-alt text-secondary me-2"></i>Meta Məlumatlar
                            </h5>
                        </div>
                        <div class="card-body">
                            <ul class="nav nav-tabs" role="tablist">
                                @foreach ($languages as $lang)
                                <li class="nav-item">
                                    <a class="nav-link {{$loop->first ? 'active' : ''}}" data-bs-toggle="tab" href="#lang_key_{{$lang->code}}" role="tab">
                                        <span>{{$lang->upper_code}}</span>
                                    </a>
                                </li>
                                @endforeach
                            </ul>
                            <div class="tab-content tab-p text-muted">
                                @foreach ($languages as $lang)
                                <div class="tab-pane {{$loop->first ? 'active' : ''}}" id="lang_key_{{$lang->code}}" role="tabpanel">
                                    @include("gopanel.component.meta", ['lang' => $lang->code])
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sağ tərəf: Loqolar -->
                <div class="col-xl-4">
                    <div class="card">
                        <div class="card-header bg-transparent border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="bx bx-image text-purple me-2"></i>Loqolar
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Logo Light -->
                            <div class="mb-4">
                                <label for="logo_light" class="form-label fw-semibold">
                                    Logo White <small class="text-danger">152x48</small>
                                </label>
                                @if ($item->logo_light_url ?? false)
                                <div class="mb-2 p-2 bg-dark rounded text-center">
                                    <img src="{{ $item->logo_light_url }}" alt="Logo Light" class="img-fluid" style="max-height:48px;">
                                </div>
                                @endif
                                <div class="input-group">
                                    <input type="file" class="form-control" id="logo_light" name="logo_light" accept="image/*">
                                    @if ($item->logo_light_url ?? false)
                                    <a class="btn btn-outline-primary" href="{{ $item->logo_light_url }}" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endif
                                </div>
                            </div>

                            <!-- Logo Dark -->
                            <div class="mb-4">
                                <label for="logo_dark" class="form-label fw-semibold">
                                    Logo Dark <small class="text-danger">152x48</small>
                                </label>
                                @if ($item->logo_dark_url ?? false)
                                <div class="mb-2 p-2 bg-light rounded text-center">
                                    <img src="{{ $item->logo_dark_url }}" alt="Logo Dark" class="img-fluid" style="max-height:48px;">
                                </div>
                                @endif
                                <div class="input-group">
                                    <input type="file" class="form-control" id="logo_dark" name="logo_dark" accept="image/*">
                                    @if ($item->logo_dark_url ?? false)
                                    <a class="btn btn-outline-primary" href="{{ $item->logo_dark_url }}" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endif
                                </div>
                            </div>

                            <!-- Gopanel Logo -->
                            <div class="mb-2">
                                <label for="gopanel_logo" class="form-label fw-semibold">
                                    Gopanel Logo <small class="text-danger">152x48</small>
                                </label>
                                @if ($item->gopanel_logo_url ?? false)
                                <div class="mb-2 p-2 bg-light rounded text-center">
                                    <img src="{{ $item->gopanel_logo_url }}" alt="Gopanel Logo" class="img-fluid" style="max-height:48px;">
                                </div>
                                @endif
                                <div class="input-group">
                                    <input type="file" class="form-control" id="gopanel_logo" name="gopanel_logo" accept="image/*">
                                    @if ($item->gopanel_logo_url ?? false)
                                    <a class="btn btn-outline-primary" href="{{ $item->gopanel_logo_url }}" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="card">
                        <div class="card-body text-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-save me-1"></i> Yadda Saxla
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </form>

    </div>
</div>

@push('scripts')
<script>
    // Toggle label update
    document.querySelectorAll('.form-check-input[type="checkbox"]').forEach(function(el) {
        el.addEventListener('change', function() {
            var label = this.closest('.form-check').querySelector('.form-check-label');
            if (label) {
                label.textContent = this.checked ? 'Aktiv' : 'Deaktiv';
            }
        });
    });
</script>
@endpush
@endsection