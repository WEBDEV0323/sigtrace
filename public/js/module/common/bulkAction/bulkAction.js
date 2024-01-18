function chooseAction(actionId){
    $('#loading').show();
    let iDs = [];
    var selectedRows = gridOptions.api.getSelectedRows();
    if (selectedRows.length == 0) {
        $('#bulk_actions').val(0);
        $("#statusBulkAction").show();
        $("#fashMessage").removeClass('alert-success').addClass('alert-danger').html('Please select any case to apply bulk action.<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>');
        $('#loading').hide();
        return false;
    }
    $("#fashMessage").removeClass('alert-danger').addClass('alert-success')
    $("#statusBulkAction").hide();
    selectedRows.forEach( function(selectedRow, index) {
        iDs.push(selectedRow.id);
    });
    if (actionId != 0){
        $.ajax({ 
            url:'/bulk-action/check/'+trackerId+'/'+formId+'/'+actionId,
            type:'POST',
            success: function(response){
                $('#loading').hide();
                if (response !== 0){
                    var data = JSON.parse(response);
                    if (data['xtra_required'] == 'No'){
                        $('#manualModalForm').html('');
                        $('#automaticModalForm').show().html('<img src="/assets/dashboard_spinner.gif" width="15%" class="mx-auto d-block" alt="loading..." />');
                        setTimeout(function(){ 
                            $('#automaticModalForm').html('\n\
                                <form name="bulkAutomatic" method="post">\n\
                                    <input type="hidden" name="actionId" value="'+actionId+'"/>\n\
                                    <input type="hidden" name="recordIds" value="'+iDs+'"/>\n\
                                    <div class="form-group row">\n\
                                        <label class="col-sm-4 col-form-label" for="reason">Reason For Change<span class="error ml-1">*</span></label>\n\
                                        <div class="col-sm-7">\n\
                                            <textarea id="reason_for_change" class="form-control" placeholder="Add reason for change" name="reason"></textarea>\n\
                                            <div class="error" id="reason_for_change_error"></div>\n\
                                        </div>\n\
                                    </div>\n\
                                </form><div style="clear:both;"></div>');
                        }, 2000);
                        $('#automaticModal').modal('toggle');
                    } else if (data['xtra_required'] == 'Yes') {
                        $('#automaticModalForm').html('');
                        $('#manualModalForm').show().html('<img src="/assets/dashboard_spinner.gif" width="15%" class="mx-auto d-block" alt="loading..." />');
                        $.ajax({ 
                            url:'/bulk-action/getManualFormFields/'+trackerId+'/'+formId+'/'+actionId,
                            type:'POST',
                            success: function(response){
                                var data1 = JSON.parse(response); 
                                $('#manualModalForm').html('\n\
                                    <form name="bulkManual" method="post" class="form-horizontal">\n\
                                        <input type="hidden" name="actionId" value="'+actionId+'"/>\n\
                                        <input type="hidden" name="recordIds" value="'+iDs+'"/>'+data1['data']+'\
                                        <div class="form-group row">\n\
                                            <label class="col-sm-4 col-form-label" for="reason">Reason For Change<span class="error ml-1">*</span></label>\n\
                                            <div class="col-sm-7">\n\
                                                <textarea id="reason_for_change" class="form-control" placeholder="Add reason for change" name="reason"></textarea>\n\
                                                <div class="error" id="reason_for_change_error"></div>\n\
                                            </div>\n\
                                        </div>\n\
                                    </form><div style="clear:both;"></div>'); 
                            }
                        });
                        $('#manualModal').modal('toggle');
                    }
                }
                $('#bulk_actions').val(0);
            }
        });
    }     
}
function applyAction(type) {
    if($("#reason_for_change").val() != ""){
        $('#loading').show();
        let formData;
        if (type == 'manual') {
            formData = $('form[name="bulkManual"]').serializeArray();
        } else if (type == 'automatic') {
            formData = $('form[name="bulkAutomatic"]').serializeArray();
        }
        $.ajax({ 
            url:'/bulk-action/save/'+trackerId+'/'+formId,
            data:formData,
            type:'POST',
            success: function(response){
                var data = JSON.parse(response);
                if (data['responseCode'] == 1) {
                    $("#statusBulkAction").show();
                    $("#fashMessage").removeClass('alert-danger').addClass('alert-success').html('Bulk Action Completed Successfully.<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>');
                    $('#loading').hide();
                    setTimeout(function(){ window.location.reload(); }, 2000);
                } else {
                    $("#statusBulkAction").show();
                    $("#fashMessage").removeClass('alert-success').addClass('alert-danger').html('Error while applying bulk action.<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>');
                    $('#loading').hide();
                }
                if (type == 'automatic') {
                    $('#automaticModal').modal('toggle');
                } else if (type == 'manual') {
                    $('#manualModal').modal('toggle');
                }
                $("#bulk_actions").val("0");
                setTimeout(function(){
                    $("#statusBulkAction").hide();
                    $("#fashMessage").removeClass('alert-success').removeClass('alert-danger').text('');
                }, 2000);
            }
        });
    } else {
        $('#reason_for_change_error').html("Please enter Reason For Change.");
    }
    
}
