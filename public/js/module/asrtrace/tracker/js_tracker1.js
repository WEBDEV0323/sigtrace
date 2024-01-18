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
    var valid = $("#Groupform").valid();
    if(!valid) {
        return false;
    }
    document.getElementById("Groupform").submit();
}

/*
 * Function to delete group :to make group archive
 */

function deletegroup(group_id)
{
    if( confirm("Are you sure you want to delete this user?"))
    {
        //            var id=$(this).closest('tr').attr("id");
        $.ajax({
            url: "/user/deleteGroup",
            type:'post',
            dataType:'json',
            data:'group_id='+group_id,
            success:function(data) {
                if(data=='deleted')
                {
                    window.location.assign('/user/manage_group');
                }
            }
        })

        return false;
    }

 
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

function deleteuser(id,tid)
{
    if( confirm("Are you sure you want to delete this user?"))
    {
        //            var id=$(this).closest('tr').attr("id");
        $.ajax({
            url: "/user/deleteuser",
            type:'post',
            dataType:'json',
            data:'user_id='+id,
            success:function(data) {
                if(data=='deleted')
                {
                    window.location.assign('/user/manage_user/'+tid);
                }
            }
        })

        return false;
    }

 
}