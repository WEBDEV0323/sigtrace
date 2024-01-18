/**
 * Created by saritatewari on 16/11/15.
 */
//$(document).ready(function() {
    $("#trackerform").validate();
//});

/*
 * Function to check input fields validations:add tracker
 */
function addTracker(){
    var flag=0;
    var re=/[a-zA-Z]/i;
    $("#error_tracker").hide();
    $("#error_tracker").html("");
    if(!re.test($("#c_tracker").val())) {
        $("#error_tracker").show();
        $("#error_tracker").html("Kindly enter atleast one character.");
        flag=1;
    }
    if(flag==1) {
        return false;
    }
        var valid = $("#trackerform").valid();
        if (!valid) {
            $('.error').show();
            return false;
        }
    document.getElementById("trackerform").submit();
}



function addtrackerforclient(id)
{

        window.location.assign('/tracker/addtracker/'+id);

}


/*
 * Function to delete tracker :to make tracker archive
 */

function deleteTracker(trackerId)
{
    if( confirm("Are you sure you want to delete this tracker?"))
    {
        $.ajax({
            url: "/tracker/deletetracker",
            type:'post',
            dataType:'json',
            data:'trackerId='+trackerId,
            success:function(data) {
                if(data=='deleted')
                {
                    window.location.assign('/tracker/viewall');
                }
            }
        });

        return false;
    }
}







