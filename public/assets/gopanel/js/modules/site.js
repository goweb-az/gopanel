// const { Exception } = require("sass");

var formwrap = $("#form-wrap");

initDatatableUiElements();

function getForm(route) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: route,
            data: {show:true},
            type: 'GET',
            success: function (response) {
                // beforeunloadPage();
                resolve(response);
            },
            error: function (e) {
                reject(e);
            }
        });
    });
}


function refreshDataTable(){
    if (window.dTable) {
        window.dTable.ajax.reload();
    }
    else{
        window.location.reload();
    }
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
        return false;
    }
    elementLoader("#form-wrap",true);
    saveForm(form).then(response => {
        elementLoader("#form-wrap");
        Swal.fire({
            title: response.message, 
            icon: response.status
        }).then((result) => {
            if (response.status == 'success') {
                $("#cerate-modal").modal("hide");
                refreshDataTable();
            }
        });
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
                    icon: response.status
                }).then((result) => {
                    if (response.status == 'success') {
                       if(response.redirect){
                            pageLoader(1);
                            window.location.href = response.redirect;
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



$("body").on("click", ".plus-plan-price-feature", function(e) {
    e.preventDefault();
    try {
        let parent  = $(this).parents(".plan-price-features");
        let route   = parent.attr("data-route");
        let rows    = parent.find(".plan-price-feature-rows");
        if (!route) {
            throw new Error("Route boş və ya düz deyil");
        }
        elementLoader("#plan-price-features",true);
        let featureIndex = rows.find(".feature-row").eq(-1).attr("data-index");
        $.ajax({
            url: route,
            type: "GET",
            data: {show:true,index: featureIndex},
            success: function(response) {
                elementLoader("#plan-price-features",false);
                let targetDiv = $("#plan-price-feature-row");
                if (targetDiv.length && response.html) {
                    targetDiv.append(response.html);
                } else {
                    console.warn("Hedef div tapılmadı: #plan-price-feature-row");
                }
            },
            error: function(xhr) {
                elementLoader("#plan-price-features",false);
                throw new Error(xhr.responseText || "Bilinmeyen bir xəta");
            }
        });
        
    } catch (error) {
        console.error(error);
        basicAlert(error.message ||  "Bilinmeyen bir xəta");
        elementLoader("#plan-price-features",false);
    }
});


$("body").on("click", ".delete-plan-price-feature", function (e) {
    e.preventDefault();
    let deletedId = $(this).attr("data-deleted-id");
    let deletedRow = $(deletedId);
    deletedRow.fadeOut(300, function () { $(this).remove(); });
    console.log("Silinen element:", deletedId);
});


$("body").on("change", ".select-toogle", function (e) {
    e.preventDefault();
    let select      = $(this);
    let elementId   = select.attr("data-id-element");
    let selected    = select.val();
    $(".select-toggle-div").addClass("d-none");
    $("#"+elementId+selected).removeClass("d-none");
});



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
