
var formwrap = $("#form-wrap");

function getForm(route) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: route,
            data: {show:true},
            type: 'GET',
            success: function (response) {
                beforeunloadPage();
                resolve(response);
            },
            error: function (e) {
                reject(e);
            }
        });
    });
}



$("body").on("click", "#open-create-modal", function(e){
    e.preventDefault();
    let route   = $(this).attr("data-route");
    formwrap.html("");
    $("#cerate-modal").modal("show");
    elementLoader("#form-wrap",true);
    getForm(route).then(response => {
        elementLoader("#form-wrap");
        if (response.status == 'success') {
            setTimeout(() => {
                formwrap.html(response.html);
                if ($("#form-wrap").find(".select2").length) {
                    $("#form-wrap").find(".select2").select2();
                }
            }, 500);
        }
        else{
            basicAlert(response.message,response.status);
        }
    }).catch(error => {
        console.log(error);
        showError(error);
    }).finally(() => {
        elementLoader("#form-wrap");
    });
});


$("body").on("click","#save-form-btn", function(e){
    e.preventDefault();
    let form = $("#data-form");
    if (!validateForm(form)) {
        return;
    }
    elementLoader("#form-wrap",true);
    saveForm(form).then(response => {
        elementLoader("#form-wrap");
        if (response.status == 'success') {
            if(response.status == 'success'){
                if (response.redirect) {
                    pageLoader(1);
                    window.location.href = response.redirect;
                } else {
                    window.dTable.ajax.reload();
                    $("#cerate-modal").modal("hide");
                }
            }
        }
        basicAlert(response.message,response.status);
    }).catch(error => {
        console.log(error);
        elementLoader("#form-wrap");
        showError(error);
    }).finally(() => {
        elementLoader("#form-wrap");
    });
});



function saveForm(form){
    return new Promise((resolve, reject) => {
        var formData = new FormData(form[0]);
            $.ajax({
                url: form.attr("action"),
                data: formData,
                type: 'POST',
                processData: false, // Prevent jQuery from automatically transforming the data into a query string
                contentType: false, // Prevent jQuery from automatically setting the Content-Type header
                success: function (response) {
                    // Handle success
                    resolve(response);
                },
                error: function (e) {
                    // Handle error
                    reject(e);
                }
            });
    });
}



$("body").on("click", ".edit", function(e){
    e.preventDefault();
    let route   = $(this).attr("href");
    formwrap.html("");
    $("#cerate-modal").modal("show");
    elementLoader("#form-wrap",true);
    getForm(route).then(response => {
        elementLoader("#form-wrap");
        if (response.status == 'success') {
            setTimeout(() => {
                formwrap.html(response.html);
            }, 500);
        }
        else{
            basicAlert(response.message,response.status);
        }
    }).catch(error => {
        console.log(error);
        showError(error);
    }).finally(() => {
        elementLoader("#form-wrap");
    });
});


$('#cerate-modal').on('hidden.bs.modal', function () {
    removeBeforeunloadPage();
});


$("body").on("submit", "#static-form", function(e) {
    e.preventDefault();
    try {
        let route = $(this).attr("action");
        if (!route) {
            throw new Error("Route boş və ya düz deyil");
        }
        let formData = new FormData(this);
        elementLoader("#static-form",true);
        $.ajax({
            url: route,
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                elementLoader("#static-form",false);
                Swal.fire({
                    title: response.message, 
                    icon: response.status,
                    willClose: () => {
                        if (response.status == 'success') {
                            if (response.redirect) {
                                pageLoader(1);
                                window.location.href = response.redirect;
                            }
                        }
                    }
                });
            },
            error: function(xhr) {
                elementLoader("#static-form",false);
                basicAlert(xhr.responseText ||  "Bilinmeyen bir xəta");
            }
        });
        
    } catch (error) {
        console.error(error);
        basicAlert(error.message ||  "Bilinmeyen bir xəta");
        elementLoader("#static-form",false);
    }
});


function pasteKeyIntoCKEditor(value, instanceId) {
    if (CKEDITOR.instances[instanceId]) {
        const editor = CKEDITOR.instances[instanceId];
        editor.focus();
        editor.insertHtml(` {{${value}}} `);
    }
}



$('body').on('dblclick', '.editable', function() {

    var route   = $(this).data('url');
    var id      = $(this).data('id');
    var row     = $(this).data('row');
    var model   = $(this).data('model');
    var text    = $(this).data('text');
    var $input = $('<input type="text" class="editable-input" />');
    $input.val(text);
    $(this).replaceWith($input);
    var $saveButton = $('<button class="save-btn"><i class="fas fa-save"></i></button>');
    
    $input.after($saveButton);
    $input.focus();
    $saveButton.off('click').on('click', function() {
        $saveButton.prop("disabled",true);
        var value = $input.val();
        $.ajax({
            url: route,
            type: "POST",
            data: {id:id,row:row,model:model,value:value},
            success: function(response) {
                $saveButton.prop("disabled",false);
                if (response.status == 'success') {
                    refreshDataTable();
                    $input.replaceWith(`
                        <span class="editable" 
                              data-model="${model}" 
                              data-row="${row}" 
                              data-id="${id}" 
                              data-url="${route}"
                              data-text="${value}"
                              >
                            ${value} <i class="fas fa-pen edit-pen"></i>
                        </span>
                    `);                        
                    $saveButton.remove();
                }
                else{
                    basicAlert(response.message,response.status);
                }
            },
            error: function(xhr) {
                basicAlert(xhr.responseText,"error");
                $saveButton.prop("disabled",false);
            }
        });
    });
});