var cond_option = '';
var sel_rule_id='';
var str = 2;
$(document).ready(function() {
    
    var jsonString = JSON.stringify(jsonWorkflowData);
    $('#inputWfHidden').val(jsonString);


    var jsonString = JSON.stringify(jsonCodeListData);
    $('#input_hidden_codelist').val(jsonString);


    var jsonString = JSON.stringify(jsonRolesData);
    $('#input_hidden_roles').val(jsonString);
    
    $('#fieldDataTable').dataTable({
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

});

function editFieldName(value)
{
    var fieldName = value["fieldName"];
    var fieldType = value["fieldType"]; 
    var label = value["label"]; 
    var f_id = value["fieldId"]; 
    var kpi = value["KPI"];
    var option = value["codeListId"];
    var formula = value["formula"];
    var validation_required = value["validationRequired"];
    var rule_id = value["ruleId"];
    var rule_value = value["ruleValue"];
    var rule_message = value["ruleMsg"];
    sel_rule_id = rule_id;
    $("#fieldEditErrorMessage").html("");
    $("#edit_role_option_error").html("");
    $("#edit_code_list_option_error").html("");
    $("#edit_field_name_error").html("");
    $("#edit_val_rules").html("");
    $("#edit_reason_for_change_error").html("");
    
    $("#edit_field_name_hidden").val(fieldName);
    $("#edit_field_name").val(label);
    $("#edit_field_type").val(fieldType);
    $("#edit_field_id").val(f_id);
    $("#edit_kpi_type").val(kpi);
    $("#edit_code_list_option").val(option);
    $("#edit_val_req").val(validation_required);

    $("#rule_value_edit_1").val(rule_value);
    $("#rule_message_edit_1").val(rule_message);
    $("#originalFieldType").val(fieldType);
    $("#originalRuleId").val(rule_id);
    $("#originalRuleValue").val(rule_value);
    $("#originalRuleMsg").val(rule_message);
    
    $("#reason_for_change").val("");
    
    if(fieldType == 'Check Box' || fieldType == 'Combo Box' || fieldType == 'Formula Combo Box'){
        $("#id_selid").show();
        $("#id_selid_role").hide();
    } else if(fieldType == 'User Role'){
        var respCheckListJson = $('#input_hidden_roles').val();
        var resp_checklist =JSON.parse(respCheckListJson);
        $("#id_selid").hide();
        $("#id_selid_role").show();
        $("#edit_role_option").val(formula);
     } else {
        $("#id_selid").hide();
        $("#id_selid_role").hide();
    }
    
    if(validation_required == '1')
    {
        var res = rule_id.split("==>");
        var ruleVal = rule_value.split("==>");
        var ruleMsg = rule_message.split("==>");
         if (res.length >= 1) {
             $("#valdiv").html("");
            $.each(res, function( index, value ) {
                editRule(res[index],ruleVal[index],ruleMsg[index],res.length,index);                    
            });
        }
        $("#id_validreq").show();
    }
    else
    {
        checkVal();
        $("#id_validreq").hide();
    }
}

function addFieldName() {
   $('#status_add').html("");
   $("#fieldAddErrorMessage").html("");
   var count = 0;
   var ruleErrorCount = 0;
   var fieldName = $.trim($("#add_field_name").val());
   var workflowName = $.trim($("#add_workflow").val());
   var fieldType = $.trim($("#add_field_type").val());
   var f_id = $.trim($("#add_field_id").val());
   var kpi = $.trim($("#add_kpi_type").val());
   var code_list_id = $.trim($("#add_code_list_option").val());
   var role_id = $.trim($("#add_role_option").val());
   var edit_field_name_hidden = $.trim($("#add_field_name_hidden").val());
   var comment = $.trim($("#add_reason_for_change").val());
   var validation_required = $.trim($("#add_val_req").val());
   
   var rule_id = new Array(); 
   var rule_value = new Array(); 
   var rule_message = new Array();
    $('select[name*="rule_id_"]').each(function() 
    {
        if ($(this).val() == '') {
            $(this).parent().addClass('has-error');
            ruleErrorCount++;
        } else {
            $(this).parent().removeClass('has-error');
        }
        rule_id.push($(this).val());
    });

   $('input[name*="value_"]').each(function() 
    {
        if ($(this).val() == '') {
            $(this).parent().addClass('has-error');
            ruleErrorCount++;
        } else {
            $(this).parent().removeClass('has-error');
        }
        rule_value.push($(this).val());
    });

   $('textarea[name*="message_"]').each(function() 
    {
        if ($(this).val() == '') {
            $(this).parent().addClass('has-error');
            ruleErrorCount++;
        } else {
            $(this).parent().removeClass('has-error');
        }
        rule_message.push($(this).val());
    });

    if(fieldName == null || fieldName == ''){
        $("#add_field_name_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Field Label Name'));
        count++;
    } else if (!fieldName.match(/^[a-zA-Z][a-zA-Z0-9 \-()]+$/)) {
       $("#add_field_name_error").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Field Label Name'));
       count++;
    } else if (fieldName.length > 30) {
       $("#add_field_name_error").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Field Label Name').replace('#char','30'));
       count++;
    } else {
        $("#add_field_name_error").html('');
    }

    if(workflowName == null || workflowName == '' || workflowName == '0'){
        $("#workflow_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Workflow'));
        count++;
    } else {
        $("#workflow_error").html('');
    }

    if(fieldType == null || fieldType == ''){
        $("#add_field_type_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Field Type'));
        count++;
    } else {
        $("#add_field_type_error").html('');
    }

    if(fieldType == 'Check Box' || fieldType == 'Combo Box' || fieldType == 'Formula Combo Box'){
       if(code_list_id != 0){
           $('#add_code_list_option_error').html('');
       } else{
           $('#add_code_list_option_error').html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Options'));
           count++;
       }
    } else {
        code_list_id = 0;
    }

    if(fieldType == 'User Role') {
       if(role_id != 0) {
            $('add_role_option_error').html('');
       } else {
            $('#add_role_option_error').html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Options'));
            count++;
       }
    } else {
        role_id = 0;
    }

    if(kpi == null || kpi == ''){
        $("#add_kpi_type_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','KPI'));
        count++;
    } else {
        $("#add_kpi_type_error").html('');
    }

    if(validation_required == null || validation_required == ''){
        $("#add_val_req_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Validation Required'));
        count++;
    } else {
        $("#add_val_req_error").html('');
    }
    
    if(comment == null || comment == ''){
        $("#add_reason_for_change_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        count++;
    } else {
        $("#add_reason_for_change_error").html('');
    }

    if (validation_required == 1 && ruleErrorCount > 0) {
       $("#add_val_rules").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Validation Rules'));
       count++; 
    } else {
        $("#add_val_rules").html("");
    }
    if(count === 0) {
        $("#status_add").html('processing...');
        var data = {
            fieldName : fieldName,
            workflowId : workflowName,
            edit_field_name_hidden : edit_field_name_hidden,
            fieldType : fieldType,
            f_id : f_id,
            kpi : kpi,
            code_list_id : code_list_id,
            tracker_id : trackerId,
            form_id : formId,
            role_id : role_id,
            validation_req : validation_required,
            rule_id : rule_id,
            rule_message : rule_message,
            rule_value : rule_value,
            comment : comment
        };
        var url = "/field/addEditField/"+trackerId+"/"+formId;
        $.post(url, data,function(respJson){
            var resp =JSON.parse(respJson);
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            if(responseCode == 1){
                window.setTimeout('window.location.replace("/field/field_management/'+trackerId+'/'+formId+'")', 1000);
            }
            else{
                $("#fieldAddErrorMessage").html(errMessage);
            }
        });
    } else {
        return false;
    }
}
function addFieldErrorMesagesClean() {
   $("#add_field_name_error").html("");
   $("#workflow_error").html("");
   $("#add_role_option_error").html("");
   $("#add_code_list_option_error").html("");
   $("#add_val_rules").html("");
   $("#add_reason_for_change_error").html("");
   $("#fieldAddErrorMessage").html("");
   
   $("#add_field_name").val("");
   $("#add_workflow").val(0);
   $("#add_field_type").val("Integer");
   $("#add_id_selid").hide();
   $("#add_id_selid_role").hide();
   $("#add_code_list_option").val(0);
   $("#add_role_option").val(0);
   $("#add_kpi_type").val(0);
   $("#add_val_req").val(0);
   $("#add_id_valid_req").hide();
   $("#add_valdiv").html("");
   $("#add_reason_for_change").val("");
   
}
function clearEditModelStatus()
{
    $('#status_edit').html("");
}
function editFieldList()
{
   $('#status_edit').html("");
   $("#fieldEditErrorMessage").html("");
   var count = 0;
   var ruleErrorCount = 0;
   
   var fieldName = $.trim($("#edit_field_name").val());
   var fieldType = $.trim($("#edit_field_type").val());
   var f_id = $.trim($("#edit_field_id").val());
   var kpi = $.trim($("#edit_kpi_type").val());
   var code_list_id = $.trim($("#edit_code_list_option").val());
   var role_id = $.trim($("#edit_role_option").val());
   var edit_field_name_hidden = $.trim($("#edit_field_name_hidden").val());
   var comment = $.trim($("#reason_for_change").val());
   var validation_required = $.trim($("#edit_val_req").val());
   
   var rule_id = new Array(); 
   var rule_value = new Array(); 
   var rule_message = new Array();
    $('select[name*="rule_id_"]').each(function() 
    {
        if ($(this).val() == '') {
            $(this).parent().addClass('has-error');
            ruleErrorCount++;
        } else {
            $(this).parent().removeClass('has-error');
        }
        rule_id.push($(this).val());
    });

   $('input[name*="value_"]').each(function() 
    {
        if ($(this).val() == '') {
            $(this).parent().addClass('has-error');
            ruleErrorCount++;
        } else {
            $(this).parent().removeClass('has-error');
        }
        rule_value.push($(this).val());
    });

   $('textarea[name*="message_"]').each(function() 
    {
        if ($(this).val() == '') {
            $(this).parent().addClass('has-error');
            ruleErrorCount++;
        } else {
            $(this).parent().removeClass('has-error');
        }
        rule_message.push($(this).val());
    });
   
    if(fieldName == null || fieldName == ''){
        $("#edit_field_name_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Field Label Name'));
        count++;
    } else if (!fieldName.match(/^[a-zA-Z][a-zA-Z0-9 ]+$/)) {
       $("#edit_field_name_error").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Field Label Name'));
       count++;
    } else if (fieldName.length > 30) {
       $("#edit_field_name_error").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Field Label Name').replace('#char','30')); 
       count++;
    } else {
        $("#edit_field_name_error").html('');
    }
    
    if(fieldType == null || fieldType == ''){
        $("#edit_field_type_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Field Type'));
        count++;
    } else {
        $("#edit_field_type_error").html('');
    }
    
    if(fieldType == 'Check Box' || fieldType == 'Combo Box' || fieldType == 'Formula Combo Box'){
       if(code_list_id != 0){
            $('#edit_code_list_option_error').html('');
       } else{
           $('#edit_code_list_option_error').html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Options'));
           count++;
       }
    } else {
        code_list_id = 0;
    }
    
    if(fieldType == 'User Role') {
       if(role_id != 0) {
            $('#edit_role_option_error').html('');
       } else {
            $('#edit_role_option_error').html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Options'));
            count++;
       }
    } else {
        role_id = 0;
    }
    
    if(kpi == null || kpi == ''){
        $("#edit_kpi_type_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','KPI'));
        count++;
    } else {
        $("#edit_kpi_type_error").html('');
    }
    
    if(validation_required == null || validation_required == ''){
        $("#edit_val_req_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Validation Required'));
        count++;
    } else {
        $("#edit_val_req_error").html('');
    }
    
    if(comment == null || comment == ''){
        $("#edit_reason_for_change_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        count++;
    } else {
        $("#edit_reason_for_change_error").html('');
    }
    
    if (validation_required == 1 && ruleErrorCount > 0) {
       $("#edit_val_rules").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Validation Rules'));
       count++; 
    } else {
        $("#edit_val_rules").html("");
    }

    if(count === 0) {
        $("#status_edit").html('processing...');
        var data = {
            fieldName : fieldName,
            edit_field_name_hidden : edit_field_name_hidden,
            fieldType : fieldType,
            f_id : f_id,
            kpi : kpi,
            code_list_id : code_list_id,
            tracker_id : trackerId,
            form_id : formId,
            role_id : role_id,
            validation_req : validation_required,
            rule_id : rule_id,
            rule_message : rule_message,
            rule_value : rule_value,
            comment : comment
        };
        var url = "/field/addEditField/"+trackerId+"/"+formId;
        $.post(url, data,function(respJson){
            var resp =JSON.parse(respJson);
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            if(responseCode == 2){
                window.setTimeout('window.location.replace("/field/field_management/'+trackerId+'/'+formId+'")', 1000);
            }
            else{
                $("#fieldEditErrorMessage").html(errMessage);
            }
        });
    } else {
        return false;
    }

}

function deleteField()
{
    var count = 0;
    var fieldID = $("#fieldIDToDelete").val();
    $('#commenterrorfordelete').html("");
    $('#fieldDeleteErrorMessage').html("");
    if ($.trim($('#addcommentfordelete').val()) == ''){
        $('#commenterrorfordelete').html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        count++;
    }
    if (fieldID == 0) {
       count++;
    }
    if (count === 0) {

        $.ajax({
            url: "/field/delete_field/"+trackerId+"/"+formId+"/"+fieldID,
            type:'post',
            data:{ fieldID:fieldID, tracker_id:trackerId, form_id:formId, comment:$.trim($('#addcommentfordelete').val()) },
            success:function(respJson) {
                var resp = JSON.parse(respJson);
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                if(responseCode == 1){
                    location.reload();
                }
                else{
                    $("#fieldDeleteErrorMessage").html(errMessage);
                }
            }
        });
    }
}

function clearDeleteFields (fieldID) {
   $('#deletecommentasreason').modal('show');
   $('#addcommentfordelete').val("");
   $('#commenterrorfordelete').html("");
   $("#fieldIDToDelete").val(fieldID);
   $("#fieldDeleteErrorMessage").html("");
}

function checkMultiple(id, selID, start, questionType){
    var selVal= $('#'+selID).val();
    var html="";

    if(selVal == 'Check Box' || selVal == 'Combo Box' || selVal == 'Formula Combo Box'){
        $("#"+id).show();
        $("#"+id+"_role").hide();
    }
    else if(selVal == 'User Role'){
           $("#"+id+"_role").show();
            $("#"+id).hide();
     }
    else{
        $("#"+id).hide();
         $("#"+id+"_role").hide();

    }
}

function valDataType(){
    var fieldType = $('#edit_field_type').val();
    var valReq = $('#edit_val_req').val();
    if(valReq=="1" && fieldType!=""){
        $("#valdiv").html("");
        if($("#originalFieldType").val() == fieldType) {
            var originalRuleId = $("#originalRuleId").val();
            var originalRuleVal = $("#originalRuleValue").val();
            var originalRuleMsg = $("#originalRuleMsg").val();
            var res = originalRuleId.split("==>");
            var ruleVal = originalRuleVal.split("==>");
            var ruleMsg = originalRuleMsg.split("==>");
            if (res.length >= 1) {
                $.each(res, function( index, value ) {
                    editRule(res[index],ruleVal[index],ruleMsg[index],res.length,index);
                });
            }
        } else {
           addRule(); 
        }
    }
}

function valDataTypeForAdd(){
    $("#add_valdiv").html("");
    addRuleForAdd();
}

function checkVal()
{
    valDataType();
    var selValReq = $('#edit_val_req').val();
    if(selValReq == '1')
    {
        $("#id_validreq").show();
        if($("#valdiv > div").length == 0) {
            $.ajax({
                url: "/field/getValidationRule",
                type: 'post',
                data: "fieldtype=" + $('#fieldType_edit').val(),
                success: function (data) {
                    var resp = JSON.parse(data);
                    cond_option = '<option value=""> Rule Name </option>';
                    $.each(resp, function (i, item)
                    {
                        cond_option += '<option value="' + item['rule_id'] + '">' + item['rule_name'] + '</option>';
                    }
                    );
                    $('[id^="n_conditionrule_"]').html('');
                    $('[id^="n_conditionrule_"]').append(cond_option);
                    $("#n_conditionrule_1").val(sel_rule_id);
                }
            });
        }
    }
    else
    {
        $("#id_validreq").hide();
    }
}

function checkRules() {
    var selValReq = $('#add_val_req').val();
    if(selValReq == '1')
    {
        $("#add_id_valid_req").show();
        var fieldType = $('#add_field_type').val();
        var valReq = $('#add_val_req').val();
        var incr_number = 'cond_'+str
        var html = "<div condition='"+incr_number+"' class='form-group row'>";
        if(valReq === '1' && fieldType !== ""){
           $.ajax({

                url: "/field/getValidationRule",
                type: 'post',
                data: "fieldtype="+fieldType,
                success: function (data) {
                    var resp = JSON.parse(data);
                    var cond_option = '<option value="" default> Rule Name </option>';
                    $.each(resp, function (i, item)
                    {
                        cond_option += '<option value="' + item['rule_id'] + '">' + item['rule_name'] + '</option>';
                    }
                    );
                    html +='<div class="col-sm-4">';
                    html +='<label>Rule</label>';
                    html +='<select name="rule_id_'+str+'" id="n_condition_'+str+'" class="form-control validationRuleId" required >'+cond_option+'</select>';
                    html +='</div>';
                    html +='<div class="col-sm-4">';
                    html +='<label>Value</label>';
                    html +='<input name="value_'+str+'" type="text" class="form-control validationRuleValue"  placeholder="Rule Value" id="rule_value_edit_'+str+'" name="rule_value_edit" required value="">';
                    html +='</div>';
                    html +='<div class="col-sm-3">';
                    html +='<label>Message</label>';
                    html +='<textarea name="message_'+str+'" class="form-control validationRuleMsg"   id="rule_message_edit_'+str+'" name="rule_message_edit" required value=""></textarea>';
                    html +='</div>';
                        if($("#add_valdiv > div").length !=0 ){
                            html += '<div class="col-sm-1" style="margin-top:60px;"><i class="lnr icon-trash2" style="color: red; cursor: pointer;" onClick="delRule(this)"></i></div>'; 
                        }
                        html +='</div>';
                    $("#add_valdiv").append(html);
                    str++;
                }
            }); 
        }
    }
    else
    {
        $("#add_valdiv").html("");
        $("#add_id_valid_req").hide();
    } 
}

function editRule(ruleId,ruleValue, ruleMsg,rulesCount,index)
{
    var fieldType = $('#edit_field_type').val();
    var valReq = $('#edit_val_req').val();
    var incr_number = 'cond_'+index;
    var html = "<div condition='"+incr_number+"' class='form-group row'>";
    if(valReq === '1' && fieldType !== ""){
       $.ajax({
            url: "/field/getValidationRule",
            type: 'post',
            data: "fieldtype="+fieldType,
            success: function (data) {
                var resp = JSON.parse(data);
                var cond_option = '<option value="" default> Rule Name </option>';
                $.each(resp, function (i, item)
                {
                    cond_option += '<option value="' + item['rule_id']+'"' ;
                    if(ruleId == item['rule_id']){ cond_option += " selected ";}
                    cond_option += '>' + item['rule_name'] + '</option>';
                }
                );
                if($("#valdiv > div").length >= rulesCount){
                    $("#valdiv").html('');
                }
                html +='<div class="col-sm-4">';
                html +='<label>Rule</label>';
                html +='<select name="rule_id_'+index+'" id="n_condition_'+index+'" class="form-control validationRuleId" required>'+cond_option+'</select>';
                html +='</div>';
                html +='<div class="col-sm-4">';
                html +='<label>Value</label>';
                html +=' <input name="value_'+index+'" type="text" class="form-control validationRuleValue"  placeholder="Rule Value" id="rule_value_edit_'+index+'" name="rule_value_edit" required value="'+ruleValue+'">';
                html +='</div>';
                html +='<div class="col-sm-3">';
                html +='<label>Message</label>';
                html +='<textarea name="message_'+index+'" class="form-control validationRuleMsg"   id="rule_message_edit_'+index+'" name="rule_message_edit" required value="">'+ruleMsg+'</textarea>';
                html +='</div>';
                if($("#valdiv > div").length !=0 ){
                    html += '<div class="col-sm-1" style="margin-top:60px;"><i class="lnr icon-trash2" style="color: red; cursor: pointer;" onClick="delRule(this)"></i></div>'; 
                }
                html +='</div>';

                $("#valdiv").append(html);                  
            }
        }); 
    }



}


function addRule()
{
    var fieldType = $('#edit_field_type').val();
    var valReq = $('#edit_val_req').val();
    var incr_number = 'cond_'+str
    var html = "<div condition='"+incr_number+"' class='form-group row'>";
    if(valReq === '1' && fieldType !== ""){
       $.ajax({

            url: "/field/getValidationRule",
            type: 'post',
            data: "fieldtype="+fieldType,
            success: function (data) {
                var resp = JSON.parse(data);
                var cond_option = '<option value="" default> Rule Name </option>';
                $.each(resp, function (i, item)
                {
                    cond_option += '<option value="' + item['rule_id'] + '">' + item['rule_name'] + '</option>';
                }
                );
                html +='<div class="col-sm-4">';
                html +='<label>Rule</label>';
                html +='<select name="rule_id_'+str+'" id="n_condition_'+str+'" class="form-control validationRuleId" required >'+cond_option+'</select>';
                html +='</div>';
                html +='<div class="col-sm-4">';
                html +='<label>Value</label>';
                html +='<input name="value_'+str+'" type="text" class="form-control validationRuleValue"  placeholder="Rule Value" id="rule_value_edit_'+str+'" name="rule_value_edit" required value="">';
                html +='</div>';
                html +='<div class="col-sm-3">';
                html +='<label>Message</label>';
                html +='<textarea name="message_'+str+'" class="form-control validationRuleMsg"   id="rule_message_edit_'+str+'" name="rule_message_edit" required value=""></textarea>';
                html +='</div>';
                    if($("#valdiv > div").length !=0 ){
                        html += '<div class="col-sm-1" style="margin-top:60px;"><i class="lnr icon-trash2" style="color: red; cursor: pointer;" onClick="delRule(this)"></i></div>'; 
                    }
                    html +='</div>';
                $("#valdiv").append(html);
                str++;
            }
        }); 
    }



}

function addRuleForAdd() {
    var fieldType = $('#add_field_type').val();
    var valReq = $('#add_val_req').val();
    var incr_number = 'cond_'+str
    var html = "<div condition='"+incr_number+"' class='form-group row'>";
    if(valReq === '1' && fieldType !== ""){
       $.ajax({

            url: "/field/getValidationRule",
            type: 'post',
            data: "fieldtype="+fieldType,
            success: function (data) {
                var resp = JSON.parse(data);
                var cond_option = '<option value="" default> Rule Name </option>';
                $.each(resp, function (i, item)
                {
                    cond_option += '<option value="' + item['rule_id'] + '">' + item['rule_name'] + '</option>';
                }
                );
                html +='<div class="col-sm-4">';
                html +='<label>Rule</label>';
                html +='<select name="rule_id_'+str+'" id="n_condition_'+str+'" class="form-control validationRuleId" required >'+cond_option+'</select>';
                html +='</div>';
                html +='<div class="col-sm-4">';
                html +='<label>Value</label>';
                html +='<input name="value_'+str+'" type="text" class="form-control validationRuleValue"  placeholder="Rule Value" id="rule_value_edit_'+str+'" name="rule_value_edit" required value="">';
                html +='</div>';
                html +='<div class="col-sm-3">';
                html +='<label>Message</label>';
                html +='<textarea name="message_'+str+'" class="form-control validationRuleMsg"   id="rule_message_edit_'+str+'" name="rule_message_edit" required value=""></textarea>';
                html +='</div>';
                    if($("#add_valdiv > div").length != 0 ){
                        html += '<div class="col-sm-1" style="margin-top:60px;"><i class="lnr icon-trash2" style="color: red; cursor: pointer;" onClick="delRule(this)"></i></div>'; 
                    }
                    html +='</div>';
                $("#add_valdiv").append(html);
                str++;
            }
        });
    }
}
function delRule(node)
{
    r = node.parentNode.parentNode;
    r.parentNode.removeChild(r);
    sendOrderToServer();
}

function sendOrderToServer() {
    var elem = document.getElementsByClassName("form-control quesiionRulename");
    var max_sort_rule = parseInt($('#inputMaxRuleHidden').val());
    for (var i = 0; i < elem.length; ++i) {
        elem[i].innerHTML = "#"+(max_sort_rule+1+i);
    }
    var elem = document.getElementsByClassName("sort_rder");
    for (var i = 0; i < elem.length; ++i) {
        elem[i].value = max_sort_num+1+i;
    }
}  