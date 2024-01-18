//$( ".datepicker" ).datepicker({
//        showOtherMonths:true,
//        autoSize: true,
//        appendText: '<span class="help-block">(dd-mm-yyyy)</span>',
//        dateFormat: 'dd-mm-yy'
//});
$( ".datepicker" ).daterangepicker({
    singleDatePicker: true,
    linkedCalendars: false,
    locale: {
        "format": 'DD-MMM-YYYY',
    }
});