$(document).ready(function() {
    $('#list_of_trackers').dataTable( {
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

$(document.body).on('change','#c_select_client', function(){
    var clientName = $("#c_select_client").val();
    $("#forClientName").html("");
    if(clientName == null || clientName == ''){
        $("#forClientName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','customer name'));
    }
});
$(document.body).on('change','#c_tracker', function(){
    var trackerName = $("#c_tracker").val();
    $("#forTrackerName").html("");
    if(trackerName == null || trackerName == ''){
        $("#forTrackerName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','tracker name'));
    } else if (!trackerName.match(/^[a-zA-Z0-9 ]+$/)) {
       $("#forTrackerName").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','tracker name'));
    } else if (trackerName.length > 200) {
       $("#forTrackerName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','tracker name').replace('#char','200')); 
    }
});

$(document.body).on('change','#reason', function(){
    var reason = $("#reason").val();
    $("#forReason").html('');
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    } 
});


function addEditTracker() {
    var clientName = $("#c_select_client").val();
    var trackerName = $("#c_tracker").val();
    var reason = $("#reason").val();
    var trackerId = $("#c_hidden").val();
    
    $("#forClientName").html('');
    $("#forTrackerName").html('');
    $("#forReason").html('');
    $("#trackerErrorMessages").html('');
    
    var count = 0;
    
    if (trackerId == 0) {
        if(clientName == null || clientName == ''){
            $("#forClientName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','customer name'));
            count++;
        }
    }
    
    if(trackerName == null || trackerName == ''){
        $("#forTrackerName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','tracker name'));
        count++;
    } else if (!trackerName.match(/^[a-zA-Z0-9 ]+$/)) {
       $("#forTrackerName").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','tracker name'));
        count++; 
    } else if (trackerName.length > 200) {
       $("#forTrackerName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','tracker name').replace('#char','200'));
       count++;
    }
    
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
        count++;
    }
    if (count == 0) {
        var data = {
            clientId: clientName,
            trackerName : trackerName,
            trackerId:trackerId,
            reason:reason
            };
        var url = "/tracker/saveUpdateTracker/"+trackerId;
        $.post(url, data,function(respJson){
            var resp = JSON.parse(respJson);
            //console.log(resp);
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            if((responseCode == 1 || responseCode == 2) && (responseCode != null)){
                window.location.assign('/tracker/tracker_management');
            }
            else{
                $("#trackerErrorMessages").html(errMessage);
            }
        });
    }
}

function deleteTrackerAction(id) { 
    var reason = $("#reason_"+id).val();
    $('#forReason_'+id).html("");
    $('#trackerErrorMessages').html("");
    if (reason == null || reason == '') {
        $('#forReason_'+id).html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    } else {
        $.ajax({
            url: "/tracker/delete/"+id,
            type:'post',
            //dataType:'json',
            data:{'trackerId':id,'comment':reason},
            success:function(data) {
                var resp = JSON.parse(data);
                var responseCode = resp.responseCode;
                if (responseCode == 0) {
                    $('#trackerErrorMessages').html("Due to some error could not able to delete tracker."); 
                } else if (responseCode == 1) {
                    window.location.assign('/tracker/tracker_management'); 
                } else if (responseCode == 2) {
                    window.location.reload();
                }
            }
         });
    }
}

function reloadPopUp(id) {
   $('#reason_'+id).val(""); 
   $('#forReason_'+id).html(""); 
}