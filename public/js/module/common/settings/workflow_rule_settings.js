/* jshint sub:true*/
/* jshint shadow:true */
/* jshint -W119 */
$(document).ready(function() {
    $('#list_of_rules').dataTable( {
            "bDestroy": true,
            "responsive": true,
            "bScrollInfinite": true,
            "bScrollCollapse": true,
            "paging":         true,
            "aLengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
            "order": [],
            "columnDefs": [
                { 
                    render: function (data, type, full, meta) {
                        return "<div class='text-wrap width-200'>" + data + "</div>";
                    },
                    targets: 0, orderable: false 
                }
            ]
    });
    setTimeout(function(){$('#fashMessage').remove();}, 3000);
});
function clearRulePopup() {
    $("#r_form").val('');
    $("#r_form_error").html("");
    $("#RuleErrorMessage").html("");
    
    $("#r_condition").val('');
    $("#r_condition_error").html("");
    
    $("#addReasonForAdd").val("");
    $("#addReasonForAddError").html("");
    
    $('.n_conditionfield').each(function (i, obj) {
        var id = obj.id.split('_');
        if (id[2] > 1) {
            $("#add_condition_" + id[2]).remove();
        }
        $("#n_conditionfields_" + id[2]).val('');
        $("#n_conditionfields_" + id[2]).html('');
        $("#n_conditionfields_" + id[2]).parent().removeClass("has-error");
        $("#n_condition_operands_" + id[2]).val('');
        $("#n_condition_operands_" + id[2]).parent().removeClass("has-error");
        $("#displayValue_" + id[2]).val('');
        $("#displayValue_" + id[2]).parent().removeClass("has-error");
    });

    $('.n_actions').each(function (i, obj) {
        var id = obj.id.split('_');
        if (id[2] > 1) {
            $("#add_action_" + id[2]).remove();
        }
        $("#n_actions_" + id[2]).val('');
        $("#n_actions_" + id[2]).parent().removeClass("has-error");
        $("#n_conditionvals_" + id[2]).val('');
        $("#n_conditionvals_" + id[2]).html('');
        $("#n_conditionvals_" + id[2]).parent().removeClass("has-error");
        $('#action_fields_'+id[2]).hide();
        $("#n_fields_" + id[2]).html('');
        $("#n_fields_" + id[2]).parent().parent().removeClass("has-error");
    });
}
$('[id^="addrule"]').click(function () {
    $("#rule_title").html("Add Rule");
    $("#ruleId").val(0);
    clearRulePopup();
    
});

$('#conditionsgroup').on('change', '[id^="selectuser_"]', function () {
    var id = this.id;
    id = id.split('_');
    document.getElementById('displayValue_' + id[1]).value = this.options[this.selectedIndex].text;
    document.getElementById('cond_values_' + id[1]).value = this.options[this.selectedIndex].value;
});
$('#conditionsgroup').on('keyup', '[id^="displayValue_"]', function () {
    var id = this.id;
    id = id.split('_');
    document.getElementById('cond_values_' + id[1]).value = this.value;
});
$('#conditionsgroup').on('change', '[id^="n_conditionfields_"]', function () {
    var id = this.id;
    id = id.split('_');
    if ($('option:selected', this).attr('type') == 'User Role') {
        $("#selectuser_" + id[2]).show();
        $("#selectuser_" + id[2]).val("");
        $("#displayValue_" + id[2]).hide();
    }
    else {
        $("#selectuser_" + id[2]).hide();
        $("#displayValue_" + id[2]).show();
        $("#displayValue_" + id[2]).val("");
    }
});
$('#actionsgroup').on('change', '[id^="n_conditionvals_"]', function () {
    var WorkflowId = this.value;
    var _id = this.id.split('_')[2];
    if ($('#n_actions_'+_id).val() == "Hide Fields") {
        validateActionWorkflowFields(WorkflowId,_id);
    } else {
        $('#action_fields_'+_id).hide();
    }
    
});
function validateActionWorkflowFields(WorkflowId,_id, selectedActionFields = 0) {
    $.ajax({
        url: "/settings/get_fields_by_workflow_id",
        data: 'workflow_id='+WorkflowId,
        type: "POST",
        success: function (data) {
            var resp = JSON.parse(data);
            var action_fields = '';
            $.each(resp, function (i, items) {
                if(i == 'results') {
                    $.each(items, function(j, item) {
                        action_fields += '<option value="' + item['field_id'] + '" type="' + item['field_name'] + '">' + item['label'] + '</option>';                        
                    });
                }
            });
            $('#action_fields_'+_id).show();
            $('#n_fields_'+_id).selectpicker();
            $('#n_fields_'+_id).html('');
            $('#n_fields_'+_id).html(action_fields);
            if (selectedActionFields != 0 && selectedActionFields != null) {
                $('#n_fields_'+_id).selectpicker("val", (selectedActionFields).split(","));
            }
            $('#n_fields_'+_id).selectpicker('refresh');
        }
    });
}
$('#actionsgroup').on('change', '[id^="n_actions_"]', function () {
    var dId = this.id;
    var _value = this.value; 
    var _id = dId.split('_')[2];
    if (_value == 'Hide Fields') {
        $('#action_fields_'+_id).show();
        $('#n_fields_'+_id).selectpicker();
        $('#n_fields_'+_id).html('');
        var workflowId = $('#n_conditionvals_'+_id).val();
        if (workflowId > 0) {    
            validateActionWorkflowFields(workflowId,_id);
            return false;
        }
        $('#n_fields_'+_id).selectpicker('refresh');
    } else {
        $('#action_fields_'+_id).hide();
    }
    
});
function getfields(formId) {
    $("#loading").show();
    if (formId > 0) {
        getWorkflowsAndFieldsByFormId(formId);
    } else {
        cond_option = action_option = '';
        $('[id^="n_conditionfields_"]').html('');
        $('[id^="n_conditionvals_"]').html('');
        $('#n_fields_').html('');
    }
    setTimeout(function(){$("#loading").hide();}, 1000);
}

