$(document).ready(function() {

    $('option[value=""]').attr("disabled", "disabled");

    $('#list_of_trigger').dataTable( {
            "bDestroy": true,
            "bScrollInfinite": true,
            "bScrollCollapse": true,
            "paging":         true,
            "aLengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
            "order": [],
            "columnDefs": [
                { targets: 0, orderable: false }
            ]
    });
    
    setTimeout(function(){$('#fashMessage').remove();}, 3000);
});

$(document.body).on('change','#c_name', function(){
    var triggerName = $("#c_name").val();
    $("#forTriggerName").html('');
    if(triggerName == null || triggerName == ''){
        $("#forTriggerName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','trigger name'));
    } else if (!triggerName.match(/^[a-zA-Z0-9 ]+$/)) {
       $("#forTriggerName").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','trigger name'));
    } else if (triggerName.length > 200) {
       $("#forTriggerName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','trigger name').replace('#char','200')); 
    }
});

$(document.body).on('change','#trigger_when', function(){
    var triggerWhen = $("#trigger_when").val();
    $("#forTriggerWhen").html('');
    if(triggerWhen == null || triggerWhen == ''){
        $("#forTriggerWhen").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','trigger when'));
    } 
});

$(document.body).on('change','#trigger_then', function(){
    var triggerThen = $("#trigger_then").val();
    $("#forTriggerThen").html('');
    if(triggerThen == null || triggerThen == ''){
        $("#forTriggerThen").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','trigger then'));
    } 
});

$(document.body).on('change','#source', function(){
    var triggerSource = $("#source").val();
    $("#forSource").html('');
    if(triggerSource == null || triggerSource == ''){
        $("#forSource").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','source'));
    } 
});

$(document.body).on('change','#destination', function(){
    var triggerDestination = $("#destination").val();
    $("#forDestination").html('');
    if(triggerDestination == null || triggerDestination == ''){
        $("#forDestination").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','destination'));
    } 
});

$(document.body).on('change','#when_conditions', function(){
    var triggerCondition = $("#when_conditions").val();
    $("#forWhenCondition").html('');
    if(triggerCondition == null || triggerCondition == ''){
        $("#forWhenCondition").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','when conditions'));
    } 
});

$(document.body).on('change','#fields_to_copy', function(){
    var triggerFieldCopy = $("#fields_to_copy").val();
    $("#forFieldsToCopy").html('');
    if(triggerFieldCopy == null || triggerFieldCopy == ''){
        $("#forFieldsToCopy").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','fields to copy'));
    } 
});

$(document.body).on('change','#reason', function(){
    var reason = $("#reason").val();
    $("#forReason").html('');
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    } 
});


function addEditTrigger(triggerId, trackerId) {
    var triggerName = $("#c_name").val();
    var triggerWhen = $("#trigger_when").val();
    var triggerThen = $("#trigger_then").val();
    var triggerSource = $("#source").val();
    var triggerDestination = $("#destination").val();
    var triggerCondition = $("#when_conditions").val();
    var triggerFieldCopy = $("#fields_to_copy").val();
    var reason = $("#reason").val();
    
    $("#forTriggerName").html('');
    $("#forReason").html('');
    $("#triggerErrorMessages").html('');
    
    var count = 0;
    
    if(triggerName == null || triggerName == ''){
        $("#forTriggerName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','trigger name'));
        count++;
    } else if (!triggerName.match(/^[a-zA-Z0-9 ]+$/)) {
       $("#forTriggerName").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','trigger name'));
        count++; 
    } else if (triggerName.length > 200) {
       $("#forTriggerName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','trigger name').replace('#char','200')); 
    }
    
    if(triggerWhen == null || triggerWhen == ''){
        $("#forTriggerWhen").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','trigger when'));
        count++;
    }

    if(triggerThen == null || triggerThen == ''){
        $("#forTriggerThen").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','trigger then'));
        count++;
    }

    if(triggerSource == null || triggerSource == ''){
        $("#forSource").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','source'));
        count++;
    }

    if(triggerDestination == null || triggerDestination == ''){
        $("#forDestination").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','destination'));
        count++;
    }

    if(triggerCondition == null || triggerCondition == ''){
        $("#forWhenCondition").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','when conditions'));
        count++;
    }

    if(triggerFieldCopy == null || triggerFieldCopy == ''){
        $("#forFieldsToCopy").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','fields to copy'));
        count++;
    }

    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
        count++;
    }

    if (count == 0) {
        var data = {
            c_name : triggerName,
            trigger_when : triggerWhen,
            trigger_then : triggerThen,
            source : triggerSource,
            destination : triggerDestination,
            when_conditions : triggerCondition,
            fields_to_copy : triggerFieldCopy,
            triggerId : triggerId,
            trackerId:trackerId,
            reason:reason
            }
        var url = "/triggerform/addUpdate/"+trackerId+"/"+triggerId;
        $.post(url, data,function(respJson){
            var resp = JSON.parse(respJson);console.log(resp);
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            if((responseCode == 1 || responseCode == 2) && (responseCode != null)){
                window.location.assign('/triggerform/trigger_form/'+trackerId);
            }
            else{
                $("#triggerErrorMessages").html(errMessage);
            }
        });
    }
}

function deleteTriggerAction(id,tid) { 
    var reason = $("#reason_"+id).val();
    $('#forReason_'+id).html("");
    $('#triggerErrorMessages').html("");
    if (reason == null || reason == '') {
        $('#forReason_'+id).html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    } else {
        $.ajax({
            url: "/triggerform/delete/"+tid+"/"+id,
            type:'post',
            dataType:'json',
            data:{'trigger_id':id,'tracker_id':tid,'comment':reason},
            success:function(data) {
                if(data == 'deleted')
                {
                    window.location.assign('/triggerform/trigger_form/'+tid);
                } else if (data == 'error') {
                    $('#triggerErrorMessages').html("Due to some error could not able to delete trigger form.");
                }
            }
         })
    }
}

function reloadPopUp(id) {
   $('#reason_'+id).val(""); 
   $('#forReason_'+id).html(""); 
}

