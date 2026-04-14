
function getColumnsInitTable(dTableRoute,dTableSourceRoute, dTableElement = null, options = null){
    
    dTableElement = dTableElement == null ? 'datatable' : dTableElement;
    options = options == null ? [[10, 25, 50, 100, 300,'-1'], ['10 Ədəd', '25 Ədəd', '50 Ədəd', '100 Ədəd', '300 Ədəd', 'Hamısı']] : options;
    return new Promise((resolve, reject) => {
        $.ajax({
            url: dTableRoute,
            type:'get',
            success:function (result){
                let dt = initTable(result, dTableSourceRoute, dTableElement, options);
                resolve(dt);
            },
            error:function (error){
                reject(error);
            }   
        });
    });
}


function initTable(_columns, dTableSourceRoute, dTableElement, options, callback) {
    if (typeof __cusomParam !== 'undefined' && __cusomParam !== null) {
        __cusomParam = __cusomParam.replace("amp;", "");
    }
    return window.dTable = dTableElement.DataTable({
        "language": {
            "url": "/assets/gopanel/json/table_az.json"
        },
        "lengthMenu": options,
        buttons: [],
        "ajax": {
            url: dTableSourceRoute,
            type: "GET", //POST
            data: get_query(__cusomParam)
        },
        columns: _columns,
        serverSide: true,
        responsive: false,
        lengthChange: true,
        processing: true,
        order: [
            [0, 'desc']
        ],
        "columnDefs": [{
            // "className": "dt-center",
            "targets": "_all"
        },
            {
                orderable: false,
                targets: [0]
            },
            {
                targets: 'no-sort',
                orderable: false
            }
        ],
        "drawCallback": function() {
            initDatatableUiElements();
        },
        "initComplete": function(settings, json) {
            initDatatableUiElements();
            if (typeof callback === 'function') {
                callback();
            }
        }
    });
}