{{--
    Adi keçid (məs. backup endirmə linki) üçün PIN təsdiqi səhifəsi.

    `RequireAdminPin` brauzer naviqasiyasını bura yönləndirir; hədəf URL
    sessiyadadır. Forma JS-siz də işləyir (adi POST → hədəfə yönləndirmə);
    `modules/security/admin-pin.js` onu AJAX-a çevirib uğurdan sonra
    «Təsdiqləndi» blokunu göstərir - endirmə zamanı səhifə forma ilə qalmasın.

    Parametrlər: $backUrl
--}}
@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">

        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card mt-4">
                    <div class="card-body p-4">

                        <div class="text-center mb-3">
                            <div class="avatar-md mx-auto mb-3">
                                <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-24">
                                    <i class="bx bx-lock-alt"></i>
                                </span>
                            </div>
                            <h5 class="mb-1">PIN təsdiqi</h5>
                            <p class="text-muted font-size-13 mb-0">Bu əməliyyat üçün PIN kodunuzu daxil edin.</p>
                        </div>

                        <form method="POST" action="{{ route('gopanel.pin.confirm') }}" id="pin-prompt-form">
                            @csrf
                            <div class="mb-3">
                                <input type="password" inputmode="numeric" autocomplete="one-time-code"
                                       class="form-control form-control-lg text-center @error('pin') is-invalid @enderror"
                                       name="pin" placeholder="PIN" required autofocus>
                                @error('pin')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Təsdiqlə</button>
                                <a href="{{ $backUrl }}" class="btn btn-light">Geri qayıt</a>
                            </div>
                        </form>

                        <div class="text-center d-none" id="pin-prompt-done">
                            <div class="alert alert-success font-size-13">
                                PIN təsdiqləndi. Əməliyyat başladı.
                            </div>
                            <a href="{{ $backUrl }}" class="btn btn-light w-100">Geri qayıt</a>
                        </div>

                        <div class="text-center mt-3">
                            <a href="{{ route('gopanel.profile.pin.index') }}" class="font-size-12 text-muted">
                                PIN-i unutmusunuz? Profildən şifrə ilə yeniləyin.
                            </a>
                        </div>

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
