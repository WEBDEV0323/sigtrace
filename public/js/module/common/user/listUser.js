$(document).ready(function() {
    $('#list_of_users').dataTable( {
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

function deleteUserAction(id,tid,uname) { 
    var reason = $("#reason_"+id).val();
    $('#forReason_'+id).html("");
    $('#userErrorMessages').html("");
    if (reason == null || reason == '') {
        $('#forReason_'+id).html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    } else {
        $.ajax({
            url: "/user/deleteuser/"+tid+"/"+id,
            type:'post',
            dataType:'json',
            data:{'user_id':id,'tracker_id':tid,'user_name':uname,'comment':reason},
            success:function(data) {
                if(data == 'deleted')
                {
                    window.location.assign('/user/user_management/'+tid);
                } else if (data == 'error') {
                    $('#userErrorMessages').html("Due to some error could not able to delete tracker.");
                }
            }
         })
    }
}

function reloadPopUp(id) {
   $('#reason_'+id).val(""); 
   $('#forReason_'+id).html(""); 
}