function getWorkflowsAndFieldsByFormId(formId, type="add", edata="") {
    $.ajax({
        url: "/settings/getWorkflowsAndFields/"+trackerId,
        data: 'form_id='+ formId,
        type: "POST",
        success: function (data) {
            var resp = JSON.parse(data);
            if(resp.responseCode == 1){
                cond_option = '<option value="0">Select Field</option>';
                $.each(resp.fields, function (i, item) {
                    cond_option += '<option value="' + item['field_id'] + '" type="' + item['field_type'] + '">' + item['label'] + '</option>';
                });
                $('[id^="n_conditionfields_"]').html('');
                $('[id^="n_conditionfields_"]').append(cond_option);

                action_option = '<option value="0">Select Workflow</option>';
                $.each(resp.workflows, function (i, item) {
                    action_option += '<option value="' + item['workflow_id'] + '">' + item['workflow_name'] + '</option>';
                });
                $('[id^="n_conditionvals_"]').html('');
                $('[id^="n_conditionvals_"]').append(action_option);
                if (type == "edit") {
                    viewRule(edata);
                }
            }
            $("#loading").hide();
        }
    });
}

function ClickAddCond() {
    html = '<div id="add_condition_' + str + '" class="form-group row">\n\
                <label class="col-sm-3 col-form-label" for="r_condition">&nbsp;</label>\n\
                <div class="col-sm-3">\n\
                <select id="n_conditionfields_' + str + '" class="n_conditionfield form-control"></select>\n\
                </div>\n\
                <div class="col-sm-3">\n\
                <select id="n_condition_operands_' + str + '"   class="demo-default n_condition_operand form-control">\n\
                    <option value="">Select Condition</option>\n\
                    <option value="=">=</option><option value="<>">!-</option>\n\
                    <option value=">">&gt</option><option value="<"><</option>\n\
                </select>\n\
                </div>\n\
                <div class="col-sm-2">\n\
                <select id="selectuser_' + str + '" style="display:none;" class="form-control">\n\
                    <option value=""></option>\n\
                    <option value="cur_user">Current User</option>\n\
                </select>\n\
                <input class="displayValue form-control" name="displayValue_' + str + '" placeholder="add/select a value"  id="displayValue_' + str + '" type="text">\n\
                <input name="cond_values_1" class="cond_values" id="cond_values_' + str + '" type="hidden">\n\
                </div>\n\
                <div class="col-sm-0">\n\
                <i class="lnr icon-trash2" onclick="delcondition(this)" style="color: red; cursor: pointer;"></i>\n\
                </div>\n\
            </div>';
    $("#conditionsgroup").append(html);
    $('[id^="n_conditionfields_"]').append(cond_option);
    str++;
}

