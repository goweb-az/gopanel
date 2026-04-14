<form method="POST" action="{{$route}}" id="static-form">


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
                <label for="formrow-slug-input" class="form-label">Slug</label>
                <input type="text" class="form-control" id="formrow-slug-input" name="slug[{{$lang->code}}]" value="{{$item->getTranslation('slug', $lang->code, true)}}">
            </div>
            <div class="mb-3">
                <label class="form-label">Alt Başlıq</label>
                <textarea class="form-control" rows="3" name="description[{{$lang->code}}]">{{$item->getTranslation('description', $lang->code, true)}}</textarea>
            </div>
            @include("gopanel.component.meta", ['lang' => $lang->code])
        </div>
        @endforeach
    </div>

    <div class="row">


        <div class="col-12 mb-2">
            <label for="route_name" class="form-label">Route</label>
            <input type="text" class="form-control" id="route_name" name="route_name" value="{{ $item->route_name}}">
        </div>

        <div class="col-md-4 col-sm-12 col-xs-12">
            <label for="statusSelect" class="form-label">Menyu tipi</label>
            <select id="statusSelect" class="form-select" name="type">
                @foreach ($types as $type)
                    <option value="{{$type->value}}" @selected(isset($item->id) && $item->type == $type->value) >{{$type->label()}}</option>
                @endforeach
            </select>
        </div>


        <div class="col-md-4 col-sm-12 col-xs-12">
            <label for="statusSelect" class="form-label">Menyu Mövqeyi</label>
            <select id="statusSelect" class="form-select" name="position">
                @foreach ($positions as $position)
                    <option value="{{$position->value}}" @selected(isset($item->id) && $item->position == $position->value) >{{$position->label()}}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 col-sm-12 col-xs-12">
            <label for="statusSelect" class="form-label">Status</label>
            <select id="statusSelect" class="form-select" name="is_active">
                <option value="1" @selected(!is_null($item?->id) && $item->is_active == 1)>Aktiv</option>
                <option value="0" @selected(!is_null($item?->id) && $item->is_active == 0)>Deaktiv</option>
            </select>
        </div>

        @if ($parent_id > 0)
            <input type="hidden" name="parent_id" value="{{$parent_id}}">
        @endif

    </div>


    <div class="row">
        <div class="col-12 text-right mt-3">
            <button type="submit" class="btn btn-primary pull-right">
                <i class="fas fa-save"></i> Yadda Saxla
            </button>
        </div>
    </div>

</form>