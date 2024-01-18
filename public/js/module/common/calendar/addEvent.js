

//$('.selectpicker').on('change', function () {
//    var $el = $(':focus');
//    $(this).blur();
//    $el.focus();
//});
$(function () {
    $('#dateRange').daterangepicker({
         dateFormat: 'DD-MMM-YYYY',
         linkedCalendars: false,
         //beforeShowDay: disableDates
     autoUpdateInput: false //disable default date
//        showDropdowns: true
    });
    $('#dateRange').on('apply.daterangepicker', function(ev, picker) {
        if(isNaN(Date.parse(picker.startDate.format('DD-MMM-YYYY'))) || isNaN(Date.parse(picker.endDate.format('DD-MMM-YYYY')))) {
            $("#forDateRange").html(messageJSON.MSG_DATE_EMPTY.replace('#fieldName','Start Date and End Date'));        
        } else {
            $('#dateRange').val( picker.startDate.format('DD-MMM-YYYY') +' / '+picker.endDate.format('DD-MMM-YYYY') );   
            $("#forDateRange").html('');
        }
    });
    
});
$(document.body).on('change','#e_name', function(){
    var ename = $("#e_name").val();
    $("#forEventName").html('');
    if(ename === null || ename === ''){
        $("#forEventName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Event Name'));
    } else if (ename.length > 200) {
       $("#forEventName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Event Name').replace('#char','200')); 
    } else {
        $("#forEventName").html('');
    }
});


$(document.body).on('change',"#eventData", function(){
    var eventData = $("#eventData").val();
    $("#forEventData").html('');
    if(eventData === null || eventData === ''){
        $("#forEventData").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Description'));
    } else if (!eventData.match(/^[a-zA-Z0-9 '.]+$/)) {
       $("#forEventData").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Description').replace('#char','200')); 
       count++;
    } else if (eventData.length > 200) {
       $("#forEventData").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Description').replace('#char','200')); 
    } else {
        $("#forEventData").html('');
    }
});

$(document.body).on('change','#reason', function(){
    var reason = $("#reason").val();
    $("#forReason").html('');
    if(reason === null || reason === ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
    } else {
        $("#forReason").html('');
    }
});
    var count = 0;
function addNewEvent (trackerId,formId) {
    var ename = $("#e_name").val();
    var eventData = $("#eventData").val();
    var dateRange = $("#dateRange").val().split('/'); 
    var sDate = moment(dateRange[0]).format("YYYY-MM-DD"); 
    var eDate = moment(dateRange[1]).format("YYYY-MM-DD");
    var reason = $("#reason").val();    
    $("#forEventName").html('');
    $("#forEventData").html('');
    $("#forDateRange").html('');
    $("#forReason").html('');
    count = 0;

    if(ename === null || ename === '') {
        $("#forEventName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Event Name'));
        count++;
    } else if (ename.length > 200) {
       $("#forEventName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Event Name').replace('#char','200')); 
       count++;
    } else {
        $("#forEventName").html('');
    }
    
    if(isNaN(Date.parse(sDate)) || isNaN(Date.parse(eDate))) {
        $("#forDateRange").html(messageJSON.MSG_DATE_EMPTY.replace('#fieldName','Start Date and End Date'));     
        count++;
    } else {
        $("#forDateRange").html('');
    }
    
    if(eventData === null || eventData === '') {
        $("#forEventData").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Description'));
        count++;
    } else if (!eventData.match(/^[a-zA-Z0-9 '.]+$/)) {
       $("#forEventData").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Description').replace('#char','200')); 
       count++;
    } else if (eventData.length > 200) {
       $("#forEventData").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Description').replace('#char','200')); 
       count++;
    } else {
        $("#forEventData").html('');
    }
    
    if(reason === null || reason === '') {
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        count++;
    } else {
        $("#forReason").html('');
    }
    
    if (count === 0) { 
        var data = {                       
            event_id : ename,
            event_data : eventData,                        
            start_date : sDate,
            end_date : eDate,
            reason : reason
            };
    var url = "/calendar/saveNewEvent/"+trackerId+'/'+formId;
        $.post(url, data,function(respJson){
            var resp = JSON.parse(respJson);
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            if(responseCode === 1 && responseCode !== null){
                window.location.assign('/calendar/events_list/'+trackerId+'/'+formId);
            } else {
                 //window.location.assign('/calendar/add/'+trackerId);          
                 $('#eventErrorMessages').html('<div class="contents boxpadding"><div class="alert alert-dismissable alert-danger" id="fashMessage">'+errMessage+'<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div></div>');
            }
        });
    }
}

function saveEditEvent (trackerId,formId,id) {
    var ename = $("#e_name").val();       
    var eventData = $("#eventData").val();
    var dateRange = $("#dateRange").val().split('/');
    var sDate = moment(dateRange[0]).format("YYYY-MM-DD");
    var eDate = moment(dateRange[1]).format("YYYY-MM-DD");
    var reason = $("#reason").val();    
    $("#forEventName").html('');
    $("#forEventData").html('');
    $("#forDateRange").html('');
    $("#forReason").html('');
    
    var count = 0;
    if(ename === null || ename === '') {
        $("#forEventName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Event Name'));
        count++;
    } else if (ename.length > 200) {
       $("#forEventName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Event Name').replace('#char','200')); 
       count++;
    } else {
        $("#forEventName").html('');
    } 
           
    if(eventData === null || eventData === '') {
        $("#forEventData").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Description'));
        count++;
    } else if (eventData.length > 200) {
       $("#forEventData").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Description').replace('#char','200')); 
       count++;
    } else if (!eventData.match(/^[a-zA-Z0-9 '.]+$/)) {
       $("#forEventData").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Description').replace('#char','200')); 
       count++;
    } else {
        $("#forEventData").html('');
    }
    
     if(isNaN(Date.parse(sDate)) || isNaN(Date.parse(eDate))) {
        $("#forDateRange").html(messageJSON.MSG_DATE_EMPTY.replace('#fieldName','Start Date and End Date'));      
        count++;
    } else {
        $("#forDateRange").html('');
    }
    
    if(reason === null || reason === '') {
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        count++;
    } else {
        $("#forReason").html('');
    } 
   
    if (count === 0) {
        var data = {
            id : id,
            event_id : ename,
            event_data : eventData,                                   
            start_date : sDate,
            end_date : eDate,           
            reason : reason
            };
        var url = "/calendar/saveEditEvent/"+trackerId;
        $.post(url, data,function(respJson){
            var resp = JSON.parse(respJson);
            var responseCode = resp.responseCode;
             var errMessage = resp.errMessage;
            if(responseCode === 1 && responseCode !== null){
                window.location.assign('/calendar/events_list/'+trackerId+'/'+formId);
            } else {
                $('#editEventErrorMessages').html('<div class="contents boxpadding"><div class="alert alert-dismissable alert-danger" id="fashMessage">'+errMessage+'<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button></div></div>');
            }
        });
    }
}
