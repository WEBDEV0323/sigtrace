

//$('.selectpicker').on('change', function () {
//    var $el = $(':focus');
//    $(this).blur();
//    $el.focus();
//});

$(document.body).on('change','#u_name', function(){
    var uname = $("#u_name").val();
    $("#forUserId").html('');
    if(uname == null || uname == ''){
        $("#forUserId").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','username'));
    }else if (!uname.match(/^[a-zA-Z0-9\.]+$/)) {
       $("#forUserId").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','username'));
    } else if (uname.length > 200) {
       $("#forUserId").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','username').replace('#char','200')); 
    }
});

$(document.body).on('change','#email', function(){
    var email = $("#email").val();
    $("#forEmail").html('');
    if(email == null || email == ''){
        $("#forEmail").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','user email'));
    }else if (!email.match(/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/)) {
       $("#forEmail").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','user email'));
    } else if (email.length > 200) {
       $("#forEmail").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','user email').replace('#char','200')); 
    }
});

$(document.body).on('change',"#role_id", function(){
    var roleId = $("#role_id").val();
    $("#forRoleId").html('');
    if(roleId == null || roleId == ''){
        $("#forRoleId").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','role name'));
    }
});

$(document.body).on('change','#reason', function(){
    var reason = $("#reason").val();
    $("#forReason").html('');
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    }
});

function addNewUser(userId) {

    var uname = $("#u_name").val();
    var roleId = $("#role_id").val();
    var reason = $("#reason").val();
    var trackerId=$("#t_hidden").val();
    var email = $("#email").val();
    
    var status = 0;
    var archived = 0;
    
    $("#forUserId").html('');
    $("#forRoleId").html('');
    $("#forReason").html('');
    $("#forStatus").html('');
    $("#forEmail").html('');
    $("#userErrorMessages").html('');
    
    var count = 0;
    if(uname == null || uname == ''){
        $("#forUserId").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','username'));
        count++;
    } else if (!uname.match(/^[a-zA-Z0-9\.]+$/)) {
       $("#forUserId").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','username'));
        count++; 
    } else if (uname.length > 200) {
       $("#forUserId").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','username').replace('#char','200')); 
    }
    
    
    if(email == null || email == ''){
        $("#forEmail").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','user email'));
        count++;
    }else if (!email.match(/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/)) {
       $("#forEmail").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','user email'));
       count++;
    } else if (email.length > 200) {
       $("#forEmail").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','user email').replace('#char','200'));
       count++;
    }
    
    
    if(roleId == null || roleId == ''){
        $("#forRoleId").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','role name'));
        count++;
    }
    
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
        count++;
    }
    
    if(userId > 0)
    {
        var status = $('#c_status').val();
        
        if(status == null || status == ''){
            $("#forStatus").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','status'));
            count++;
        }
    }
    
    if (count == 0) {
        var data = {
            u_name : uname,
            email : email,
            role_id : roleId,
            user_id:userId,
            status:status,
            archived:archived,
            t_hidden:trackerId,
            tracker_name:trackerName,
            reason:reason
            }
        var url = "/user/userCheck/"+trackerId;
        $.post(url, data,function(respJson){
            var resp = JSON.parse(respJson);
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            if((responseCode == 1 || responseCode == 2) && (responseCode != null)){
                window.location.assign('/user/user_management/'+trackerId);
            }
            else{
                $("#userErrorMessages").html(errMessage);
            }
        });
    }
}