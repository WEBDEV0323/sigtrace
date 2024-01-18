function validateEmail(sEmail) {

    var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
    if (filter.test(sEmail)) {
        return true;
    }
    else {
        return false;
    }
}


function addNewUser(user_id){
   // console.log(ccc);

    var $valid = $("#commentForm").valid();
    if(!$valid) {
        return false;
    }else{
        var u_name = $("#u_name").val();
        var role_id = $("#role_id").val();
        if(user_id>0){
            var type=user_type;
        }
        else{
            var type=$("#user_type").val();
            if(type == 0){
                $("#status").html('<font color="#cc0000">Select type</font>');
                return false;
            }
            var res= u_name.split("@");
            if(domain==res[1]&& type!='LDAP' ){
                $("#status").html('<font color="#cc0000">Kindly select user type as LDAP</font>');
                return false;
            }
            if(type=='Normal'){
                if (!validateEmail(u_name)) {
                    $("#status").html('<font color="#cc0000">Email is invalid</font>');
                    return false;
                }
            }
        }
        if(role_id == null){
            $("#status").html('<font color="#cc0000">Select groups</font>');
            return false;
        }
        var tracker_id=$("#t_hidden").val();
        if(user_id>0){
            var status = $("#c_status").val();
            var archived = $("#c_archive").val();
        }else{
            var status = 0;
            var archived = 0;
        }

        $("#status").html('processing...');

        var data = {
            u_name : u_name,
            role_id : role_id,
            user_id:user_id,
            status:status,
            type:type,
            archived:archived,
            t_hidden:tracker_id,
            tracker_name:tracker_name
            //                tracker_id: tracker_id
        }
        var url = "<?php echo $this->url('user', array('action' => 'userCheck')); ?>";
        $.post(url, data,function(respJson){
            var resp =JSON.parse(respJson);
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            var html = "";
            if(responseCode == 1 || responseCode == 2){
                window.location.assign('/manage_user/'+tracker_id);
                // $("#status").html('<font color="#088A08">'+errMessage+'</font>');

            }
            else{
                $("#status").html('<font color="#cc0000">'+errMessage+'</font>');
            }
        });
        return false;
    }
}
