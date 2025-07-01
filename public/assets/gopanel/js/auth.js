$("body").on("submit","#gopanelAuthFrom", function(e){
    e.preventDefault();
    if(!($("#email").val().length > 0)){
        basicAlert("E-poçt boş olabilməz");
    }
    else if(!($("#password").val().length > 0)){
        basicAlert("Şifrə boş olabilməz");
    }
    else{
        pageLoader(true);
        $.ajax({
            url:$(this).attr("action"),
            data:$(this).serialize(),
            method:'POST',
            success: function (response) {
                pageLoader(false);
                basicAlert(response.message, response.status);
                if (response.status == 'success')
                    location.href = response.redirect_to;
            },
            error: function (e) {
                console.log(e);
                pageLoader(false);
                if(e.responseJSON)
                    basicAlert(e.responseJSON.message);
            }
        })
    }
});