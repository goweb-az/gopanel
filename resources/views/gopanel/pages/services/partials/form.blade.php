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
                <label class="form-label">Başlıq <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="title[{{$lang->code}}]" value="{{$item->getTranslation('title', $lang->code, true)}}">
            </div>
            <div class="mb-3">
                <label class="form-label">Qısa məlumat</label>
                <textarea class="form-control" rows="3" name="short_description[{{$lang->code}}]">{{$item->getTranslation('short_description', $lang->code, true)}}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Məlumat</label>
                <textarea class="form-control ckeditor" rows="6" name="description[{{$lang->code}}]">{{$item->getTranslation('description', $lang->code, true)}}</textarea>
            </div>
            @include("gopanel.component.meta", ['lang' => $lang->code, 'open' => false])
        </div>
        @endforeach
    </div>

    <hr>

    <div class="row">
        <div class="col-sm-12 mb-3">
            <label class="form-label">İkon tipi</label>
            <select class="form-select service-icon-type" name="icon_type">
                <option value="font" @selected($iconType === 'font')>Font ikon</option>
                <option value="image" @selected($iconType === 'image')>Şəkil</option>
            </select>
        </div>

        <div class="col-sm-12 mb-3 service-font-icon-wrap" style="{{ $iconType === 'image' ? 'display:none;' : '' }}">
            <label class="form-label">İkon</label>
            <div class="input-group">
                <button type="button" class="btn btn-outline-primary" data-icon-picker-target="#serviceIconInput" data-icon-picker-preview="#serviceIconPreview">
                    <i class="fas fa-th"></i> İkon seçin
                </button>
                <span class="input-group-text" id="serviceIconPreview" style="min-width:44px;">
                    @if($iconType === 'font' && $item->icon)
                        <i class="{{ $item->icon }}"></i>
                    @endif
                </span>
                <input type="text" class="form-control" id="serviceIconInput" name="icon" value="{{ $iconType === 'font' ? $item->icon : '' }}" placeholder="fas fa-code">
            </div>
        </div>

        <div class="col-sm-12 mb-3 service-image-icon-wrap" style="{{ $iconType === 'image' ? '' : 'display:none;' }}">
            <label class="form-label">İkon şəkli</label>
            <div class="input-group">
                <input type="file" class="form-control" name="icon_image" accept="image/*">
                <a class="btn btn-primary" href="{{ $iconType === 'image' ? ($item->icon_value ?? 'javascript:void(0)') : 'javascript:void(0)' }}" target="_blank">
                    <i class="fas fa-eye"></i> Bax
                </a>
            </div>
        </div>

        <div class="col-sm-12 mb-3">
            <label for="serviceImage" class="form-label">Xidmət şəkli</label>
            <div class="input-group">
                <input type="file" class="form-control" id="serviceImage" name="image" accept="image/*" aria-describedby="serviceImageBtn">
                <a class="btn btn-primary" href="{{$item->image_url ?? 'javascript:void(0)'}}" target="_blank" id="serviceImageBtn">
                    <i class="fas fa-eye"></i> Bax
                </a>
            </div>
        </div>
    </div>
</form>
