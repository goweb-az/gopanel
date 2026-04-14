<form action="{{$route}}" id="data-form" enctype="multipart/form-data">
    <div class="text-center mb-3">
        <img src="{{ $item->avatar_url }}" alt="Avatar" id="avatar-preview" class="rounded-circle" width="80" height="80" style="object-fit:cover; border: 3px solid #e9ecef;">
        <div class="mt-2">
            <label for="admin-image" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-camera me-1"></i> Şəkil seç
            </label>
            <input type="file" class="d-none" id="admin-image" name="image" accept="image/*">
        </div>
    </div>
    <div class="mb-3">
        <label for="admin-fullname" class="form-label">Ad soyad</label>
        <input type="text" class="form-control" id="admin-fullname" placeholder="Ad soyad yazın" name="full_name" value="{{$item->full_name}}">
    </div>
    <div class="mb-3">
        <label for="admin-email" class="form-label">Epoçt</label>
        <input type="email" class="form-control" id="admin-email" placeholder="Elektron poçt yazın" name="email" value="{{$item->email}}" @disabled(!is_null($item->id))>
    </div>

    @if(is_null($item->id))
        {{-- Create zamanı şifrə sahələri göstərilir --}}
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="admin-password" class="form-label">Şifrə</label>
                    <input type="password" class="form-control" id="admin-password" name="password" placeholder="Şifrə">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="admin-password-confirm" class="form-label">Şifrə Təkrar</label>
                    <input type="password" class="form-control" id="admin-password-confirm" name="password_confirmation" placeholder="Şifrəni doğrulayın">
                </div>
            </div>
        </div>
    @else
        {{-- Edit zamanı şifrə dəyişdirmə düyməsi --}}
        <div class="mb-3">
            <button type="button" class="btn btn-outline-warning btn-sm w-100" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                <i class="fas fa-key me-1"></i> Şifrəni dəyiş
            </button>
        </div>
    @endif

    <div class="row">
        <div class="col-12 mb-3">
            <label for="admin-role" class="form-label">Vəzifə</label>
            <select id="admin-role" class="form-select select2" name="role">
                <option value="">Vəzifə seçin</option>
                @foreach ($roles as $role)
                    <option @selected($item->hasRole($role->name)) value="{{$role->id}}">
                        {{$role->name}}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12 mb-3">
            <label for="admin-super" class="form-label">Super Admin</label>
            <select id="admin-super" class="form-select" name="is_super">
                <option value="0" @selected(is_null($item->id) || $item->is_super == 0)>Xeyr</option>
                <option value="1" @selected(!is_null($item->id) && $item->is_super == 1)>Bəli</option>
            </select>
        </div>

        <div class="col-12">
            <label for="admin-status" class="form-label">Status</label>
            <select id="admin-status" class="form-select" name="is_active">
                <option value="1" @selected(is_null($item->id) || $item->is_active == 1)>Aktiv</option>
                <option value="0" @selected(!is_null($item->id) && $item->is_active == 0)>Deaktiv</option>
            </select>
        </div>
    </div>
</form>
<script>
    $('#admin-image').off('change').on('change', function(){
        var file = this.files[0];
        if(file){
            var reader = new FileReader();
            reader.onload = function(e){ $('#avatar-preview').attr('src', e.target.result); };
            reader.readAsDataURL(file);
        }
    });
</script>