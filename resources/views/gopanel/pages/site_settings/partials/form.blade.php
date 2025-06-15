<form method="POST" action="{{route("gopanel.site-settings.save.form", $item)}}" id="static-form">


    <div class="row">

        <div class="col-6">
            <label for="statusSelect" class="form-label">Sayt Statusu</label>
            <select id="statusSelect" class="form-select" name="site_status">
                <option value="1" @selected(isset($item->id) && $item->site_status == 1)>Açıq</option>
                <option value="0" @selected(isset($item->id) && $item->site_status == 0)>Bağlı</option>
            </select>
        </div>

        <div class="col-6">
            <label for="statusSelect" class="form-label">Login Statusu</label>
            <select id="statusSelect" class="form-select" name="login_status">
                <option value="1" @selected(isset($item->id) && $item->login_status == 1)>Açıq</option>
                <option value="0" @selected(isset($item->id) && $item->login_status == 0)>Bağlı</option>
            </select>
        </div>

        <div class="col-6">
            <label for="statusSelect" class="form-label">Qeydiyyat Statusu</label>
            <select id="statusSelect" class="form-select" name="register_status">
                <option value="1" @selected(isset($item->id) && $item->register_status == 1)>Açıq</option>
                <option value="0" @selected(isset($item->id) && $item->register_status == 0)>Bağlı</option>
            </select>
        </div>

        <div class="col-6">
            <label for="statusSelect" class="form-label">Saytdan ödəniş statusu</label>
            <select id="statusSelect" class="form-select" name="payment_status">
                <option value="1" @selected(isset($item->id) && $item->payment_status == 1)>Açıq</option>
                <option value="0" @selected(isset($item->id) && $item->payment_status == 0)>Bağlı</option>
            </select>
        </div>

        <div class="col-sm-6">                        
            <div class="mt-3">
                <label for="formFile" class="form-label">Logo White <small class="text-danger">152x48</small></label>
                <div class="input-group">
                    <input type="file" class="form-control" id="logo_light" name="logo_light" aria-describedby="logo_lightBtn" aria-label="Upload">
                    <a class="btn btn-primary" type="button" href="{{$item->logo_light_url ?? 'javascript:void()'}}" target="_blank" id="logo_lightBtn">
                        <i class="fas fa-eye"></i> Bax
                    </a>
                </div>
            </div>
        </div>
        <div class="col-sm-6">                        
            <div class="mt-3">
                <label for="formFile" class="form-label">Logo Dark <small class="text-danger">152x48</small></label>
                <div class="input-group">
                    <input type="file" class="form-control" id="logo_dark" name="logo_dark" aria-describedby="logo_darkBtn" aria-label="Upload">
                    <a class="btn btn-primary" type="button" href="{{$item->logo_dark_url ?? 'javascript:void()'}}" target="_blank" id="logo_darkBtn">
                        <i class="fas fa-eye"></i> Bax
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="row mt-4">
        <div class="col-12">
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
                    @include("gopanel.component.meta", ['lang' => $lang->code])
                </div>
                @endforeach
            </div>
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