$(document).ready(function() {
    $('#list_of_roles').dataTable( {
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
    var roleName = $("#c_name").val();
    $("#forRoleName").html('');
    if(roleName == null || roleName == ''){
        $("#forRoleName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','role name'));
    } else if (!roleName.match(/^[a-zA-Z0-9 ]+$/)) {
       $("#forRoleName").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','role name'));
    } else if (roleName.length > 200) {
       $("#forRoleName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','role name').replace('#char','200')); 
    }
});

$(document.body).on('change','#reason', function(){
    var reason = $("#reason").val();
    $("#forReason").html('');
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    } 
});


function addEditRole(roleId, trackerId) {
    var roleName = $("#c_name").val();
    var reason = $("#reason").val();
    
    $("#forRoleName").html('');
    $("#forReason").html('');
    $("#roleErrorMessages").html('');
    
    var count = 0;
    
    if(roleName == null || roleName == ''){
        $("#forRoleName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','role name'));
        count++;
    } else if (!roleName.match(/^[a-zA-Z0-9 ]+$/)) {
       $("#forRoleName").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','role name'));
        count++; 
    } else if (roleName.length > 200) {
       $("#forRoleName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','role name').replace('#char','200')); 
    }
    
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
        count++;
    }
    if (count == 0) {
        var data = {
            c_name : roleName,
            roleId : roleId,
            trackerId:trackerId,
            reason:reason
            }
        var url = "/role/addUpdate/"+trackerId+"/"+roleId;
        $.post(url, data,function(respJson){
            var resp = JSON.parse(respJson);console.log(resp);
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            if((responseCode == 1 || responseCode == 2) && (responseCode != null)){
                window.location.assign('/role/role_management/'+trackerId);
            }
            else{
                $("#roleErrorMessages").html(errMessage);
            }
        });
    }
}

function deleteRoleAction(id,tid) { 
    var reason = $("#reason_"+id).val();
    $('#forReason_'+id).html("");
    $('#roleErrorMessages').html("");
    if (reason == null || reason == '') {
        $('#forReason_'+id).html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    } else {
        $.ajax({
            url: "/role/delete/"+tid+"/"+id,
            type:'post',
            dataType:'json',
            data:{'role_id':id,'tracker_id':tid,'comment':reason},
            success:function(data) {
                if(data == 'deleted')
                {
                    window.location.assign('/role/role_management/'+tid);
                } else if (data == 'error') {
                    $('#roleErrorMessages').html("Due to some error could not able to delete role.");
                }
            }
         })
    }
}

function reloadPopUp(id) {
   $('#reason_'+id).val(""); 
   $('#forReason_'+id).html(""); 
}