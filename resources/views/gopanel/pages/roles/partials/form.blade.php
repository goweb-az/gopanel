<form method="POST" action="{{$route}}" id="static-form" class="role-permission-form">

    <div class="row g-3 mb-4">
        <div class="col-lg-6 col-md-6">
            <label for="roleName" class="form-label fw-semibold">Vəzifə adı</label>
            <input type="text" class="form-control form-control-lg" id="roleName" name="name" value="{{ $item->name }}" placeholder="Mis: Menecer">
        </div>

        <div class="col-lg-6 col-md-6">
            <label for="guard" class="form-label fw-semibold">Guard Seçin</label>
            <select id="guard" class="form-select form-select-lg" name="guard_name">
                @foreach (config("auth.guards") as $key => $guard)
                    <option value="{{ $key }}" @selected(is_null($item?->id) ? $key == 'gopanel' : $item->guard_name === $key)>
                        {{ $guard['name'] ?? ucfirst($key) }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="card bg-light border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="checkAllPermissions" style="width:2.5em; height:1.25em;">
                    <label class="form-check-label fw-bold ms-1" for="checkAllPermissions">
                        Bütün icazələri seç / ləğv et
                    </label>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-pill rpf-counter" id="permissionCounter">0</span>
                    <span class="text-muted small">seçilib</span>
                    <input type="search" class="form-control form-control-sm" style="max-width: 220px" placeholder="🔍 İcazə axtar..." id="searchRole">
                </div>
            </div>
        </div>
    </div>

    {{-- Permission Groups --}}
    <div class="row g-3">
        @foreach ($permissions as $group => $items)
            @php $gid = 'perm-grp-' . $loop->index; @endphp
            <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 group-item">
                <div class="card border shadow-sm h-100 rpf-card">
                    <div class="card-header bg-white d-flex align-items-center justify-content-between py-3 border-bottom rpf-card-header">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill group-counter rpf-badge-primary" data-group="{{ $gid }}">
                                <span class="group-checked-count">0</span>/{{ $items->count() }}
                            </span>
                            <h6 class="mb-0 fw-semibold group-label" data-target="{{ $gid }}" style="cursor: pointer;">
                                {{ ucfirst($group) }}
                            </h6>
                        </div>
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input group-checkbox" data-group="{{ $gid }}" role="switch" style="width:2em; height:1em;">
                        </div>
                    </div>
                    <div class="card-body py-2">
                        @foreach ($items as $permission)
                            <div class="form-check group-item-check rpf-check-row py-1">
                                <input class="form-check-input permission-checkbox {{ $gid }}"
                                    type="checkbox"
                                    name="permissions[]"
                                    id="perm_{{ $permission->id }}"
                                    value="{{ $permission->name }}"
                                    @checked($item?->permissions->contains('name', $permission->name))>
                                <label class="form-check-label rpf-check-label text-truncate d-block" for="perm_{{ $permission->id }}" style="max-width: 100%;" title="{{ $permission->name }}">
                                    {{ $permission->title ?? $permission->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row mt-4">
        <div class="col-12 d-flex justify-content-end">
            <a href="{{ route('gopanel.admins.roles.index') }}" class="btn btn-light me-2">
                <i class="fas fa-times"></i> İmtina
            </a>
            <button type="submit" class="btn btn-primary btn-lg px-4">
                <i class="fas fa-save"></i> Yadda Saxla
            </button>
        </div>
    </div>

</form>