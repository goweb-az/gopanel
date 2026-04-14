@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Şifrə Dəyişmə</h4>
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
                                <span class="avatar-title rounded-circle bg-soft-warning text-warning font-size-24">
                                    <i class="fas fa-key"></i>
                                </span>
                            </div>
                            <h5>Şifrənizi dəyişdirin</h5>
                            <p class="text-muted">Təhlükəsizlik üçün güclü şifrə istifadə edin</p>
                        </div>

                        <form method="POST" action="{{ route('gopanel.profile.change-password') }}" id="static-form">
                            @csrf
                            <div class="mb-3">
                                <label for="current-password" class="form-label">Mövcud şifrə</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="current-password" name="current_password" placeholder="Mövcud şifrənizi daxil edin" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#current-password"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="new-password" class="form-label">Yeni şifrə</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                    <input type="password" class="form-control" id="new-password" name="password" placeholder="Yeni şifrə (min 6 simvol)" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#new-password"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="confirm-password" class="form-label">Şifrə təsdiqi</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-check-double"></i></span>
                                    <input type="password" class="form-control" id="confirm-password" name="password_confirmation" placeholder="Yeni şifrəni təkrar daxil edin" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#confirm-password"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="fas fa-key me-1"></i> Şifrəni Dəyiş
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
<script>
    $('.toggle-password').on('click', function(){
        var input = $($(this).data('target'));
        var icon = $(this).find('i');
        if(input.attr('type') === 'password'){
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
</script>
@endpush
