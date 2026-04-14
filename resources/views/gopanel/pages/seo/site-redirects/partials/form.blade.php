<form action="{{$route}}" id="data-form" method="post" autocomplete="off">
    @csrf

    <div class="row g-3">

        {{-- Locale (dil) --}}
        <div class="col-md-4">
            <label class="form-label">Dil (locale)</label>
            <select name="locale" class="form-select">
                <option value="" {{ $item->locale === null ? 'selected' : '' }}>— Bütün dillər —</option>
                @php
                    // $languages varsa ondan istifadə et; yoxdursa ehtiyat AZ/EN/RU göstər
                    $locItems = isset($languages) && count($languages)
                        ? $languages->map(fn($l) => $l->code)->all()
                        : ['az','en','ru'];
                @endphp
                @foreach ($locItems as $lc)
                    <option value="{{$lc}}" {{ $item->locale === $lc ? 'selected' : '' }}>{{ strtoupper($lc) }}</option>
                @endforeach
            </select>
            <div class="form-text">Boş buraxsan bütün dillərdə işləyər.</div>
        </div>

        {{-- Match type --}}
        <div class="col-md-4">
            <label class="form-label">Uyğunluq tipi</label>
            <select name="match_type" id="match_type" class="form-select" onchange="toggleRegexFlags(this)">
                @foreach ($match_types as $type)
                    <option value="{{ $type->value }}"
                        {{ $item->match_type === $type->value ? 'selected' : '' }}>
                        {{ method_exists($type, 'label') ? $type->label() : ucfirst($type->value) }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- HTTP code --}}
        <div class="col-md-4">
            <label class="form-label">HTTP kodu</label>
            <select name="http_code" class="form-select">
                @foreach ([301,302,307,308] as $code)
                    <option value="{{$code}}" {{ (int)$item->http_code === $code ? 'selected' : '' }}>{{$code}}</option>
                @endforeach
            </select>
        </div>

        {{-- Source --}}
        <div class="col-12">
            <label class="form-label">Mənbə (source)</label>
                   <textarea name="source" class="form-control" placeholder="/az/services/... və ya tam URL və ya regex nümunəsi">{{$item->source}}</textarea>
            <div class="form-text">
                <strong>exact/prefix/contains:</strong> tam URL və ya path. |
                <strong>regex:</strong> delimitersiz nümunə yaz (məs: <code>^https?:\/\/proweb\.az\/([a-z]{2})\/services\/(.+)$</code>).
            </div>
        </div>

        {{-- Regex flags (yalnız regex üçün) --}}
        <div class="col-12" id="regex_flags_wrap" style="display:none;">
            <label class="form-label">Regex bayraqları</label>
            <input type="text" class="form-control" name="regex_flags"
                   value="{{ old('regex_flags', $item->regex_flags) }}" placeholder="i,m,u">
            <div class="form-text">Məs: i (case-insensitive), m, u və s.</div>
        </div>

        {{-- Target --}}
        <div class="col-12">
            <label class="form-label">Hədəf (target)</label>
            <input type="text" class="form-control" name="target"
                   value="{{ old('target', $item->target) }}"
                   placeholder="https://proweb.az/az/... və ya boş burax (ana səhifə)">
            <div class="form-text">Boş buraxsan – middleware ana səhifəyə yönləndirəcək.</div>
        </div>

        {{-- Priority & Active --}}
        <div class="col-md-4">
            <label class="form-label">Prioritet</label>
            <input type="number" class="form-control" name="priority"
                   value="{{ old('priority', (int)$item->priority) }}" step="1">
            <div class="form-text">Böyük prioritet əvvəl işləyir.</div>
        </div>

        {{-- Date range --}}
        <div class="col-md-4">
            <label class="form-label">Başlama tarixi</label>
            <input type="datetime-local" class="form-control" name="starts_at"
                   value="{{ old('starts_at', optional($item->starts_at)->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Bitmə tarixi</label>
            <input type="datetime-local" class="form-control" name="ends_at"
                   value="{{ old('ends_at', optional($item->ends_at)->format('Y-m-d\TH:i')) }}">
        </div>

        <div class="col-12">
            <label for="statusSelect" class="form-label">Status</label>
            <select id="statusSelect" class="form-select" name="is_active">
                <option value="1" @selected(!is_null($item?->id) && $item->is_active == 1)>Aktiv</option>
                <option value="0" @selected(!is_null($item?->id) && $item->is_active == 0)>Deaktiv</option>
            </select>
        </div>

        {{-- Notes --}}
        <div class="col-12">
            <label class="form-label">Qeyd</label>
            <textarea name="notes" class="form-control">{{$item->notes}}</textarea>
        </div>

    </div>
</form>

{{-- Regex bayrağı üçün sadə toggle --}}

