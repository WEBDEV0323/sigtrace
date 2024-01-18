$(function() {
    $(".monthrange").daterangepicker({
        autoUpdateInput: false,
        showDropdowns: true, 
        linkedCalendars: false,
        locale: {
            "format": 'MMM YYYY',
            "separator": " To ",
            "cancelLabel": 'Clear'
        } 
    }).on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('MMM YYYY') + ' - ' + picker.endDate.format('MMM YYYY'));
    }).on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });
    
    $(".daterange").daterangepicker({
        autoUpdateInput: false,
        showDropdowns: true,
        linkedCalendars: false,
        locale: {
            format: 'DD-MMM-YYYY',
            separator: " To ",
            cancelLabel: 'Clear'
        }
    }).on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('DD-MMM-YYYY') + ' to ' + picker.endDate.format('DD-MMM-YYYY'));
    }).on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });
});

$('#filterForm').submit( function( event ) {
        event.preventDefault();

        //validate fields
        var fail = false;
        var data = [];
        var name;
        $( '#filterForm' ).find( 'select, textarea, input' ).each(function(){
            var fail_log = '';
            $("#"+$(this).attr("id")+"_error").html("");
            if ( $( this ).attr('mandatory') == 1 && $(this).val() == '') {
                fail = true;
                name = $( this ).attr( 'label' );
                fail_log += name + " is required \n";
                $("#"+$(this).attr("id")+"_error").html(fail_log);
            }
            data.push({
                "name": $(this).attr("id"),
                "label":$(this).attr("label"),
                "value": $(this).val(),
                "type": $(this).attr("typeOffield"),
                "format": $(this).attr("date-format")
            });
            
        });

        //submit if fail never got set to true
        if ( ! fail ) {
            $.redirect("/report/"+trackerId+"/"+formId+"/"+reportId, { "data": data});
        }
});