

$("body").on("click",".show-log", function(e){
    e.preventDefault();
    let route = $(this).attr("data-url");
    if (route) {
        let modal = $("#showLogModal");
        pageLoader(1);
        $.ajax({
            url: route,
            type: "GET",
            data: {show:true},
            success: function (response) {
                pageLoader(0);
                if (response.status == 'success') {
                    modal.modal("show");
                    modal.find("#modalBody").html(response.html);
                    $("#data_id").text(response.data_id);
                    let log_details = eval('(' + $('.log_details').text() + ')');
			        $('.log_details').json_viewer(log_details);
                    let context = eval('(' + $('.context').text() + ')');
			        $('.context').json_viewer(context);
                }
                else{
                    basicAlert(response.message,response.status);
                }
            },
            error: function (error) {
                console.error(error);
            }
        });
    }
    else{
        alert(route);
    }
});


$("body").on("click",".show-history", function(e){
    e.preventDefault();
    let route = $(this).attr("data-url");
    if (route) {
        let modal = $("#showHistoryModal");
        pageLoader(1);
        $.ajax({
            url: route,
            type: "GET",
            data: {show:true},
            success: function (response) {
                pageLoader(0);
                if (response.status == 'success') {
                    modal.modal("show");
                    modal.find("#modalBody").html(response.html);
                    $("#data_id").text(response.data_id);
                    let properties = eval('(' + $('.properties').text() + ')');
			        $('.properties').json_viewer(properties);
                }
                else{
                    basicAlert(response.message,response.status);
                }
            },
            error: function (error) {
                console.error(error);
            }
        });
    }
    else{
        alert(route);
    }
});