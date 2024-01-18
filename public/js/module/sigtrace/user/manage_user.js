/**
 * Created by saritatewari on 12/11/15.
 */

$("#Userform").validate();
$(document).ready(function() {
    $('#usertable').dataTable( {
        "bDestroy": true,
        "bScrollInfinite": true,
        "bScrollCollapse": true,
        //  "scrollX":   true,
        "paging":         true,
        "aLengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]]
    } );

});
/*
 * Function to delete user :to make user archive
 */

function deleteuser(id,tid)
{
    if( confirm("Are you sure you want to delete this user?"))
    {
        //            var id=$(this).closest('tr').attr("id");
        $.ajax({
            url: "/deleteuser",
            type:'post',
            dataType:'json',
            data:'user_id='+id+'&tracker_id='+tid,
            success:function(data) {
                if(data=='deleted')
                {
                    //alert("ds");
                    window.location.assign('/manage_user/'+tid);
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
