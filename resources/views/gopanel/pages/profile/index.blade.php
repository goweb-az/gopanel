@extends('gopanel.layouts.main')
@section('content')
<div class="page-content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                    <h4 class="mb-sm-0 font-size-18">Profil</h4>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Sol panel — Avatar + Info --}}
            <div class="col-xl-4">
                <div class="card overflow-hidden">
                    <div class="bg-primary bg-soft">
                        <div class="row">
                            <div class="col-7">
                                <div class="text-primary p-4">
                                    <h5 class="text-primary">Xoş gəldiniz!</h5>
                                    <p>Profil məlumatlarınız</p>
                                </div>
                            </div>
                            <div class="col-5 align-self-end">
                                <img src="{{ asset('assets/gopanel/images/profile-img.png') }}" alt="" class="img-fluid" onerror="this.style.display='none'">
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="text-center mt-n4">
                                    <img src="{{ $item->avatar_url }}" alt="Avatar"
                                         class="rounded-circle img-thumbnail" id="profile-avatar-preview"
                                         width="100" height="100"
                                         style="object-fit:cover; border: 3px solid #fff; box-shadow: 0 2px 10px rgba(0,0,0,.15);">
                                </div>
                                <div class="text-center mt-3">
                                    <h5 class="font-size-16 mb-1">{{ $item->full_name }}</h5>
                                    <p class="text-muted mb-2">{{ $item->email }}</p>
                                    @if($item->roles->count())
                                        <span class="badge bg-primary rounded-pill px-3 py-1">{{ $item->roles->first()->name }}</span>
                                    @endif
                                    @if($item->is_super)
                                        <span class="badge bg-danger rounded-pill px-3 py-1 ms-1">Super Admin</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="{{ route('gopanel.profile.change-password.index') }}" class="btn btn-warning w-100">
                                <i class="fas fa-key me-1"></i> Şifrəni Dəyiş
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sağ panel — Profil formu --}}
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-edit me-2 text-primary"></i>Profil Məlumatları
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('gopanel.profile.update') }}" id="static-form" enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col-md-12 mb-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $item->avatar_url }}" alt="Avatar" id="profile-form-preview"
                                             class="rounded-circle" width="64" height="64" style="object-fit:cover; border: 2px solid #e9ecef;">
                                        <div>
                                            <label for="profile-image" class="btn btn-outline-primary btn-sm mb-0">
                                                <i class="fas fa-camera me-1"></i> Şəkil dəyiş
                                            </label>
                                            <input type="file" class="d-none" id="profile-image" name="image" accept="image/*">
                                            <p class="text-muted small mb-0 mt-1">JPG, PNG, WebP — max 2MB</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="profile-fullname" class="form-label">Ad soyad</label>
                                    <input type="text" class="form-control" id="profile-fullname" name="full_name" value="{{ $item->full_name }}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="profile-email" class="form-label">E-poçt</label>
                                    <input type="email" class="form-control" id="profile-email" name="email" value="{{ $item->email }}" required>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="fas fa-save me-1"></i> Yadda Saxla
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
    $('#profile-image').on('change', function(){
        var file = this.files[0];
        if(file){
            var reader = new FileReader();
            reader.onload = function(e){
                $('#profile-avatar-preview, #profile-form-preview').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });
</script>
@endpush
