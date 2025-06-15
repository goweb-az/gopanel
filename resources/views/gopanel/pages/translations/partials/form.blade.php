<form method="POST" action="{{$route}}" id="data-form">
    <ul class="nav nav-tabs" role="tablist">
        @foreach ($languages as $lang)
        <li class="nav-item">
            <a class="nav-link {{$loop->first ? 'active' : ''}}" data-bs-toggle="tab" href="#name_{{$lang->code}}" role="tab">
                <span class="">{{$lang->upper_code}}</span>    
            </a>
        </li>
        @endforeach
    </ul>

    <!-- Tab panes -->
    <div class="tab-content tab-p text-muted">

        <div class="col-12 mb-2">
            <label for="formrow-firstname-input" class="form-label">Açar</label>
            <input type="text" class="form-control" id="formrow-firstname-input" name="key" value="{{$item->key}}" @disabled(isset($item->id)) >
        </div>

        @foreach ($languages as $lang)
        <div class="tab-pane {{$loop->first ? 'active' : ''}}" id="name_{{$lang->code}}" role="tabpanel">
            <div class="mb-3">
                <label for="formrow-value-input" class="form-label">Dəyər</label>
                <textarea name="value[{{$lang->code}}]" id="formrow-value-input" class="form-control">{{ $item->getValue($lang->code) }}</textarea>
            </div>
        </div>
        @endforeach
    </div>


    <div class="row">


        <div class="col-12 mb-2">
            <label for="formrow-group-input" class="form-label">Tərcümə faylı</label>
            <select name="group" class="form-control" @disabled(isset($item->id))>
                @foreach ($groups as $group)
                    @php 
                        $isSelected = isset($item->group) && $item->group == $group->value;
                        if ($isSelected) $selectedExists = true;
                    @endphp
                    <option value="{{ $group->value }}" @selected($isSelected)>
                        {{ $group->getLabel() }}
                    </option>
                @endforeach

                <option value="{{ isset($item->id) ? $item->platform : '' }}" @selected(!$selectedExists && isset($item->id))>
                    Platforma ilə eyni
                </option>
            </select>

        </div>

        <div class="col-12">
            <label for="platform" class="form-label">Platfroma</label>
            <select id="platform" class="form-select" name="platform" @disabled(isset($item->id))>
                @foreach ($platforms as $platform)
                    <option value="{{$platform->value}}" @selected(isset($item->id) && $item->status == $platform->value)>{{$platform->value}}</option>
                @endforeach
            </select>
        </div>
    </div>
    


</form>