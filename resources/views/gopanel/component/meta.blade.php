<fieldset class="border p-2 mb-3">
    <legend class="w-auto px-2">Meta bilgilər</legend>
    <div class="mb-3">
        <label for="formrow-title-input" class="form-label">Meta Başlıq</label>
        <input type="text" class="form-control" id="formrow-title-input" name="meta[title][{{$lang}}]" value="{{$item->getMeta('title', $lang)}}">
    </div>
    <div class="mb-3">
        <label class="form-label">Meta Məlumat <small>max 300 simvol</small></label>
        <div>
            <textarea class="form-control" rows="6" name="meta[description][{{$lang}}]">{{$item->getMeta('description', $lang)}}</textarea>
        </div>
    </div>
    <div class="mb-3">
        <label for="formrow-keywords-input" class="form-label">Meta Açar sözlər</label>
        <input type="text" class="form-control tags" id="formrow-keywords-input" name="meta[keywords][{{$lang}}]" value="{{$item->getMeta('keywords', $lang)}}">
    </div>
    <div class="mb-3">
        <label for="formFile" class="form-label">Şəkili <small class="text-danger">600x600</small> {{$item->meta_image_url}}</label>
        <div class="input-group">
            <input type="file" class="form-control" id="metaImage_{{$lang}}" name="meta[image][{{$lang}}]" aria-describedby="metImage{{$lang}}" aria-label="Upload">
            <a class="btn btn-primary" type="button" href="{{$item->getMetaImage($lang) ?? 'javascript:void()'}}" target="_blank" id="metImage{{$lang}}">
                <i class="fas fa-eye"></i> Bax
            </a>
        </div>
    </div>
</fieldset>