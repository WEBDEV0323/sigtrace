/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
allVals = [];
/*
 * Function to check input fields validations
 */
function addNewClient(clientid){
    var valid = $("#clientform").valid();
    if(!valid) {
        return false;
    }
    if(clientid>0)
    {
        $('#addcommentasreason').modal('show');
        $('#commenterror').hide();
    }else{
        $('#commenterror').hide();
        document.getElementById("clientform").submit();
    }
}

function addClient(){
    if ($('#addcomment').val() == ''){
        $('#commenterror').show();
        return false;
    }
    $('#commenterror').hide();
    document.getElementById("clientform").submit();
}
/*
 * Function to delete client :to make client archive
 */
function deleteClient(clientId)
{
    $('#deletecommentasreason').modal('show');
        $("#reasonfordelete").click(function(){
            $('#commenterrorfordelete').hide();
            if ($('#addcommentfordelete').val() == ''){
                $('#commenterrorfordelete').show();
                return false;
            }
            else {
                $.ajax({
                    url: "/client/deleteclient/"+clientId,
                    type:'post',
                    dataType:'json',
                    data:{'clientid':clientId,'comment':$('#addcommentfordelete').val()},
                    success:function(data) {
                        if(data=='deleted')
                        {
                            window.location.assign('/client');
                        }
                    }
                })
            }
        });
}
$(document).ready(function() {
    $('#list_notifications').dataTable( {
        "bDestroy": true,
        "bScrollInfinite": true,
        "bScrollCollapse": true,
        //  "scrollX":   true,
        "paging":         true,
        "aLengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]], 
        'order':[],
        columnDefs: [
            { orderable: false, targets: [0,0] }
        ]
    } );
} );

/*
 * Function to check input fields validations:add tracker
 */
function addTracker(){
    if ($('#addcomment').val() == ''){
        $('#commenterror').show();
        return false;
    }
    $('#commenterror').hide();
    document.getElementById("clientform").submit();
}



function addtrackerforclient(id)
{
    $("#c_flag").val('1');
    addNewClient(id);
}
/*
 * Function to delete tracker :to make tracker archive
 */

function deleteTracker(trackerId)
{
    $('#deletecommentasreason').modal('show');
        $("#reasonfordelete").click(function(){
            $('#commenterrorfordelete').hide();
            if ($('#addcommentfordelete').val() == ''){
                $('#commenterrorfordelete').show();
                return false;
            }
            else {
                    $.ajax({
                        url: "/client/deletetracker",
                        type:'post',
                        dataType:'json',
                        data:{'trackerId':trackerId,'comment':$('#addcommentfordelete').val()},
                        success:function(data) {
                            if(data=='deleted')
                            {
                                window.location.assign('/client/viewtracker');
                            }
                        }
                    })
                }
            });

}