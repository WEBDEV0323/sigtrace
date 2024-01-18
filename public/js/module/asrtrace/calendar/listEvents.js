/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
    // specify the columns agNumberColumnFilter

  

$(document).ready(function() {
    setTimeout(function(){$('#fashMessage').remove();}, 3000);
});
function deleteEventAction(id,tid,fid) { 
    var reason = $("#reason").val();
    $('#forReason').html("");
    $('#userErrorMessages').html("");
    if (reason === null || reason === '') {
        $('#forReason').html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
    } else {
        $.ajax({
            url: "/calendar/delete_event",
            type:'post',
            dataType:'json',
            data:{'event_id':id,'tracker_id':tid,'comment':reason},
            success:function(resp) {
            var responseCode = resp.responseCode;
            if(responseCode === 1  && responseCode !== null) {
                window.location.assign('/calendar/events_list/'+tid+'/'+fid);
            }
            else{
                window.location.assign('/calendar/events_list/'+tid+'/'+fid);
            }
        }
         });
    }
}

function reloadPopUp(id,tid,fid) {
   $('#reason').val(""); 
   $('#forReason').html("");
   $('#reasonfordelete').attr('onclick','deleteEventAction('+id+','+tid+','+fid+')');
   $('#deleteEvent').modal('show');
}