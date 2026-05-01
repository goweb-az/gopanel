@php
    $metaOpen = $open ?? true;
    $metaId = 'metaCollapse_' . $lang . '_' . uniqid();
@endphp

<fieldset class="border p-2 mb-3 meta-fieldset">
    <legend class="w-auto px-2 mb-0">
        <button
            type="button"
            class="btn btn-sm btn-light meta-collapse-toggle"
            data-bs-toggle="collapse"
            data-bs-target="#{{$metaId}}"
            aria-expanded="{{$metaOpen ? 'true' : 'false'}}"
            aria-controls="{{$metaId}}"
            data-open-text="Meta bilgiləri bağla"
            data-closed-text="Meta bilgiləri aç"
        >
            <i class="fas {{$metaOpen ? 'fa-chevron-up' : 'fa-chevron-down'}} me-1"></i>
            <span>{{$metaOpen ? 'Meta bilgiləri bağla' : 'Meta bilgiləri aç'}}</span>
        </button>
    </legend>

    <div id="{{$metaId}}" class="collapse meta-collapse {{$metaOpen ? 'show' : ''}}">
        <div class="mb-3">
            <label for="metaTitle_{{$metaId}}" class="form-label">Meta başlıq</label>
            <input type="text" class="form-control" id="metaTitle_{{$metaId}}" name="meta[title][{{$lang}}]" value="{{$item->getMeta('title', $lang)}}">
        </div>
        <div class="mb-3">
            <label class="form-label">Meta məlumat <small>max 300 simvol</small></label>
            <textarea class="form-control" rows="6" name="meta[description][{{$lang}}]">{{$item->getMeta('description', $lang)}}</textarea>
        </div>
        <div class="mb-3">
            <label for="metaKeywords_{{$metaId}}" class="form-label">Meta açar sözlər</label>
            <input type="text" class="form-control tags" id="metaKeywords_{{$metaId}}" name="meta[keywords][{{$lang}}]" value="{{$item->getMeta('keywords', $lang)}}">
        </div>
        <div class="mb-3">
            <label for="metaImage_{{$metaId}}" class="form-label">Şəkil <small class="text-danger">600x600</small></label>
            <div class="input-group">
                <input type="file" class="form-control" id="metaImage_{{$metaId}}" name="meta[image][{{$lang}}]" aria-describedby="metaImageBtn_{{$metaId}}" aria-label="Upload">
                <a class="btn btn-primary" type="button" href="{{$item->getMetaImage($lang) ?? 'javascript:void(0)'}}" target="_blank" id="metaImageBtn_{{$metaId}}">
                    <i class="fas fa-eye"></i> Bax
                </a>
            </div>
        </div>
    </div>
</fieldset>
