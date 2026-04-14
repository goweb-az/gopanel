<form method="POST" action="{{route("gopanel.contact.contact-info.save.form", $item)}}" id="static-form">

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
                <label for="formrow-title-input" class="form-label">Səhifə Başlıq</label>
                <input type="text" class="form-control" id="formrow-title-input" name="page_title[{{$lang->code}}]" value="{{$item->getTranslation('page_title', $lang->code, true)}}">
            </div>
            <div class="mb-3">
                <label class="form-label">Səhifə Məlumat</label>
                <div>
                    <textarea class="form-control" rows="6" name="page_description[{{$lang->code}}]">{{$item->getTranslation('page_description', $lang->code, true)}}</textarea>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Ünvan</label>
                <div>
                    <textarea class="form-control" rows="6" name="adress[{{$lang->code}}]">{{$item->getTranslation('adress', $lang->code, true)}}</textarea>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    
    <div class="row">
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 mb-2">
            <label for="phone" class="form-label">Ofis telefon</label>
            <input type="text" class="form-control" id="phone" name="phone" value="{{ $item->phone }}">
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 mb-2">
            <label for="mobile" class="form-label">Mobil telefon</label>
            <input type="text" class="form-control" id="mobile" name="mobile" value="{{ $item->mobile }}">
        </div>
        <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 mb-2">
            <label for="whatsapp" class="form-label">Vatsap nömərsi (Ofis)</label>
            <input type="text" class="form-control" id="whatsapp" name="whatsapp" value="{{ $item->whatsapp }}">
        </div>
        <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 mb-2">
            <label for="sales_whatsapp" class="form-label">Vatsap nömərsi (Satış)</label>
            <input type="text" class="form-control" id="sales_whatsapp" name="sales_whatsapp" value="{{ $item->sales_whatsapp }}">
        </div>
        <div class="col-lg-4 col-md-4 col-sm-12 col-xs-12 mb-2">
            <label for="support_whatsapp" class="form-label">Vatsap nömərsi (Texniki dsətək)</label>
            <input type="text" class="form-control" id="support_whatsapp" name="support_whatsapp" value="{{ $item->support_whatsapp }}">
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 mb-2">
            <label for="info_email" class="form-label">Məlumat E-poçt (info mail)</label>
            <input type="text" class="form-control" id="info_email" name="info_email" value="{{ $item->info_email }}">
        </div>
        <div class="col-lg-6 col-md-6 col-sm-12 col-xs-12 mb-2">
            <label for="support_email" class="form-label">Dəstək E-poçt</label>
            <input type="text" class="form-control" id="support_email" name="support_email" value="{{ $item->support_email }}">
        </div>
        <div class="col-12 mb-3">
            <label class="form-label">Xəritə</label>
            <textarea class="form-control" rows="6" name="map">{{$item->map}}</textarea>
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