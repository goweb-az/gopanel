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

    initFormUiElements(document);
    observeDynamicFormUiElements();

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

function setMetaCollapseButtonState($collapse, isOpen) {
    var target = '#' + $collapse.attr('id');
    var $button = $('.meta-collapse-toggle[data-bs-target="' + target + '"]');
    var openText = $button.data('open-text') || 'Meta bilgiləri bağla';
    var closedText = $button.data('closed-text') || 'Meta bilgiləri aç';

    $button.attr('aria-expanded', isOpen ? 'true' : 'false');
    $button.find('span').text(isOpen ? openText : closedText);
    $button.find('i')
        .toggleClass('fa-chevron-up', isOpen)
        .toggleClass('fa-chevron-down', !isOpen);
}

function initMetaCollapse(context) {
    var $context = $(context || document);

    $context.find('.meta-collapse').addBack('.meta-collapse').each(function () {
        setMetaCollapseButtonState($(this), $(this).hasClass('show'));
    });
}

function initFormUiElements(context) {
    var $context = $(context || document);

    $context.find('.select2').addBack('.select2').each(function () {
        if (!$(this).data('select2')) {
            $(this).select2({
                theme: 'bootstrap-5',
            });
        }
    });

    $context.find('.bigTags').addBack('.bigTags').not('[data-bigtags-initialized]').each(function () {
        $(this).attr('data-bigtags-initialized', 'true');
        $(this).tagsinput({
            tagClass: 'big'
        });
    });

    $context.find('.tags').addBack('.tags').not('[data-tags-initialized]').each(function () {
        $(this).attr('data-tags-initialized', 'true');
        $(this).tagsInput({
            width: 'auto'
        });
    });

    initMetaCollapse($context);
    initDatatableUiElements();
}

function observeDynamicFormUiElements() {
    if (!window.MutationObserver) {
        return;
    }

    var target = document.getElementById('form-wrap');
    if (!target || target.dataset.uiObserverInitialized) {
        return;
    }

    target.dataset.uiObserverInitialized = 'true';

    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            $(mutation.addedNodes).each(function () {
                if (this.nodeType === 1) {
                    initFormUiElements(this);
                }
            });
        });
    });

    observer.observe(target, {
        childList: true,
        subtree: true
    });
}

$(document).on('shown.bs.collapse', '.meta-collapse', function () {
    setMetaCollapseButtonState($(this), true);
});

$(document).on('hidden.bs.collapse', '.meta-collapse', function () {
    setMetaCollapseButtonState($(this), false);
});


function initDatatableUiElements(){
    // Bootstrap 5 form-switch: change olduqda label mətnini və rəngini yenilə
    $('input.form-check-input.is_active, input.form-check-input.status').not('[data-switch-initialized]').each(function() {
        $(this).attr('data-switch-initialized', 'true');
        $(this).on('change', function() {
            var $label = $(this).siblings('.form-check-label');
            if ($(this).is(':checked')) {
                $label.text($(this).data('on-text') || 'Aktiv')
                      .removeClass('text-danger').addClass('text-success');
            } else {
                $label.text($(this).data('off-text') || 'Deaktiv')
                      .removeClass('text-success').addClass('text-danger');
            }
        });
    });

    // Bootstrap Switch Button: AJAX sonrası dinamik init
    $('input[data-toggle="switchbutton"]').not('[data-switchbutton-initialized]').each(function() {
        $(this).attr('data-switchbutton-initialized', 'true');
        this.switchButton();
    });

    initToltip();
}

function handleToggleSuccess(response) {
    pageLoader(false);

    if (response.status == 'success') {
        toastr.success(response.message);
    } else {
        basicAlert(response.message, response.status);
    }

    if (window.dTable && typeof window.dTable.ajax === 'function') {
        window.dTable.ajax.reload(null, false);
    }
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
    var key         =   $(this).attr('data-key');
    var route       =   $(this).attr('data-url');
    var hard        =   $(this).attr('data-hard');
    var element     =   $(this).parents('tr');
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
                    if (window.dTable && typeof window.dTable.ajax === 'function') {
                        window.dTable.ajax.reload();
                    } else {
                        element.remove();
                    }
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
    if(id){
        $.ajax({
            url: route,
            data: {id:id,row:row,model:model,status:status},
            type: 'POST',
            success: handleToggleSuccess,
            error: function (e) {
                console.log(e);
                pageLoader(false);
                showError(e);
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
    if(id){
        $.ajax({
            url: route,
            data: {id:id,row:row,model:model,status:status},
            type: 'POST',
            success: handleToggleSuccess,
            error: function (e) {
                console.log(e);
                pageLoader(false);
                showError(e);
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

