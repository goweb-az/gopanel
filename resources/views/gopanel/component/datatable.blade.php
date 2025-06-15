@push('styles')
    <!-- DataTables -->
    <link href="/assets/gopanel/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="/assets/gopanel/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <!-- Responsive datatable examples -->
    <link href="/assets/gopanel/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />   
@endpush
@php
if (isset($__export)) {
    $__excel    = isset($__export['excel']) ? $__export['excel'] : '1,2,3,4';
    $__pdf      = isset($__export['pdf']) ? $__export['pdf'] : '1,2,3,4';
    $__print    = isset($__export['print']) ? $__export['print'] : '1,2,3,4';
}
else{
    $__excel    = '0,1,2,3,4,5,6,7,8,9,10';
    $__pdf      = '0,1,2,3,4,5,6,7,8,9,10';
    $__print    = '0,1,2,3,4,5,6,7,8,9,10';
}
if (isset($__cusomParam)) {
    $__cusomParam =  is_array($__cusomParam) ? $__cusomParam = http_build_query($__cusomParam, 'amp;amp;', '&') : $__cusomParam;
    $__cusomParam = str_replace("amp;","",$__cusomParam);
} else {
    $__cusomParam = '';
}
$options = "[10, 25, 50, 100, 300,'-1'], ['10 Ədəd', '25 Ədəd', '50 Ədəd', '100 Ədəd', '300 Ədəd', 'Hamısı']";
if (isset($__all_status_diasbled)) {
    $options = "[10, 25, 50, 100, 300], ['10 Ədəd', '25 Ədəd', '50 Ədəd', '100 Ədəd', '300 Ədəd']";
}
$__tableClaslist = isset($__tableClaslist) ? $__tableClaslist : ['table-hover'];
@endphp

<table id="{{ $__datatableId }}" class="table {{implode(" ", $__tableClaslist)}}">
    {{-- Table codes  --}}
</table>


@push('scripts')
    <!-- Required datatable js -->
    <script src="/assets/gopanel/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="/assets/gopanel/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <!-- Buttons examples -->
    <script src="/assets/gopanel/libs/datatables.net-buttons/js/dataTables.buttons.min.js"></script>
    <script src="/assets/gopanel/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js"></script>
    <script src="/assets/gopanel/libs/jszip/jszip.min.js"></script>
    <script src="/assets/gopanel/libs/pdfmake/build/pdfmake.min.js"></script>
    <script src="/assets/gopanel/libs/pdfmake/build/vfs_fonts.js"></script>
    <script src="/assets/gopanel/libs/datatables.net-buttons/js/buttons.html5.min.js"></script>
    <script src="/assets/gopanel/libs/datatables.net-buttons/js/buttons.print.min.js"></script>
    <script src="/assets/gopanel/libs/datatables.net-buttons/js/buttons.colVis.min.js"></script>
    
    <!-- Responsive examples -->
    <script src="/assets/gopanel/libs/datatables.net-responsive/js/dataTables.responsive.min.js"></script>
    <script src="/assets/gopanel/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js"></script>
    <script src="{{ asset('assets/gopanel/js/initDatatable.js?v='.time()) }}"></script>
@endpush
@push('js_stack')
    <script>

        var dTableRoute = '{{ route('gopanel.datatable.source', $__datatableName) }}?show_columns=ok';
        var dTableSourceRoute = "{{ route('gopanel.datatable.source', $__datatableName) . '?' . request()->getQueryString() . $__cusomParam }}";
        var __cusomParam = '{{$__cusomParam}}';
        var dTableElement = $("#{{ isset($__datatableId) ? $__datatableId : 'datatable' }}");
        var options = [{!!$options!!}];
        $(document).ready(function() {
            getColumnsInitTable(dTableRoute,dTableSourceRoute, dTableElement, options);
        });
    </script>
@endpush
