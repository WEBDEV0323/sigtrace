/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
function  addWorkflowRoleId(roleId,workflowId){   
    var Read=$('input[name="Read_'+roleId+'_'+workflowId+'"]:checked').val();
    var Insert=$('input[name="Insert_'+roleId+'_'+workflowId+'"]:checked').val();
    var Update=$('input[name="Update_'+roleId+'_'+workflowId+'"]:checked').val();
    var Delete=$('input[name="Delete_'+roleId+'_'+workflowId+'"]:checked').val()
      
    $.ajax({
        url: "/tracker/addWorkflowRoleId",
        type:'post',
        // dataType:'json',
        data:'roleId='+roleId+'&workflowId='+workflowId+'&Read='+Read+'&Insert='+Insert+'&Update='+Update+'&Delete='+Delete,
        success:function(data) {
            $('span[id="result'+roleId+'__'+workflowId+'"]').html('Row '+data+'.').css('color','green');
           
        }
    })
}
/*
 * Function to check input fields validations
 */
function addNewGroup(){
    if ($('#addcomment').val() == ''){
        $('#commenterror').show();
        return false;
    }
    $('#commenterror').hide();
    $("#status_for_reason").html('processing...');
    document.getElementById("Groupform").submit();
}

/*
 * Function to check input fields validations
 */
function addcomment(){
    var valid = $("#Groupform").valid();
    if(!valid) {
        $('.error').show();
        return false;
    }
    else{
        $('#addcommentasreason').modal('show');
    }
    document.getElementById("Groupform").submit();
}

/*
 * Function to delete group :to make group archive
 */

function deletegroup(group_id, tracker_id)
{
    $('#deletecommentasreason').modal('show');
    $('#addcommentfordelete').val('');
    $("#reasonfordelete").click(function () {
        $('#commenterrorfordelete').hide();
        if ($('#addcommentfordelete').val() == '') {
            $('#commenterrorfordelete').show();
            return false;
        } else {
            $.ajax({
                url: "/user/deleteGroup",
                type: 'post',
                dataType: 'json',
                data: 'group_id=' + group_id + '&tracker_id=' + tracker_id+'&comment='+$('#addcommentfordelete').val(),
                success: function (data) {
                    if (data == 'deleted')
                    {
                        window.location.assign('/user/manage_group/' + tracker_id);
                    }
                }
            })
            return false;
        }
    });


}
/*
 * Function to check input fields validations
 */
function addNewuser(){
    var valid = $("#Userform").valid();
    if(!valid) {
        return false;
    }
    document.getElementById("Userform").submit();
}


/*
 * Function to delete user :to make user archive
 */
function deleteuser(id,tid,uname)
{
    $('#deletecommentasreason').modal('show');
    $("#reasonfordelete").click(function(){
        $('#commenterrorfordelete').hide();
        if ($.trim($('#addcommentfordelete').val()) == '' || ($('#addcommentfordelete').val()).length <= 5){
            $('#commenterrorfordelete').show();
            return false;
        }
        else if(/[a-zA-Z0-9]/.test($.trim($('#addcommentfordelete').val())) === false){
            $('#commenterrorfordelete').show();
            return false;
        }
        else {
            $('#commenterrorfordelete').hide();
            $.ajax({
                url: "/user/deleteuser/"+tid+"/"+id,
                type:'post',
                dataType:'json',
                data:{'user_id':id,'tracker_id':tid,'user_name':uname,'comment':$('#addcommentfordelete').val()},
                success:function(data) {
                    if(data=='deleted')
                    {
                        window.location.assign('/user/manage_user/'+tid);
                    }
                }
            })
        }
    });
}