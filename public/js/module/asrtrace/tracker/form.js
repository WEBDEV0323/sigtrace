    $(document).ready(function() {
        
    });	

    function addNewForm(){
        var count = 0;
        $("#FormErrorMessage").html("");
        $("#status").html("");
        var formName = $.trim($("#form_name").val());
        if (formName == '') {
            $('#addReasonForFormNameError').html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Form Name'));
            count++;
        } else if (!formName.match(/^[a-zA-Z][a-zA-Z0-9 ]+$/)) {
            $("#addReasonForFormNameError").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Form Name'));
            count++;
        } else if (formName.length > 200) {
            $("#addReasonForFormNameError").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Form Name').replace('#char','200'));
            count++;
        } else {
            $('#addReasonForFormNameError').html("");
        }
        
        var recordName = $.trim($("#record").val());
        if (recordName == '') {
            $('#addReasonForRecordNameError').html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','single record name'));
            count++;
        } else if (!recordName.match(/^[a-zA-Z][a-zA-Z0-9 ]+$/)) {
            $("#addReasonForRecordNameError").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','single record name'));
            count++;
        } else if (recordName.length > 200) {
            $("#addReasonForRecordNameError").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','single record name').replace('#char','200'));
            count++;
        } else {
            $('#addReasonForRecordNameError').html("");
        }
        
        var description = $.trim($("#description").val());
        var reason = $.trim($("#addReasonForAdd").val());
        if (reason == '') {
            $('#addReasonForAddError').html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            count++;
        } else {
            $('#addReasonForAddError').html("");
        }
        if(count === 0) {
            $("#status").html('processing...');
            var data = {
                form_name : formName,
                record : recordName,
                description : description,
                reason: reason,
                tracker_id:trackerId
            };
            var url = "/form/ajax_form_add/"+trackerId;
            $.post(url, data,function(respJson){
                var resp =JSON.parse(respJson);
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                var formId = resp.formId;
                if (responseCode == 1) {
                       window.setTimeout('window.location.replace("/workflow/workflow_management/'+trackerId+'/'+formId+'")', 1000);
                } else {
                    $("#FormErrorMessage").html(errMessage);
                }
            });
        }
    }
    $('#cancelButton').on('click',function() {
        window.location.href = $(this).data('link');
    });