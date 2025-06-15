function refreshDataTable(){
    if (window.dTable) {
        window.dTable.ajax.reload();
    }
    else{
        window.location.reload();
    }
}