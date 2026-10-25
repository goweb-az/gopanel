{{--
    Adminin öz PIN-i (AdminPinController@edit).

    PIN şifrənin əvəzi deyil - panelə giriş vermir. Həssas əməliyyatlarda
    (backup endirmə və s.) ikinci təsdiq kimi soruşulur.

    Parametrlər: $item, $hasPin, $updatedAt, $enabled, $minLength, $maxLength, $ttlMinutes
--}}
@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">PIN kodum</h4>
                    <div class="page-title-right">
                        <a class="btn btn-light" href="{{ route('gopanel.profile.index') }}">
                            <i class="fas fa-arrow-left me-1"></i> Profilə qayıt
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body p-4">

                        <div class="text-center mb-4">
                            <div class="avatar-lg mx-auto mb-3">
                                <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-24">
                                    <i class="bx bx-hash"></i>
                                </span>
                            </div>
                            <h5 class="mb-2">{{ $hasPin ? 'PIN təyin edilib' : 'PIN təyin edilməyib' }}</h5>
                            @if ($hasPin && $updatedAt)
                                <div class="text-muted font-size-12">Son dəyişiklik: {{ $updatedAt }}</div>
                            @endif
                        </div>

                        @if (!$enabled)
                            <div class="alert alert-warning font-size-13">
                                <i class="bx bx-info-circle align-middle me-1"></i>
                                PIN təsdiqi hazırda sistemdə <strong>sönükdür</strong>. PIN-i indi təyin edə
                                bilərsiniz - mexanizm açılanda istifadə olunacaq.
                            </div>
                        @endif

                        <div class="alert alert-light border font-size-12">
                            <i class="bx bx-shield-quarter align-middle me-1"></i>
                            PIN <strong>şifrənin əvəzi deyil</strong> - panelə giriş vermir. Yalnız həssas
                            əməliyyatlarda (backup endirmə və s.) ikinci təsdiq kimi soruşulur. Bir dəfə
                            təsdiqləndikdən sonra {{ $ttlMinutes }} dəqiqə yenidən soruşulmur.
                            <div class="mt-1">Mövcud kod göstərilmir - yalnız şifrələnmiş formada saxlanılır.</div>
                        </div>

                        <form method="POST" action="{{ route('gopanel.profile.pin.update') }}" id="static-form">
                            @csrf

                            <div class="mb-3">
                                <label for="profile-pin" class="form-label">Yeni PIN</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-hash"></i></span>
                                    <input type="password" class="form-control" id="profile-pin" name="pin"
                                           inputmode="numeric" autocomplete="new-password"
                                           minlength="{{ $minLength }}" maxlength="{{ $maxLength }}"
                                           placeholder="{{ $minLength }}–{{ $maxLength }} rəqəm">
                                    <button class="btn btn-outline-secondary" type="button"
                                            data-pin-toggle="#profile-pin"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="profile-pin-confirm" class="form-label">PIN təkrar</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-hash"></i></span>
                                    <input type="password" class="form-control" id="profile-pin-confirm"
                                           name="pin_confirmation" inputmode="numeric" autocomplete="new-password"
                                           minlength="{{ $minLength }}" maxlength="{{ $maxLength }}">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="profile-pin-password" class="form-label">Mövcud şifrəniz</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-lock-alt"></i></span>
                                    <input type="password" class="form-control" id="profile-pin-password"
                                           name="current_password" autocomplete="current-password"
                                           placeholder="Təsdiq üçün şifrənizi yazın" required>
                                </div>
                                <small class="text-muted font-size-12">
                                    Sessiya oğurlansa da PIN şifrəsiz dəyişdirilə bilməsin deyə soruşulur.
                                </small>
                            </div>

                            @if ($hasPin)
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" value="1"
                                           id="profile-pin-clear" name="clear">
                                    <label class="form-check-label" for="profile-pin-clear">
                                        PIN-i geri al
                                        <span class="text-muted font-size-12 d-block">
                                            Bundan sonra həssas əməliyyatlarda təsdiq soruşulmayacaq.
                                        </span>
                                    </label>
                                </div>
                            @endif

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bx bx-save align-middle me-1"></i> Yadda saxla
                                </button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script src="/assets/gopanel/js/modules/security/admin-pin.js?v={{ time() }}"></script>
@endpush
