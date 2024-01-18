/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
allVals = [];
/*
 * Function to check input fields validations
 */
function validateCustomer(customerId){
    
    var count = 0;
    $("#customerErrorMessages").html("");
    var name = $.trim($("#c_name").val());
    var email = $.trim($("#c_email").val());
    var pmName = $.trim($("#c_proj_manager_name").val());
    var description = $.trim($("#c_description").val());
    var country = $.trim($("#c_country").val());
    var reason = $.trim($("#addreason").val());
    
    if(name == null || name == ''){
        $("#errorForName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Customer Name'));
        count++;
    } else if (!name.match(/^[a-zA-Z0-9 ]+$/i)) {
       $("#errorForName").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Customer Name'));
        count++; 
    } else if (name.length > 200) {
       $("#errorForName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Customer Name').replace('#char','200'));
       count++;
    } else {
        $("#errorForName").html('');
    }
    
    if(email == null || email == ''){
        $("#errorForEmail").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Customer Email address'));
        count++;
    } else if (!email.match(/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/)) {
        $("#errorForEmail").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Customer Email address'));
        count++;
    } else if (email.length > 200) {
       $("#errorForEmail").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Customer Email address').replace('#char','200'));
       count++;
    } else {
        $("#errorForEmail").html('');
    }
    
    if(pmName == null || pmName == ''){
        $("#errorForPM").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Project Manager Name'));
        count++;
    } else if (!pmName.match(/^[a-zA-Z0-9. ]+$/)) {
       $("#errorForPM").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Project Manager Name'));
        count++; 
    } else if (pmName.length > 200) {
       $("#errorForPM").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Project Manager Name').replace('#char','200'));
       count++;
    } else {
        $("#errorForPM").html('');
    }
    
    if(description == null || description == ''){
        $("#errorForDesc").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Description'));
        count++;
    } else {
        $("#errorForDesc").html('');
    }
    
    if(country == null || country == ''){
        $("#errorForCountry").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Country'));
        count++;
    } else {
        $("#errorForCountry").html('');
    }
    
    
    if(reason == null || reason == ''){
        $("#errorForReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        count++;
    } else {
        $("#errorForReason").html('');
    }
    
    if (count === 0) {
        $.ajax({
            url: "/customer/save_customer/"+customerId,
            type:'post',
            data:{ 
                customerId:customerId, 
                name : name,
                email : email,
                pmName : pmName,
                description : description,
                country :country,
                reason: reason
            },
            success:function(data) {
                var resp = JSON.parse(data);
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                if(responseCode !== 0){
                    window.setTimeout('window.location.replace("/customer")', 1000);
                }
                else{
                    $("#customerErrorMessages").html(errMessage);
                }
            }, error: function(jqXHR, exception) {
                $("#customerErrorMessages").html(jqXHR.responseText);
            }
        });
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
function deleteClient(customerId)
{
    $('#deletecommentasreason').modal('show');
    $("#customerIdTodelete").val(customerId);
    $("#customerDeleteErrorMessages").html("");
    $("#commenterrorfordelete").html("");
    $("#addcommentfordelete").val("");
}

$("#reasonfordelete").click(function(){
    var count = 0;
    $("#customerDeleteErrorMessages").html("");
    var comment = $.trim($("#addcommentfordelete").val());
    var customerId = $("#customerIdTodelete").val();
    
    if(comment == null || comment == ''){
        $("#commenterrorfordelete").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        count++;
    } else {
        $("#commenterrorfordelete").html('');
    }
    
    if (count === 0) {
        $.ajax({
            url: "/customer/delete/"+customerId,
            type:'post',
            data:{'comment':comment},
            success:function(data) {
                if(data=='Deleted'){
                    window.location.assign('/customer');
                } else {
                    $("#customerDeleteErrorMessages").html(data);
                }
            }
        });
    }
});

$(document).ready(function() {
    $('#customerData').dataTable( {
        "bDestroy": true,
        "bScrollInfinite": true,
        "bScrollCollapse": true,
        "paging": true,
        "aLengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
        "order": [],
        "columnDefs": [
            { targets: 0, orderable: false }
        ]
    });
    setTimeout(function(){$('#fashMessage').remove();}, 3000);
} );