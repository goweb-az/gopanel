{{--
    «Bu qeydlə kim məşğuldur» zolağı - detal səhifəsində.

    Qıfıl deyil: səhifə bloklanmır. Məqsəd iki adminin eyni müştəriyə zəng
    etməsinin qarşısını almaqdır.

    Gözlənilən dəyişən (controller hazırlayır):
        $banner = \App\Helpers\Gopanel\AuthorCell::banner($item, 'gopanel.sales.orders', 'sifariş');
    `null`-dursa (icazə yoxdur) heç nə göstərilmir.
--}}
@if (!empty($banner))
    @include('gopanel.component.assignment-assets')

    <div class="card mb-3 gp-assignment-banner"
         data-gp-assignment
         data-gp-assign-url="{{ $banner['assign_url'] }}"
         data-gp-release-url="{{ $banner['release_url'] }}"
         data-gp-assign-state="{{ $banner['state'] }}"
         data-gp-assign-stale="{{ $banner['stale'] ? '1' : '0' }}"
         data-gp-assign-owner="{{ $banner['owner'] }}"
         data-gp-assign-label="{{ $banner['label'] }}"
         data-gp-can-takeover="{{ $banner['can_takeover'] ? '1' : '0' }}">
        <div class="card-body py-2 px-3 d-flex align-items-center gap-2 flex-wrap">

            @if ($banner['state'] === 'free')
                <span class="text-muted flex-grow-1">
                    <i class="bx bx-user-plus"></i>
                    Bu {{ $banner['label'] }} hələ heç kimin üzərində deyil.
                </span>
                <button type="button" class="btn btn-sm btn-primary" data-gp-assign>
                    <i class="bx bx-user-check"></i> Öz üzərimə götürürəm
                </button>

            @elseif ($banner['state'] === 'mine')
                <span class="text-success flex-grow-1">
                    <i class="bx bx-user-check"></i>
                    Bu {{ $banner['label'] }} <strong>sizin üzərinizdədir</strong>
                    · {{ $banner['assigned_at'] }}
                </span>
                <button type="button" class="btn btn-sm btn-outline-secondary" data-gp-release>
                    <i class="bx bx-log-out-circle"></i> İşi burax
                </button>

            @else
                <span class="text-warning flex-grow-1">
                    <i class="bx bx-user-voice"></i>
                    Bu {{ $banner['label'] }} ilə <strong>{{ $banner['owner'] }}</strong> məşğuldur
                    · {{ $banner['assigned_at'] }}
                    @if ($banner['stale'])
                        <span class="text-muted">({{ $banner['ttl_hours'] }} saatdır toxunulmayıb)</span>
                    @else
                        <span class="text-muted">- zəng etməzdən əvvəl onunla dəqiqləşdirin</span>
                    @endif
                </span>

                @if ($banner['show_takeover'])
                    <button type="button" class="btn btn-sm btn-outline-warning" data-gp-assign data-gp-takeover>
                        <i class="bx bx-user-pin"></i> Üzərimə götürürəm
                    </button>
                @endif
            @endif

        </div>
    </div>
@endif
