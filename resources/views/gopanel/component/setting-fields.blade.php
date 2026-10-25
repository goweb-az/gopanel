{{--
    Tipli key-value tənzimləmə sahələri - istənilən təriflər dəsti üçün.

    Gözlənilən dəyişənlər:
        $groups - `SettingDefinitions::groupedFormRows($values)` (group => rows)
        $name   - forma massivinin adı (default `settings`)

    Hər sətir hazırdır (input tipi, dəyər, sərhəd) - burada heç nə hesablanmır.
--}}
@foreach ($groups as $groupLabel => $rows)
    <div class="mb-4">
        <h6 class="text-uppercase text-muted font-size-12 mb-3">{{ $groupLabel }}</h6>

        @foreach ($rows as $row)
            @if ($row['input'] === 'switch')
                <div class="d-flex align-items-start justify-content-between gap-2 py-2 border-bottom">
                    <div>
                        <label class="font-size-13 mb-0" for="setting-{{ $row['name'] }}">{{ $row['label'] }}</label>
                        @if ($row['hint'] !== '')
                            <div class="text-muted font-size-11">{{ $row['hint'] }}</div>
                        @endif
                    </div>
                    <div class="form-check form-switch flex-shrink-0">
                        {{-- switch işarəsiz qalanda da 0 göndərilsin --}}
                        <input type="hidden" name="{{ $name ?? 'settings' }}[{{ $row['name'] }}]" value="0">
                        <input type="checkbox" class="form-check-input" role="switch" id="setting-{{ $row['name'] }}"
                               name="{{ $name ?? 'settings' }}[{{ $row['name'] }}]" value="1" @checked($row['checked'])>
                    </div>
                </div>
            @else
                <div class="mb-3">
                    <label class="form-label" for="setting-{{ $row['name'] }}">{{ $row['label'] }}</label>

                    @if ($row['input'] === 'select')
                        <select class="form-select" id="setting-{{ $row['name'] }}" name="{{ $name ?? 'settings' }}[{{ $row['name'] }}]">
                            @foreach ($row['options'] as $optionValue => $optionLabel)
                                <option value="{{ $optionValue }}" @selected((string) $optionValue === $row['value'])>{{ $optionLabel }}</option>
                            @endforeach
                        </select>
                    @elseif ($row['input'] === 'textarea')
                        <textarea class="form-control" rows="4" id="setting-{{ $row['name'] }}"
                                  name="{{ $name ?? 'settings' }}[{{ $row['name'] }}]">{{ $row['value'] }}</textarea>
                    @else
                        <input type="{{ $row['input'] }}" class="form-control" id="setting-{{ $row['name'] }}"
                               name="{{ $name ?? 'settings' }}[{{ $row['name'] }}]" value="{{ $row['value'] }}"
                               @if ($row['input'] === 'number') step="{{ $row['step'] }}" @endif
                               @if ($row['input'] === 'number' && $row['min'] !== null) min="{{ $row['min'] }}" @endif
                               @if ($row['input'] === 'number' && $row['max'] !== null) max="{{ $row['max'] }}" @endif>
                    @endif

                    @if ($row['hint'] !== '')
                        <div class="form-text">{{ $row['hint'] }}</div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
@endforeach
