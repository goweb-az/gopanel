<form action="{{$route}}" id="data-form">
    

    <div class="row">

        <div class="col-sm-12 mb-3">                        
            <label for="formrow-firstname-input" class="form-label">Ad</label>
            <input type="text" class="form-control" id="formrow-firstname-input" placeholder="Adı daxil edin" name="name" value="{{$item->name}}">
        </div>

        <div class="col-sm-12 mb-3">                        
            <label for="formrow-url-input" class="form-label">Link</label>
            <input type="url" class="form-control" id="formrow-url-input" placeholder="facebook.az/proveb.az" name="url" value="{{$item->url}}">
        </div>

        <div class="col-12 mb-2">
            <label for="targetSelect" class="form-label">Link yeni səhifədə açılsın ?</label>
            <select id="targetSelect" class="form-select" name="target_blank">
                <option value="1" @selected(!is_null($item?->id) && $item->target_blank == 1)>Bəli</option>
                <option value="0" @selected(!is_null($item?->id) && $item->target_blank == 0)>Xeyr</option>
            </select>
        </div>

        <div class="col-12 mb-2">
            <label for="activeSelect" class="form-label">Status</label>
            <select id="activeSelect" class="form-select" name="is_active">
                <option value="1" @selected(!is_null($item?->id) && $item->is_active == 1)>Aktiv</option>
                <option value="0" @selected(!is_null($item?->id) && $item->is_active == 0)>Deaktiv</option>
            </select>
        </div>

        <div class="col-12 mb-2">
            <label for="iconTypeSelect" class="form-label">İkon tipi</label>
            <select id="iconTypeSelect" class="form-select" name="icon_type">
                @foreach ($types as $type)
                    <option value="{{$type->value}}" @selected(isset($item->id) && $item->icon_type == $type->value)>{{$type->label()}}</option>
                @endforeach
            </select>
        </div>

        <div class="col-12">
            <label for="ikone" class="form-label">İkon</label>

            @php
                $iconType = $item->icon_type ?? null;
            @endphp

            <div class="file-upload" style="{{ $iconType === 'image' ? '' : 'display:none;' }}">
                <input type="file" class="form-control" id="uploadIcon" name="image" aria-describedby="uploadIcon" aria-label="İkon seçin">
            </div>

            <div class="type-icone" style="{{ $iconType === 'image' ? 'display:none;' : '' }}">
                <div class="input-group mb-2" id="fontIconPickerGroup" style="{{ $iconType === 'font' ? '' : 'display:none;' }}">
                    <button type="button" class="btn btn-outline-primary" id="openIconPickerBtn">
                        <i class="fas fa-th"></i> Seç
                    </button>
                    <span class="input-group-text" id="iconPreviewBox" style="min-width:40px; font-size:18px;">
                        @if($iconType === 'font' && $item->icon)
                            {!! $item->icon !!}
                        @endif
                    </span>
                </div>
                <textarea name="icon" id="iconTextarea" class="form-control" rows="4">{{ $item->icon ?? '' }}</textarea>
            </div>
        </div>

    </div>
</form>