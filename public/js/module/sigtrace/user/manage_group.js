/**
 * Created by saritatewari on 12/11/15.
 */


$(document).ready(function() {
    $("#Groupform").validate();
    $('#grouptable').dataTable( {
        "bDestroy": true,
        "bScrollInfinite": true,
        "bScrollCollapse": true,
        //  "scrollX":   true,
        "paging":         false,
        "aLengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]]
    } );

});

/*
 * Function to check input fields validations
 */
function addNewGroup(type){
    var re=/[a-zA-Z]/i;
    var flag=0;
    $("div#error_group.errordiv").html("").hide();
    if(!re.test($("#c_name").val()) && ($("#c_name").val() != '')) {
        $("div#error_group.errordiv").show().html("Kindly enter atleast one character.");
        flag=1;
    }
    if(flag==1) {
        return false;
    }
    var valid = $("#Groupform").valid();
    if(!valid) {
        $('.error').show();
        return false;
    }
    if(type == 'submit') {
        document.getElementById("Groupform").submit();
    }
}

/*
 * Function to delete group :to make group archive
 */

function deletegroup(group_id,tracker_id)
{
    if( confirm("Are you sure you want to delete this group?"))
    {
        //            var id=$(this).closest('tr').attr("id");
        $.ajax({
            url: "/deleteGroup",
            type:'post',
            dataType:'json',
            data:'group_id='+group_id,
            success:function(data) {
                if(data=='deleted')
                {
                    window.location.assign('/manage_group/'+tracker_id);
                }
            }
        })

        return false;
    }


}