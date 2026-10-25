{{--
    Lookup forması - modalın içinə AJAX ilə gəlir (crud.js → #form-wrap).
    Dəyişənlər: $definition, $item, $route, $switches; $languages bütün view-lara
    ViewServiceProvider-dən paylanır (aktiv dillər, sıralı).
--}}
<form action="{{ $route }}" id="data-form" class="lookup-form">
    <div class="row">
        <div class="col-12 mb-3">
            <label for="lookup-key" class="form-label">Açar <span class="text-danger">*</span></label>
            <input type="text" id="lookup-key" class="form-control" name="key" value="{{ $item->key }}" placeholder="pending_review" maxlength="50" autocomplete="off">
            <div class="form-text">Texniki açardır (kiçik hərf, rəqəm, «_»). Kodda istifadə olunmağa başladıqdan sonra dəyişdirilməməlidir.</div>
        </div>

        @if($definition->translated !== [])
        <div class="col-12 mb-3">
            <ul class="nav nav-tabs" role="tablist">
                @foreach ($languages as $language)
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#lookup-lang-{{ $language->code }}" type="button" role="tab">
                        {{ strtoupper($language->code) }}
                    </button>
                </li>
                @endforeach
            </ul>
            <div class="tab-content border border-top-0 p-3">
                @foreach ($languages as $language)
                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="lookup-lang-{{ $language->code }}" role="tabpanel">
                    @if($definition->isTranslated('name'))
                    <div class="mb-3">
                        <label class="form-label" for="lookup-name-{{ $language->code }}">Ad</label>
                        <input type="text" id="lookup-name-{{ $language->code }}" class="form-control" name="name[{{ $language->code }}]" value="{{ $item->getTranslation('name', $language->code, true) }}" maxlength="255">
                    </div>
                    @endif

                    @if($definition->isTranslated('description'))
                    <div class="mb-0">
                        <label class="form-label" for="lookup-description-{{ $language->code }}">Təsvir</label>
                        <textarea id="lookup-description-{{ $language->code }}" class="form-control" name="description[{{ $language->code }}]" rows="3" maxlength="2000">{{ $item->getTranslation('description', $language->code, true) }}</textarea>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($definition->hasField('color'))
        <div class="col-sm-6 mb-3">
            <label class="form-label" for="lookup-color">Rəng</label>
            <div class="input-group">
                <input type="color" class="form-control form-control-color lookup-color-picker" value="{{ $item->color ?: '#556ee6' }}" data-target="#lookup-color" aria-label="Rəng seç">
                <input type="text" id="lookup-color" class="form-control" name="color" value="{{ $item->color }}" placeholder="#556ee6" maxlength="7">
            </div>
        </div>
        @endif

        @if($definition->hasField('class_name'))
        <div class="col-sm-6 mb-3">
            <label class="form-label" for="lookup-class-name">CSS sinfi</label>
            <input type="text" id="lookup-class-name" class="form-control" name="class_name" value="{{ $item->class_name }}" placeholder="bg-success" maxlength="100">
        </div>
        @endif

        @if($definition->hasField('icon'))
        <div class="col-12 mb-3">
            <label class="form-label" for="lookup-icon">İkon</label>
            <div class="input-group">
                <span class="input-group-text lookup-icon-preview" id="lookup-icon-preview">
                    @if($item->icon)
                    <i class="{{ $item->icon }}"></i>
                    @endif
                </span>
                <input type="text" id="lookup-icon" class="form-control" name="icon" value="{{ $item->icon }}" placeholder="bx bx-check-circle" maxlength="100">
                <button type="button" class="btn btn-outline-primary" data-icon-picker-target="#lookup-icon" data-icon-picker-preview="#lookup-icon-preview">
                    <i class="fas fa-th"></i> İkon seçin
                </button>
            </div>
        </div>
        @endif

        @foreach ($switches as $switch)
        <div class="col-sm-4 mb-3">
            <div class="form-check form-switch form-switch-md" dir="ltr">
                <input class="form-check-input" type="checkbox" name="{{ $switch['field'] }}" id="lookup-{{ $switch['field'] }}" value="1" @checked($switch['checked'])>
                <label class="form-check-label" for="lookup-{{ $switch['field'] }}">{{ $switch['label'] }}</label>
            </div>
        </div>
        @endforeach
    </div>
</form>