function ClickAddAction() {
    html = '<div id="add_action_'+ actionstr+'"  class="form-group row">\n\
                <label class="col-sm-3 col-form-label" for="r_condition">&nbsp;</label>\n\
                <div class="col-sm-3">\n\
                <select id="n_actions_' + actionstr + '" class="n_actions form-control">\n\
                <option value="">Select Action</option>\n\
                <option value="Edit Workflow">Edit Workflow</option>\n\
                <option value="Hide Fields">Hide Fields</option>\n\
                </select>\n\
                </div>\n\
                <div class="col-sm-3">\n\
                <select id="n_conditionvals_' + actionstr + '" class="n_conditionvals form-control" placeholder="Select Value"></select>\n\
                </div>\n\
                <div class="col-sm-2">\n\
                <div id="action_fields_' + actionstr + '" style="display:none;">\n\
                    <select id="n_fields_' + actionstr + '" name="n_fields_' + actionstr + '[]" placeholder="Select Field" title="Select Field" class="n_actionfields selectpicker form-control" multiple data-live-search="true" data-actions-box="true" data-container="body"></select>\n\
                </div>\n\
                </div>\n\
                <div class="col-sm-0">\n\
                <i class="lnr icon-trash2" onclick="delaction(this)" style="color: red; cursor: pointer;"></i>\n\
                </div>\n\
            </div>';
    $("#actionsgroup").append(html);
    $('#n_conditionvals_'+ actionstr).html(action_option);
    actionstr++;

}

function delcondition(node)
{
    r = node.parentNode.parentNode;
    r.parentNode.removeChild(r);
}

function delaction(node)
{
    r = node.parentNode.parentNode;
    r.parentNode.removeChild(r);
}

function deleteRule(ruleId, formId) {
    
    $('#deletecommentasreason').modal('show');
    $("#ruleIdForDelete").val(ruleId);
    $("#formIdForDelete").val(formId);
    $('#ruleDeleteErrorMessages').html("");
    $('#commenterrorfordelete').html("");
    $('#addcommentfordelete').val("");
}

$("#reasonfordelete").click(function () {
    $("#ruleDeleteErrorMessages").html("");
    var reason = $.trim($('#addcommentfordelete').val());
    var ruleId = $("#ruleIdForDelete").val();
    var formId = $("#formIdForDelete").val();
    var count = 0;
        
    if (reason == '') {
        $('#commenterrorfordelete').html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        count++;
    } else {
        $('#commenterrorfordelete').html("");
    }
    if (count === 0) {
        var url = "/settings/deleteRule/"+trackerId;
        $.post(url, {ruleId: ruleId, formId : formId, trackerId: trackerId, reason: reason}, function (respJson) {
            var resp =JSON.parse(respJson);
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                if(responseCode == 1){
                    window.setTimeout('window.location.replace("/settings/workflow_rule_settings/'+trackerId+'")', 1000);
                }
                else{
                    $("#ruleDeleteErrorMessages").html(errMessage);
                }
        });
    }
});

