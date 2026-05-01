@php
    $iconType = $item->icon_type?->value ?? $item->icon_type ?? 'font';
@endphp

<form action="{{$route}}" id="data-form" enctype="multipart/form-data">
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
            <div class="mb-3">
                <label class="form-label">Ad <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name[{{$lang->code}}]" value="{{$item->getTranslation('name', $lang->code, true)}}" placeholder="Kateqoriya adı">
            </div>
            <div class="mb-3">
                <label class="form-label">Açıqlama</label>
                <textarea class="form-control" rows="3" name="description[{{$lang->code}}]" placeholder="Qısa açıqlama">{{$item->getTranslation('description', $lang->code, true)}}</textarea>
            </div>
            @include("gopanel.component.meta", ['lang' => $lang->code, 'open' => false])
        </div>
        @endforeach
    </div>

    <hr>

    <div class="row">
        <div class="col-sm-6 mb-3">
            <label class="form-label">İkon tipi</label>
            <select class="form-select category-icon-type" name="icon_type">
                <option value="font" @selected($iconType === 'font')>Font ikon</option>
                <option value="image" @selected($iconType === 'image')>Şəkil</option>
            </select>
        </div>

        <div class="col-sm-6 mb-3">
            <label class="form-label">Rəng</label>
            <input type="color" class="form-control form-control-color w-100" name="color" value="{{$item->color ?? '#6c757d'}}">
        </div>

        <div class="col-12 mb-3 category-font-icon-wrap" style="{{ $iconType === 'image' ? 'display:none;' : '' }}">
            <label class="form-label">İkon</label>
            <div class="input-group">
                <span class="input-group-text" id="categoryIconPreview" style="min-width:44px; justify-content:center;">
                    @if($iconType === 'font' && $item->icon)
                        <i class="{{ $item->icon }}"></i>
                    @else
                        <i class="fas fa-image text-muted"></i>
                    @endif
                </span>
                <input type="text" class="form-control" id="categoryIconInput" name="icon" value="{{ $iconType === 'font' ? $item->icon : '' }}" placeholder="fas fa-folder">
                <button type="button" class="btn btn-outline-primary" data-icon-picker-target="#categoryIconInput" data-icon-picker-preview="#categoryIconPreview">
                    <i class="fas fa-th"></i> İkon seçin
                </button>
            </div>
        </div>

        <div class="col-12 mb-3 category-image-icon-wrap" style="{{ $iconType === 'image' ? '' : 'display:none;' }}">
            <label class="form-label">İkon şəkli</label>
            <div class="input-group">
                <input type="file" class="form-control" name="icon_image" accept="image/*">
                <a class="btn btn-primary" href="{{ $iconType === 'image' ? ($item->icon_value ?? 'javascript:void(0)') : 'javascript:void(0)' }}" target="_blank">
                    <i class="fas fa-eye"></i> Bax
                </a>
            </div>
        </div>

        <div class="col-sm-6 mb-3">
            <label class="form-label">Üst kateqoriya</label>
            <select class="form-select" name="parent_id">
                <option value="">— Əsas kateqoriya —</option>
                @foreach($parents as $parent)
                    @if($parent->id !== $item->id)
                    <option value="{{$parent->id}}" @selected($item->parent_id == $parent->id)>
                        {{ $parent->getTranslation('name', app()->getLocale(), true) ?? $parent->id }}
                    </option>
                    @endif
                @endforeach
            </select>
        </div>

        <div class="col-sm-6 mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="is_active">
                <option value="1" @selected(is_null($item?->id) || $item->is_active == 1)>Aktiv</option>
                <option value="0" @selected(!is_null($item?->id) && $item->is_active == 0)>Deaktiv</option>
            </select>
        </div>

        <div class="col-sm-6 mb-3">
            <label class="form-label">Ana səhifədə göstər</label>
            <select class="form-select" name="show_in_home">
                <option value="1" @selected(!is_null($item?->id) && $item->show_in_home == 1)>Bəli</option>
                <option value="0" @selected(is_null($item?->id) || $item->show_in_home == 0)>Xeyr</option>
            </select>
        </div>

        <div class="col-sm-6 mb-3">
            <label class="form-label">Menyuda göstər</label>
            <select class="form-select" name="show_in_menu">
                <option value="1" @selected(is_null($item?->id) || $item->show_in_menu == 1)>Bəli</option>
                <option value="0" @selected(!is_null($item?->id) && $item->show_in_menu == 0)>Xeyr</option>
            </select>
        </div>

        <div class="col-sm-6 mb-3">
            <label class="form-label">Ana səhifə sırası</label>
            <input type="number" class="form-control" name="home_order" value="{{$item->home_order ?? 0}}" min="0">
        </div>
    </div>
</form>
