// import $ from 'https://code.jquery.com/jquery-3.6.0.min.js';

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

$('.modal').on('hidden.bs.modal', function () {
    removeBeforeunloadPage();
});


var token       = document.querySelector('meta[name="csrf-token"]') != null ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : null;
var currentUrl  = window.location.href;

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

// var pusher = new Pusher('8c46139410c52e228fdf', {
//     cluster: 'ap4'
// });

function focusElement(element){
    setTimeout(() => {
        $(element).focus();
    }, 500);
}


function base64Encode(str) {
    return btoa(unescape(encodeURIComponent(str)));
}

function base64Decode(str) {
    return decodeURIComponent(escape(atob(str)));
}

function optimize() {
    let cookies = document.cookie.split("; ");
    for (let c of cookies) {
        let cookieName = c.split("=")[0];
        document.cookie = cookieName + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    }
    sessionStorage.clear();
    localStorage.clear();

    console.info("Successfuly cleared all data");
}


function getQueryParams() {
    return location.search
        ? location.search.substr(1).split`&`.reduce((qd, item) => {let [k,v] = item.split`=`;
        v = v && decodeURIComponent(v); (qd[k] = qd[k] || []).push(v); return qd}, {})
        : {}
}


function get_query(text = ''){
    var url = location.search + text;
    var qs = url.substring(url.indexOf('?') + 1).split('&');
    for(var i = 0, result = {}; i < qs.length; i++){
        qs[i] = qs[i].split('=');
        result[qs[i][0]] = decodeURIComponent(qs[i][1]);
    }
    return result;
}

  

window.dTable = null;



function pageLoader(status=1,text='Loading...') {
    if (status == 0) {
        $("#pageLoader").fadeOut(200, function() {
            $(this).remove();
        });
    } else {
        if ($("#pageLoader").length == 0) {
            $("body").append("<div id='pageLoader' style='position:fixed;top:0;left:0;width:100%;height:100%;z-index:999999999;background: rgba(0,0,0,0.4);'><div style='width: 120px; height: 40px; position: absolute; top: 0px; bottom: 0px; left: 0px; right: 0px; margin: auto; background: none repeat scroll 0% 0% rgb(238, 238, 238); text-align: center; line-height: 38px; border: 1px solid rgb(221, 221, 221); vertical-align: middle; border-radius: 5px ! important; color: rgb(131, 131, 131);'><i class='fa fa-spinner fa-spin'></i> "+text+"</div></div>");
            $("body>div:last").hide().fadeIn(200);
        }
    }
    setTimeout(function(e){
        $("#pageLoader").fadeOut(200, function() {
            $(this).remove();
        });
    }, 30000);
}


function elementLoader(element, show = false) {
    var $element = $(element);
    if (show) {
        if ($element.find('.universal-loader-wrap').length === 0) {
            $element.css('position', 'relative');
            $element.append('<div class="universal-loader-wrap"><div class="universal-loader"></div></div>');
        }
    } else {
        var $loaderWrap = $element.find('.universal-loader-wrap');
        if ($loaderWrap.length > 0) {
            $loaderWrap.addClass('fade-out');
            setTimeout(function() {
                $loaderWrap.remove();
            }, 500);
        }
    }
}




function readURL(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();

        reader.onload = function (e) {
            $('#blah')
                .attr('src', e.target.result);
        };

        reader.readAsDataURL(input.files[0]);
    }
}


function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}


function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function showError(error){
    let errorMessage = 'An error occurred';
    if (error.responseJSON && error.responseJSON.message) {
        errorMessage = error.responseJSON.message;
    } else if (error.responseText) {
        errorMessage = error.responseText;
    }
    basicAlert(errorMessage, 'error');
}

