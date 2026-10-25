{{--
    İxrac düymələri - Excel/PDF endirmə + (istəyə bağlı) «Mailə göndər».

    Hamısı ortaq endpoint-ə gedir (`gopanel.exports.download` / `.mail`) və
    sənəd `config/custom/export.php` → `definitions` açarı ilə seçilir.
    Hesablama yoxdur - linklər `App\Support\Export\ExportLinks`-dən gəlir.

    Dəyişənlər:
      $key        - reyestr açarı (məcburi), məs. 'activity.activity-logs'
      $label      - düymənin adı (default «İxrac»)
      $params     - sabit parametrlər (tək sənəd üçün ['uid' => ...])
      $filters    - süzgəc konteynerinin CSS seçicisi (məs. '#filterPanel').
                    Klik anında oradakı `name` / `data-export-param` sahələri
                    linkə əlavə olunur - fayl ekranda görünəni verir.
      $mail       - false verilsə «Mailə göndər» bəndi göstərilmir
      $mailTo     - pəncərədə hazır gələn alıcı (default: adminin öz ünvanı)
      $mailTitle  - pəncərənin başlığı (default $label)

    İstifadə:
      @include('gopanel.exports.buttons', ['key' => 'activity.activity-logs', 'filters' => '#filterPanel'])
--}}
<div class="btn-group gp-export">
    <a class="btn btn-outline-success" data-gp-export
       @if (!empty($filters)) data-gp-export-filters="{{ $filters }}" @endif
       href="{{ \App\Support\Export\ExportLinks::download($key, 'excel', $params ?? []) }}">
        <i class="bx bx-file"></i> {{ $label ?? 'İxrac' }}
    </a>
    <button type="button" class="btn btn-outline-success dropdown-toggle dropdown-toggle-split"
            data-bs-toggle="dropdown" aria-expanded="false">
        <span class="visually-hidden">Format seç</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @foreach (\App\Support\Export\ExportLinks::formats() as $exportFormat)
            <li>
                <a class="dropdown-item" data-gp-export
                   @if (!empty($filters)) data-gp-export-filters="{{ $filters }}" @endif
                   href="{{ \App\Support\Export\ExportLinks::download($key, $exportFormat['value'], $params ?? []) }}">
                    <i class="{{ $exportFormat['icon'] }} me-1"></i> {{ $exportFormat['label'] }}
                </a>
            </li>
        @endforeach
        @if (($mail ?? true) && \App\Support\Export\ExportLinks::canMail($key))
            <li><hr class="dropdown-divider"></li>
            <li>
                <button type="button" class="dropdown-item"
                        data-gp-export-mail="{{ $key }}"
                        data-gp-export-mail-url="{{ \App\Support\Export\ExportLinks::mailUrl() }}"
                        data-gp-export-mail-title="{{ $mailTitle ?? ($label ?? 'Sənədi') }}"
                        data-gp-export-mail-formats="{{ \App\Support\Export\ExportLinks::formatsJson() }}"
                        data-gp-export-mail-to="{{ $mailTo ?? auth('gopanel')->user()?->email }}"
                        @if (!empty($params)) data-gp-export-mail-params="{{ json_encode($params) }}" @endif>
                    <i class="bx bx-mail-send text-primary me-1"></i> Mailə göndər
                </button>
            </li>
        @endif
    </ul>
</div>

@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('assets/gopanel/css/export.css') }}">
    @endpush
    @push('scripts')
        <script src="{{ asset('assets/gopanel/js/modules/export.js') }}"></script>
    @endpush
@endonce
