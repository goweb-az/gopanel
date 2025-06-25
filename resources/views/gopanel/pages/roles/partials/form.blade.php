<form method="POST" action="{{$route}}" id="static-form">


    <div class="row">
        <div class="col-12 mb-2">
            <label for="roleName" class="form-label">Vəzifə adı</label>
            <input type="text" class="form-control" id="roleName" name="name" value="{{ $item->name }}" placeholder="Mis: Menecer">
        </div>

        <div class="col-12">
            <label for="guard" class="form-label">Guard Seçin</label>
            <select id="guard" class="form-select" name="guard_name">
                @foreach (config("auth.guards") as $key => $guard)
                    <option value="{{ $key }}" @selected(is_null($item?->id) ? $key == 'gopanel' : $item->guard_name === $key)>
                        {{ $guard['name'] ?? ucfirst($key) }}
                    </option>
                @endforeach
            </select>
        </div>

    </div>
    

    <div class="row mb-3 mt-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="checkAllPermissions">
                <label class="form-check-label fw-bold" for="checkAllPermissions">
                    Bütün icazələri seç / ləğv et
                </label>
            </div>
            <div>
                <input type="search" class="form-control" style="max-width: 250px" placeholder="Axtar" id="searchRole">
            </div>
        </div>
    </div>

    <div class="row mt-3">
        @foreach ($permissions as $group => $items)
            <div class="col-lg-4 col-md-6 col-sm-12 col-xs-12 mb-4 group-item">
                <fieldset class="border p-3 mb-3 rounded">
                    <legend class="w-auto px-2 mb-2 d-flex align-items-center justify-content-between gap-2">
                        <span class="group-label" data-target="{{ Str::slug($group) }}" style="cursor: pointer;">
                            {{ ucfirst($group) }}
                        </span>
                        <input type="checkbox" class="form-check-input group-checkbox" data-group="{{ Str::slug($group) }}">
                    </legend>

                    @foreach ($items as $permission)
                        <div class="form-check group-item-check">
                            <input class="form-check-input permission-checkbox {{ Str::slug($group) }}"
                                type="checkbox"
                                name="permissions[]"
                                id="perm_{{ $permission->id }}"
                                value="{{ $permission->name }}"
                                @checked($item?->permissions->contains('name', $permission->name))>
                            <label class="form-check-label" for="perm_{{ $permission->id }}">
                                {{ $permission->title ?? $permission->name }}
                            </label>
                        </div>
                    @endforeach
                </fieldset>
            </div>
        @endforeach
    </div>


    <div class="row">
        <div class="col-12 text-right mt-3">
            <button type="submit" class="btn btn-primary pull-right">
                <i class="fas fa-save"></i> Yadda Saxla
            </button>
        </div>
    </div>

</form>