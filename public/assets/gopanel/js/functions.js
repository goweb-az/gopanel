

function refreshDataTable(){
    if (window.dTable) {
        window.dTable.ajax.reload();
    }
    else{
        window.location.reload();
    }
}


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



function initToltip(){
    let tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
    if (tooltipTriggerList) {
        let tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl)); 
    }
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



function clearFormInputs(formId) {
    $(formId).find('textarea, select, input').each(function() {
        let fieldName = $(this).attr('name');
        if (excludeFields.indexOf(fieldName) === -1) {
            $(this).val('');
        }
    });
}




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


function triggerClass(target, className = 'active') {
    if (target.hasClass(className)) {
        target.removeClass(className);
    } else {
        $("." + className).removeClass(className);
        target.addClass(className);
    }
}