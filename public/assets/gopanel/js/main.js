// import $ from 'https://code.jquery.com/jquery-3.6.0.min.js';

var token       = document.querySelector('meta[name="csrf-token"]') != null ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : null;
var currentUrl  = window.location.href;
window.dTable = null;

document.addEventListener("DOMContentLoaded", function() {
    
    if (typeof toastr != 'undefined') {
        toastr.options = {
            "closeButton": false,
            "debug": false,
            "newestOnTop": false,
            "progressBar": false,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        }
        // toastr["success"]("Are you the six fingered man?");
    }
    //Toltip
    // initToltip()

    if($(".sortable").length){
        $(function() {
            $(".sortable").sortable();
            $(".sortable").disableSelection();
        });
    }

    if($(".sortableIcon").length){
        $(function() {
            $(".sortableIcon").sortable({
                handle: "td.sort-td",
                axis: "y"
            });
        });
    }

    if ($(".select2").length) {
        $(".select2").select2({
            theme: "bootstrap-5",
        });
    }

    if ($(".bigTags").length) {
        $('.bigTags').tagsinput({
            tagClass: 'big'
        });
    }

    if ($(".tags").length) {
        $('.tags').tagsInput({
            width: 'auto'
        });
    }

});


if (token) {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': token
        }
    });
}
else{
    console.error("Token not set ajax");
}

var beforeUnloadEventHandler;

function beforeunloadPage() {
    beforeUnloadEventHandler = function (e) {
        var message = 'Səhifədən çıxsanız, doldurduğunuz məlumatlar itiriləcək.';
        e.preventDefault();
        e.returnValue = message;
        return message;
    };
    window.addEventListener('beforeunload', beforeUnloadEventHandler);
}

function removeBeforeunloadPage() {
    if (beforeUnloadEventHandler) {
        window.removeEventListener('beforeunload', beforeUnloadEventHandler);
        beforeUnloadEventHandler = null;
    }
}

$('.modal').on('hidden.bs.modal', function () {
    removeBeforeunloadPage();
});


function initDatatableUiElements(){
    if ($('input.statusChange').length > 0) {
        $('input.statusChange').bootstrapToggle();
    }
    initToltip();
}


if ($('#editor').length) {
    ClassicEditor
    .create( document.querySelector( '#editor' ) )
    .then( editor => {
        editor.ui.view.editable.style.height = '400px';
    } )
    .catch( error => {
        console.error( error );
    } );
}

if ($(".select2").length) {
    $(".select2").select2();
}

$("body").on("click",".delete-button", function (e){
    var _this=this;
    e.preventDefault();
    Swal.fire({
        title: 'Are you sure delete this item?',
        text: 'It will be permanently deleted',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'No, don\'t delete',
    }).then((result) => {
        if (result.isConfirmed) {
            $(_this).next('form').submit();
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire('Canceled', 'The delete function has been disabled', 'error');
            return false;
        }
    });
})




$('body').on('click','.delete', function (e){
    e.preventDefault();
    var key     =   $(this).attr('data-key');
    var route   =   $(this).attr('data-url');
    var hard    =   $(this).attr('data-hard');
    var _this   =   $(this).parents('tr');
    Swal.fire({
        title: 'Silmək istədiyinizə əminsiniz?',
        text: 'Qalıcı olaraq silinəcək',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Bəli, sil',
        cancelButtonText: 'Xeyr, silmə',
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url:route,
                data:{key:key,hard:hard},
                method:'POST',
                success: function (response) {
                    pageLoader(false);
                    basicAlert(response.message, response.status);
                    _this.remove();
                },
                error: function (e) {
                    console.log(e);
                    pageLoader(false);
                }
            })
        } else if (result.dismiss === Swal.DismissReason.cancel) {
            Swal.fire('Ləğv olundu', 'Silinmə funksiyası ləğv edildi', 'error');
        }
    });
});


$("body").on("change",".status", function(e){
    let status  = $(this).prop('checked');
    let row     = $(this).attr("data-row");
    let id      = $(this).attr("data-id");
    let model   = $(this).attr("data-model");
    let route   = $(this).attr("data-route");
    if(id > 0){
        $.ajax({
            url: route,
            data: {id:id,row:row,model:model,status:status},
            // contentType: "application/json",
            type: 'POST',
            success: function (response) {
                pageLoader(false);
                basicAlert(response.message, response.status);
                $("#salesAddBackdrop").modal("hide");
                window.dTable.ajax.reload();
                if(response.status == 'success'){
                    clearFormInputs("#addForm");
                    salesCounter();
                }
            },
            error: function (e) {
                console.log(e);
                pageLoader(false);
            }
        });
    }
});


$("body").on("change",".is_active", function(e){
    let status  = $(this).prop('checked');
    let row     = $(this).attr("data-row");
    let id      = $(this).attr("data-id");
    let model   = $(this).attr("data-model");
    let route   = $(this).attr("data-url");
    if(id > 0){
        $.ajax({
            url: route,
            data: {id:id,row:row,model:model,status:status},
            // contentType: "application/json",
            type: 'POST',
            success: function (response) {
                pageLoader(false);
                basicAlert(response.message, response.status);
                $("#salesAddBackdrop").modal("hide");
                window.dTable.ajax.reload();
                if(response.status == 'success'){
                    clearFormInputs("#addForm");
                    salesCounter();
                }
            },
            error: function (e) {
                console.log(e);
                pageLoader(false);
            }
        });
    }
});



$("body").on("click",".clear-cache-btn", function(e){
    let route   = $(this).attr("data-url");
    if(route){
        Swal.fire({
            title: 'Silmək istədiyinizə əminsiniz?',
            text: 'Bu biraz təhülükəli əmr ola bilər',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Bəli, sil',
            cancelButtonText: 'Xeyr, silmə',
        }).then((result) => {
            if (result.isConfirmed) {
                pageLoader(true);
                $.ajax({
                    url: route,
                    data: {clear:true},
                    type: 'GET',
                    success: function (response) {
                        pageLoader(false);
                        basicAlert(response.message, response.status);
                    },
                    error: function (e) {
                        console.error(e);
                        pageLoader(false);
                    }
                });
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                Swal.fire('Ləğv olundu', 'Silinmə funksiyası ləğv edildi', 'info');
            }
        });
    }
});


$("body").on("sortupdate",".sortable", function(event,ui){
  let data      = $(this).sortable("serialize");
  let key       = $(this).attr("data-key");
  let row       = $(this).attr("data-row");
  let route     = $(this).attr("data-url");
  if (data && key && row && route) {
    $.ajax({
        url: route,
        data: {data:data, key:key, row:row},
        type: 'POST',
        success: function (response) {
            if (response.status != 'success') {
                basicAlert(response.message, response.status);
            }
        },
        error: function (e) {
            console.error(e);
            pageLoader(false);
        }
    });
  }  
});