function addNewRule(tracker_id) {
    var count = 0;
    $("#RuleErrorMessage").html("");
    var ruleId = $.trim($("#ruleId").val());
    var formId = $.trim($("#r_form").val());
    var condition = $.trim($("#r_condition").val());
    var reason = $.trim($("#addReasonForAdd").val());
    
    if(formId == null || formId == ''){
        $("#r_form_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Form Name'));
        count++;
    } else {
        $("#r_form_error").html('');
    }
    if(condition == null || condition == ''){
        $("#r_condition_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Condition'));
        count++;
    } else {
        $("#r_condition_error").html('');
    }
    if(reason == null || reason == ''){
        $("#addReasonForAddError").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        count++;
    } else {
        $("#addReasonForAddError").html('');
    }

    count += validateConditionsAndActions();
    
    if (count === 0) {
        var elem = document.getElementsByClassName("n_conditionfield");
        var condition_on_field = [];
        for (var i = 0; i < elem.length; ++i) {
            if (typeof elem[i].value !== "undefined") {
                condition_on_field.push(elem[i].value);
            }
        }
        var elem = document.getElementsByClassName("n_condition_operand");
        var condition_operand = [];
        for (var i = 0; i < elem.length; ++i) {
            if (typeof elem[i].value !== "undefined") {
                condition_operand.push(elem[i].value);
            }
        }
        var elem = document.getElementsByClassName("cond_values");
        var value = [];
        for (var i = 0; i < elem.length; ++i) {
            if (typeof elem[i].value !== "undefined") {
                value.push(elem[i].value);
            }
        }
        
        var elem = document.getElementsByClassName("n_actions");
        var action_name = [];
        for (var i = 0; i < elem.length; ++i) {
            if (typeof elem[i].value !== "undefined") {
                action_name.push(elem[i].value);
            }
        }
        
        var elem = document.getElementsByClassName("n_conditionvals");
        var action_value = [];
        for (var i = 0; i < elem.length; ++i) {
            if (typeof elem[i].value !== "undefined") {
                action_value.push(elem[i].value);
            }
        }
        
        var elem = document.getElementsByClassName("n_actionfields");
        var action_fields = [];
        for (var i = 0; i < elem.length; ++i) {
            var id = elem[i].id;
            if (id != '') {
                var _id = id.split('_')[2];
                action_fields.push($("#n_fields_"+_id).val());
            }
        }
        
        var data = {
            ruleId: ruleId,
            trackerId: trackerId,
            formId: formId,
            rCond: condition,
            condition_on_field: condition_on_field,
            condition_operand: condition_operand,
            value: value,
            reason: reason,
            action_value: action_value,
            action_name: action_name,
            actionFields : action_fields
        };
        $.post("/settings/save_rule/"+trackerId, data, function (respJson) {
            var resp =JSON.parse(respJson);
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            if(responseCode == 1){
                window.setTimeout('window.location.replace("/settings/workflow_rule_settings/'+trackerId+'")', 1000);
            }
            else{
                $("#RuleErrorMessage").html(errMessage);
            }
        });
        
    } 
}

function validateConditionsAndActions() {
    var count = 0;
    var elem = document.getElementsByClassName("n_conditionfield");
    for (var i = 0; i < elem.length; ++i) {
        var id = elem[i].id;
        if (id != '') {
            var _id = id.split('_')[2];
            if (elem[i].value == "" || elem[i].value == 0) {
                $("#n_conditionfields_"+_id).parent().addClass('has-error');
                count++;
            } else {
                $("#n_conditionfields_"+_id).parent().removeClass('has-error'); 
            }
        }
    }
    var elem = document.getElementsByClassName("n_condition_operand");
    for (var i = 0; i < elem.length; ++i) {
        var id = elem[i].id;
        if (id != '') {
            var _id = id.split('_')[3];
            if (elem[i].value == "") {
                $("#n_condition_operands_"+_id).parent().addClass('has-error');
                count++;
            } else {
                $("#n_condition_operands_"+_id).parent().removeClass('has-error');
            }
        }
    }
    var elem = document.getElementsByClassName("displayValue");
    for (var i = 0; i < elem.length; ++i) {
        var id = elem[i].id;
        if (id != '') {
            var _id = id.split('_')[1];
            if (elem[i].value == "") {
                $("#displayValue_"+_id).parent().addClass('has-error');
                count++;
            } else {
                $("#displayValue_"+_id).parent().removeClass('has-error');
            }
        }
    }

    var elem = document.getElementsByClassName("n_actions");
    for (var i = 0; i < elem.length; ++i) {
        var id = elem[i].id;
        if (id != '') {
            var _id = id.split('_')[2];
            if (elem[i].value == "") {
                $("#n_actions_"+_id).parent().addClass('has-error');
                count++;
            } else {
                $("#n_actions_"+_id).parent().removeClass('has-error');
            }
        }
    }
    var elem = document.getElementsByClassName("n_conditionvals");
    for (var i = 0; i < elem.length; ++i) {
        var id = elem[i].id;
        if (id != '') {
            var _id = id.split('_')[2];
            if (elem[i].value == "" || elem[i].value == 0) {
                $("#n_conditionvals_"+_id).parent().addClass('has-error');
                count++;
            } else {
                $("#n_conditionvals_"+_id).parent().removeClass('has-error');
            }
        }
    }
    
    var elem = document.getElementsByClassName("n_actionfields");
    for (var i = 0; i < elem.length; ++i) {
        var id = elem[i].id;
        if (id != '') {
            var _id = id.split('_')[2];
            if ((elem[i].value == "" || elem[i].value == 0) && ($("#n_actions_"+_id).val() == 'Hide Fields')) {
                $("#n_fields_"+_id).parent().find(".dropdown-toggle").prop("style","border-color:#C13E41 !important");
                count++;
            } else {
                $("#n_fields_"+_id).parent().find(".dropdown-toggle").prop("style","");
            }
        }
    }
    return count;
}

function editRule(ruleId, formId) {
    $("#rule_title").html("Edit Rule");
    $("#ruleId").val(ruleId);
    clearRulePopup();
    $("#loading").show();
    $.ajax({
        url: "/settings/getRuleInfo",
        data: "rule_id="+ruleId,
        type: "POST",
        success: function (data) {
            var ruleData = JSON.parse(data);
            var resp = ruleData.data;
            if (ruleData.responseCode == 1) {
                $("#r_form").val(resp[0][0].form_id);
                getWorkflowsAndFieldsByFormId(resp[0][0].form_id, "edit", resp);
            } else {
                $("#RuleErrorMessage").html(ruleData.errMessage);
                $("#loading").hide();
            }
        }
    });
}
function viewRule(resp){
    var ind = 0;
    var action_ind = 0;
    $("#r_condition").val(resp[0][0].operator);
    $.each(resp[0], function (i, item) {
        ind = i + 1;
        if (i > 0) {
            str = i + 1;
            ClickAddCond();
        }
        $("#n_conditionfields_"+ind).val(resp[0][i].field_id);
        $("#n_condition_operands_"+ind).val(resp[0][i].comparision_op);
        $("#cond_values_" + ind).val(resp[0][i].condition_val);
        if (resp[0][i].condition_val == 'cur_user')
        {
            $('#selectuser_' + ind).val(resp[0][i].condition_val);
            $("#selectuser_" + ind + " option[value='cur_user']").attr("selected", "selected");
            document.getElementById('displayValue_' + ind).value = $("#selectuser_" + ind + " option:selected").text();
            $('#selectuser_' + ind).show();
            $('#displayValue_' + ind).hide();
        } else {
            document.getElementById('displayValue_' + ind).value = resp[0][i].condition_val;
            $('#displayValue_' + ind).show();
            $('#selectuser_' + ind).hide();
        }
    });
    $.each(resp[1], function (j, item) {
        action_ind = j + 1;
        if (j > 0) {
            actionstr = j + 1;
            ClickAddAction();
        }
        $("#n_actions_" + action_ind).val(item.action);
        $("#n_conditionvals_" + action_ind).val(item.action_val);
        if ((item.action).toLowerCase() == 'hide fields') {
            validateActionWorkflowFields(item.action_val,action_ind, item.action_fields);
        }
    });
}
$('[id^="rule_"]').click(function () {
    $("#rules_display_section").show();
    $("#r_form").val('');
    $("#r_condition").val('');
    $('.n_condtionfield').each(function (i, obj) {
        var id = obj.id.split('_');
        if (id[2] > 1) {
            $("#add_condition_" + id[2]).remove();
        }
        $("#n_condtionvals_" + id[2]).val('');
        $("#n_condtionfields_" + id[2]).val('');
        $("#n_condition_operands_" + id[2]).val('');
    });

    $('.n_actions').each(function (i, obj) {
        var id = obj.id.split('_');
        if (id[2] > 1) {
            $("#add_action_" + id[2]).remove();
        }
        $("#n_actions_" + id[2]).val('');
        $("#cond_values_" + id[2]).val('');
    });
    id = this.id.split('_');
    rule_id = id[1];
    var url = "/tracker/getruleinfo";
    $.ajax({
        url: url,
        data: "rule_id=" + id[1],
        type: "POST",
        //async: false,
        success: function (data) {
            var resp = JSON.parse(data);
            $("#r_form").val(resp[0][0].form_id);
            getfields(resp[0][0].form_id);
            var ind = 0;
            var action_ind = 0;
            $("#r_condition").val(resp[0][0].operator);
            $.each(resp[0], function (i, item) {
                ind = i + 1;
                if (i > 0) {
                    str = i + 1;
                    ClickAddCond();
                }
                $("#n_condtionfields_" + ind).val(resp[0][i].field_id);
                $("#n_condition_operands_" + ind).val(resp[0][i].comparision_op);
                $("#cond_values_" + ind).val(resp[0][i].condition_val);
                if (resp[0][i].condition_val == 'cur_user')
                {
                    $('#selectuser_' + ind).val(resp[0][i].condition_val);
                    // document.getElementById('selectuser_' + ind).value = resp[0][i].condition_val;
                    $("#selectuser_" + ind + " option[value='cur_user']").attr("selected", "selected");
                    document.getElementById('displayValue_' + ind).value = $("#selectuser_" + ind + " option:selected").text();
                    $('#selectuser_' + ind).show();
                } else {
                    document.getElementById('displayValue_' + ind).value = resp[0][i].condition_val;
                    $('#displayValue_' + ind).show();
                    $('#selectuser_' + ind).show();
                }
            });
            $.each(resp[1], function (j, item) {
                action_ind = j + 1;
                if (j > 0) {
                    actionstr = j + 1;
                    ClickAddAction();
                }
                $("#n_actions_" + action_ind).val(item.action);
                $("#n_condtionvals_" + action_ind).val(item.action_val);

            });
            //$("#loading").hide();

        }
    });
});