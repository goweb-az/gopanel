{{--
    «Məsul tarixçəsi» - `record_authors` (rol = assignee).

    Qeydlər silinmədiyi üçün siyahı hazır datadır: kim, nə vaxtdan nə vaxta
    qədər işləyib və rolu necə bitib.

    Gözlənilən dəyişən (controller hazırlayır):
        $assignmentRows = \App\Helpers\Gopanel\AuthorCell::historyRows(
            app(\App\Services\Author\RecordAuthorService::class)->history($item)
        );
--}}
@forelse ($assignmentRows as $row)
    <div class="gp-assignment-line">
        <div class="gp-assignment-line__main">
            <div class="fw-medium">
                {{ $row['name'] }}
                @if ($row['current'])
                    <span class="badge bg-info-subtle text-info ms-1">hazırda</span>
                    @if ($row['stale'])
                        <span class="badge bg-light text-muted ms-1">köhnəlib</span>
                    @endif
                @endif
            </div>
            <div class="text-muted font-size-11">{{ $row['period'] }}</div>
        </div>

        @if ($row['end_reason'])
            <div class="text-muted font-size-11 text-nowrap">{{ $row['end_reason'] }}</div>
        @endif
    </div>
@empty
    <div class="text-muted text-center py-4">Bu qeydi hələ heç kim öz üzərinə götürməyib.</div>
@endforelse
