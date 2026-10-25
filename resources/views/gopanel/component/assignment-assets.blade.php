{{--
    Təyinat (record authors) üçün CSS + JS - bir dəfə qoşulur.

    Datatable sətrində `AuthorCell::claimButton()` / `creatorLine()` işlədən
    siyahı səhifəsi bunu özü include edir; `assignment-banner` isə avtomatik.
--}}
@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset_v('assets/gopanel/css/assignment.css') }}">
    @endpush
    @push('scripts')
        <script src="{{ asset_v('assets/gopanel/js/modules/assignment/gp-assignment.js') }}"></script>
        <script src="{{ asset_v('assets/gopanel/js/modules/assignment/gp-assignment-gate.js') }}"></script>
    @endpush
@endonce
