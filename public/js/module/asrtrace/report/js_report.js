
$(document).ready(function() {
    $('#reportdata').dataTable( {
        "bDestroy": true,
        //"scrollY":   300,
        "scrollX":   true,
        "aaSorting": [ ],
        "scrollCollapse": true,
        "paging": true,
        //"aLengthMenu": [[10, 25, 50, -1],[10, 25, 50, "All"]],
        dom: 'T<"clear">lfrtip',
        "tableTools": {
            "sSwfPath": "/js/datatable/swf/copy_csv_xls_pdf.swf"

        }
    } );
} );


$(document).ready(function() {
    $('#dataTable').dataTable( {
        "bDestroy": true,
        "bSearch" : false,
        //"scrollY":   300,
        "scrollX":   true,
        "aaSorting": [ ],
        "scrollCollapse": true,
        "paging": false

        //"aLengthMenu": [[10, 25, 50, -1],[10, 25, 50, "All"]],


    } );
} );










