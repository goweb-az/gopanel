<form action="{{$route}}" id="data-form">
    <ul class="nav nav-tabs" role="tablist">
        @foreach ($languages as $lang)
        <li class="nav-item">
            <a class="nav-link {{$loop->first ? 'active' : ''}}" data-bs-toggle="tab" href="#lang_key_{{$lang->code}}" role="tab">
                <span class="">{{$lang->upper_code}}</span>    
            </a>
        </li>
        @endforeach
    </ul>

    <!-- Tab panes -->
    <div class="tab-content tab-p text-muted">
        @foreach ($languages as $lang)
        <div class="tab-pane {{$loop->first ? 'active' : ''}}" id="lang_key_{{$lang->code}}" role="tabpanel">
            <div class="mb-3">
                <label for="formrow-title-input" class="form-label">Başlıq</label>
                <input type="text" class="form-control" id="formrow-title-input" name="title[{{$lang->code}}]" value="{{$item->getTranslation('title', $lang->code, true)}}">
            </div>
            <div class="mb-3">
                <label class="form-label">Məlumat</label>
                <div>
                    <textarea class="form-control ckeditor" rows="6" name="description[{{$lang->code}}]">{{$item->getTranslation('description', $lang->code, true)}}</textarea>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row">

        <div class="col-sm-12 mb-3">                        
            <div class="mt-3">
                <label for="formFile" class="form-label">Şəkili <small class="text-danger">800x520</small></label>
                <div class="input-group">
                    <input type="file" class="form-control" id="İntroCover" name="image" aria-describedby="İntroCoverBtn" aria-label="Upload">
                    <a class="btn btn-primary" type="button" href="{{$item->image_url ?? 'javascript:void()'}}" target="_blank" id="İntroCoverBtn">
                        <i class="fas fa-eye"></i> Bax
                    </a>
                </div>
            </div>
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