function printData(divToPrint)
{
//    var divToPrint = document.getElementById("printTable");
    newWin= window.open("");
    newWin.document.write(divToPrint.outerHTML);
    newWin.print();
    newWin.close();
}




  function beep(sound) {
      var snd = new Audio(sound);
      snd.play();
  }

  function alertHtml(text='', type='danger', close=false){
      let closeHtml = '';
      let hour = get_hours();
      if(close){
          closeHtml = `
              <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
              </button>
          `;
      }
      return `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${text}  ${closeHtml}</div>`;
  }

  



  function processAjaxData(response, urlPath){
      document.getElementById("content").innerHTML = response.html;
      document.title = response.pageTitle;
      window.history.pushState({"html":response.html,"pageTitle":response.pageTitle},"", urlPath);
  }


  function get_hours(){
      let unix_timestamp = Date.now();
      // Create a new JavaScript Date object based on the timestamp
      // multiplied by 1000 so that the argument is in milliseconds, not seconds.
      var date = new Date(unix_timestamp * 1000);
      // Hours part from the timestamp
      var hours = date.getHours();
      // Minutes part from the timestamp
      var minutes = "0" + date.getMinutes();
      // Seconds part from the timestamp
      var seconds = "0" + date.getSeconds();

      // Will display time in 10:30:23 format
      var formattedTime = hours + ':' + minutes.substr(-2) + ':' + seconds.substr(-2);
  }





function initToltip(){
    let tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    if (tooltipTriggerList) {
        let tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl)); 
    }
}

function dataTableReload(){
    return window.dTable.ajax.reload();
}

  function basicAlert(html,type='error') {
      Swal.fire({html:html, icon:type});
  }

window.get_query = function get_query() {
    var url = location.search;
    var qs = url.substring(url.indexOf('?') + 1).split('&');
    for (var i = 0, result = {}; i < qs.length; i++) {
        qs[i] = qs[i].split('=');
        result[qs[i][0]] = decodeURIComponent(qs[i][1]);
    }
    return result;
}

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


function clearFormInputs(formId) {
    $(formId).find('textarea, select, input').each(function() {
        let fieldName = $(this).attr('name');
        if (excludeFields.indexOf(fieldName) === -1) {
            $(this).val('');
        }
    });
}


var currentUrl = window.location.href;

// Parametreyi kaldır
function removeParam(key) {
    
    var baseUrl = currentUrl.split('?')[0],
        params = currentUrl.split('?')[1],
        queryParams = {},
        updatedUrl = baseUrl;

    if (params) {
        params = params.split('&');

        params.forEach(function(param) {
            var paramKey = param.split('=')[0];
            if (paramKey !== key) {
                queryParams[paramKey] = param.split('=')[1];
            }
        });

        var newParams = $.param(queryParams);
        updatedUrl = baseUrl + '?' + newParams;
    }

    return updatedUrl;
}




function validateForm(formElement) {
    let isValid = true;

    $(formElement).find('input[required], select[required], textarea[required]').each(function() {
        $(this).removeClass('is-invalid');
    });
    $(formElement).find('input[required], select[required], textarea[required]').each(function() {
        if ($(this).is('input[type="radio"], input[type="checkbox"]')) {
            // Radio button veya checkbox kontrolü
            const groupName = $(this).attr('name');
            if ($(`input[name="${groupName}"]:checked`).length === 0) {
                $(`input[name="${groupName}"]`).addClass('is-invalid');
                const errorMsg = $(this).data('error-msg');
                basicAlert(errorMsg);
                isValid = false;
                return false;
            }
        } else if ($(this).is('select')) {
            // Select kontrolü
            if ($(this).val() === '') {
                $(this).addClass('is-invalid');
                const errorMsg = $(this).data('error-msg');
                basicAlert(errorMsg);
                isValid = false;
                return false;
            }
        } else {
            if ($(this).val().trim() === '') {
                $(this).addClass('is-invalid');
                const errorMsg = $(this).data('error-msg');
                basicAlert(errorMsg);
                isValid = false;
                return false;
            }
        }
    });
    return isValid;
}


function clearForm(formId) {
    let form = $(formId)[0];
    $(form).find('select, input, textarea').each(function() {
        if ($(this).is('select')) {
            $(this).val('').trigger('change');
        } else if ($(this).is(':checkbox') || $(this).is(':radio')) {
            // $(this).prop('checked', false);
        } else {
            $(this).val('');
        }
    });
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


function triggerClass(target, className = 'active') {
    if (target.hasClass(className)) {
        target.removeClass(className);
    } else {
        $("." + className).removeClass(className);
        target.addClass(className);
    }
}




$('body').on('click','.delete', function (){
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



