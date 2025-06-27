<form action="{{$route}}" id="data-form">
    <div class="mb-3">
        <label for="formrow-firstname-input" class="form-label">Ad soyad</label>
        <input type="text" class="form-control" id="formrow-firstname-input" placeholder="Ad soyad yazın" name="full_name" value="{{$item->full_name}}">
    </div>
    <div class="mb-3">
        <label for="formrow-firstname-input" class="form-label">Epoçt</label>
        <input type="text" class="form-control" id="formrow-firstname-input" placeholder="Elektron poçt yazın" name="email" value="{{$item->email}}" @disabled(!is_null($item->id))>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="formrow-email-input" class="form-label">Şifrə</label>
                <input type="email" class="form-control" id="formrow-email-input" name="password" placeholder="Şifrə">
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label for="formrow-password-input" class="form-label">Şifrə Təkrar</label>
                <input type="password" class="form-control" id="formrow-password-input" name="password_confirmation" placeholder="Şifrəni doğrulayın">
            </div>
        </div>

        <div class="col-12 mb-3">
            <label for="country" class="form-label">Vəzfiə</label>
            <select id="country" class="form-select select2" name="role">
                <option value="">Vəzifə seçin</option>
                @foreach ($roles as $role)
                    <option @selected($item->hasRole($role->name)) value="{{$role->id}}">
                        {{$role->name}}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-12 mb-3">
            <label for="statusSelect" class="form-label">Super Admin</label>
            <select id="statusSelect" class="form-select" name="is_super">
                <option value="0" @selected(!is_null($item?->id) && $item->is_super == 0)>Bəli</option>
                <option value="1" @selected(!is_null($item?->id) && $item->is_super == 1)>Xeyr</option>
            </select>
        </div>

        <div class="col-12">
            <label for="statusSelect" class="form-label">Status</label>
            <select id="statusSelect" class="form-select" name="is_active">
                <option value="1" @selected(!is_null($item?->id) && $item->is_active == 1)>Aktiv</option>
                <option value="0" @selected(!is_null($item?->id) && $item->is_active == 0)>Deaktiv</option>
            </select>
        </div>
    </div>
</form>