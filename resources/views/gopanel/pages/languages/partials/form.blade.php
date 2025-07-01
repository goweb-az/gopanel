<form action="{{$route}}" id="data-form">
    

    <div class="row">

        <div class="col-sm-12 mb-3">                        
            <label for="formrow-firstname-input" class="form-label">Ad</label>
            <input type="text" class="form-control" id="formrow-firstname-input" placeholder="Adı daxil edin" name="name" value="{{$item->name}}">
        </div>

        <div class="col-sm-12 mb-3">                        
            <label for="formrow-code-input" class="form-label">Kod</label>
            <input type="text" class="form-control" id="formrow-code-input" placeholder="Kod daxil edin. mis : az" name="code" value="{{$item->code}}">
        </div>

        <div class="col-12 mb-3">
            <label for="country" class="form-label">Ölkə seçin</label>
            <select id="country" class="form-select select2" name="country_id">
                @foreach ($countries as $country)
                    <option value="{{$country->id}}" @selected(!is_null($item?->id) && $item->country_id == $country->id)>
                        {{$country->name}}
                    </option>
                @endforeach
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