<form method="POST" action="{{$route}}" id="static-form" enctype="multipart/form-data">

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
                <input type="text" class="form-control" name="title[{{$lang->code}}]" value="{{$item->getTranslation('title', $lang->code, true)}}" placeholder="Məhsul adı">
            </div>
            <div class="mb-3">
                <label class="form-label">Slug</label>
                <input type="text" class="form-control" name="slug[{{$lang->code}}]" value="{{$item->getTranslation('slug', $lang->code, true)}}" placeholder="boş buraxsanız başlıqdan avtomatik yaradılacaq">
            </div>
            <div class="mb-3">
                <label class="form-label">Qısa açıqlama</label>
                <textarea class="form-control" rows="3" name="short_description[{{$lang->code}}]" placeholder="Qısa məlumat">{{$item->getTranslation('short_description', $lang->code, true)}}</textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Məzmun</label>
                <textarea class="form-control ckeditor" rows="6" name="description[{{$lang->code}}]">{{$item->getTranslation('description', $lang->code, true)}}</textarea>
            </div>
            @include("gopanel.component.meta", ['lang' => $lang->code, 'open' => true])
        </div>
        @endforeach
    </div>

    <hr>

    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Qiymət <span class="text-danger">*</span></label>
            <div class="input-group">
                <input type="number" step="0.01" min="0" class="form-control" name="price" value="{{ old('price', $item->price ?? 0) }}" placeholder="0.00">
                <span class="input-group-text">₼</span>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Endirimli qiymət</label>
            <div class="input-group">
                <input type="number" step="0.01" min="0" class="form-control" name="discount" value="{{ old('discount', $item->discount) }}" placeholder="—">
                <span class="input-group-text">₼</span>
            </div>
        </div>

        <div class="col-md-4 mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="is_active">
                <option value="1" @selected(is_null($item?->id) || $item->is_active == 1)>Aktiv</option>
                <option value="0" @selected(!is_null($item?->id) && $item->is_active == 0)>Deaktiv</option>
            </select>
        </div>

        <div class="col-12 mb-3">
            <label for="productImage" class="form-label">Şəkil <small class="text-danger">800x800</small></label>
            <input type="file" class="form-control" id="productImage" name="image" accept="image/*">
            @if(!is_null($item->id) && !empty($item->image_url))
                <div class="mt-2">
                    <a href="{{ $item->image_url }}" target="_blank">
                        <img src="{{ $item->image_url }}" alt="{{ $item->title ?? 'Product' }}" style="width:120px;height:120px;object-fit:cover;border:1px solid #e3e8f0;border-radius:6px;">
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-12 text-right mt-3">
            <button type="submit" class="btn btn-primary pull-right">
                <i class="fas fa-save"></i> Yadda Saxla
            </button>
        </div>
    </div>

</form>
