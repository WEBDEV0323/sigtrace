/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
selectCounter = 0;
required="";
deltrackerId=0;
deltemplateId=0;
count=0;
$(document).ready(function(){
    setTimeout(function(){$('#fashMessage').remove();}, 3000);
    $('#summernote').summernote({
        height: 250,                 // set editor height
        placeholder: 'write here...',
            toolbar: [
                ["style", ["style"]],
                ["font", ["bold", "underline", "clear"]],
                ["fontname", ["fontname"]],
                ["color", ["color"]],
                ["para", ["ul", "ol", "paragraph"]],
                ["table", ["table"]],
                ["insert", ["link"]],
                ["view", ["fullscreen", "codeview", "help"]]
            ]
    });
        
    //Add condition on Button click
    $("#addCondition").click(function () {    
        var cnt = (selectCounter > 0) ? selectCounter : $(this).attr('value');
        cnt=parseInt(cnt)+parseInt(1);  
        addConditionFields(cnt);
        $(this).attr('value',cnt);
    });
     getFormFields(0);
   //Add condition for update Notification
   if (typeof notificationCondition !== 'undefined' && notificationCondition !=='') {  
        var conArray=JSON.parse(notificationCondition);
        var con=[];
        for(var i=0;i<(conArray.length);i++){
            if(i!==0){
              addConditionFields(i,conArray[i].condition_field_name,conArray[i].condition_operand,conArray[i].condition_value);
            } else {
                $("#n_condtionfield").find('option').remove() ;
                $("#n_condtionfield").append('<option value="'+conArray[i].condition_field_name+'">'+conArray[i].condition_field_name+'</option>');
                $("#n_condtionfield").append(' <option value="">--Select Fields--</option>') ;
                $('#condition').val(conArray[i].condition_operand);
                $('#n_value').val(conArray[i].condition_value);
                $('.selectpicker').selectpicker('refresh');
                $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
                $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');
            }
            con.push(conArray[i].condition_id);
            $('#conditionID').val(con);
        }
    }
   
    $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
    $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');
});

function getFormFields(identifier){
    var form_id=$("#formname").val();
    if(form_id>0) {
        var data = {
            tracker_id: tracker_id,
            form_id: form_id,
            template_id:template_id
        }
        var url = "/email/getformfields";
        $.post(url, data, function (respJson) {
            var resp = JSON.parse(respJson);
            var fieldsArray = resp.fieldsArray;
            var notifyWhom = resp.notifyWhom;
            var dateFields = resp.dateFields;
            $("#c_hidden_fields").val(respJson);
            var opt;
            $.each(fieldsArray, function (i) {
                if(opt == undefined) {
                    opt ="<option value=" + fieldsArray[i].Field + ">" + fieldsArray[i].Field + "</option>";
                } else {
                    opt +="<option value=" + fieldsArray[i].Field + ">" + fieldsArray[i].Field + "</option>";
                }
            });
            options = opt;
            $('[id^="n_condtionfield"]').append(opt);
            $("#fieldsForWorkflow").append(opt);                    
            $("#fieldsForWorkflow").selectpicker("refresh");
            $('[id^="n_condtionfield"]').selectpicker("refresh");
            $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
            $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');                    
           
            if(template_id ==0) { 
                $.each(notifyWhom, function (i) {
                    $("#notifyWhom").append("<option value=" + notifyWhom[i].field_id + ">" + notifyWhom[i].label + "</option>");                
                });
                $("#notifyWhom").selectpicker("refresh");
                $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
                $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');                
            }
        $.each(dateFields, function (i) {
            if (currentFieldVal == dateFields[i].field_name) {
                $("#dateFields").append("<option value=" + dateFields[i].field_name + " selected='selected'>" + dateFields[i].label + "</option>");
            } else { 
                $("#dateFields").append("<option value=" + dateFields[i].field_name + ">" + dateFields[i].label + "</option>");
            }
            });
            $("#dateFields").selectpicker("refresh");
            $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
            $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');
        });


        $.ajax({
            url: "/email/getWorkflowandfields",
            type:'post',
            dataType: 'json',
            data:'form_id='+form_id,
            success:function(respJson) {   
                var data = respJson.workflow_name;
                $.each(data, function(i, item){
                    $("#workflowFields").append("<option value='" + data[i] + "'>" + data[i] + "</option>");                   
                });
                $("#workflowFields").selectpicker("refresh");
                $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
                $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');
            }
        });
        if(template_id>0){
            var data = {
                tracker_id: tracker_id,
                form_id: form_id,
                template_id:template_id
            }
            var url = "/email/populatefields";
            $.post(url, data, function (respJson) {
                var result = JSON.parse(respJson);
                if(form_id==result[0].notification_template_form_id) {
                    $("#n_value").val(result[0].condition_value);
                    $("#summernote").summernote({"content": result[0].notification_template_msg});
                    $("#n_subject").val(result[0].notification_template_subject);
                    $("#NotificationWhen").val(result[0].notification_template_condition_type);

                    /*$("#n_condtionfield").val(result[0].condition_field_name);
                    $("#n_condition_operand").val(result[0].condition_operand);
                    $("#n_value").val(result[0].condition_value);*/
                } else {
                    $("#n_value").val('');
                    $("#editor").html('');
                    $("#n_subject").val('');
                    $("#NotificationWhen").val('');                    
                }
                if(identifier!=0) {
                    $.each(result, function (j, item) {
                        if(j>0 && form_id==result[0].notification_template_form_id) {
                            addConditionFields(1, result[j].condition_field_name, result[j].condition_operand, result[j].condition_value);
                        } else{
                            $.each($('[id^="maindiv_"]'), function() {
                                (this ).remove();
                            });
                        }
                    });
                }
            });
        }
    }
    else{
        $("#c_hidden_fields").val();
        $("#dateFields").find('option').remove() ;
        $("#dateFields").append(' <option value="">--Select Fields--</option>') ;
        $("#n_condtionfield").selectpicker("refresh");
        $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
        $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');                        
        $('.n_condition_operand').val('');
        $('.n_value').val('');
        $.each($('[id^="maindiv_"]'), function() {
            (this ).remove();
        });
    }
}

function addConditionFields(i, fieldName='', operand='', value='') {
    selectCounter = i;
    var notifyArray = new Array();
    var notify=$('#notifyID').val();
    if (notify != '' ) {
       notifyArray = notify.split(",").map(Number); 
    }
    notifyArray.push(selectCounter);
    $('#notifyID').val(notifyArray);
    var counter = i;
    var newTextBoxDiv = $(document.createElement('div')) .attr("id", 'fieldsDiv' + counter).attr("class",'form-group row');
    //var options = $('#fields option');alert("#fields"+counter);
    var html='<label for="Textbox" class="control-label col-md-4" ></label>';
    html+='<div class="controls col-md-3">';
    html+='<select class="form-control chosen-select fields n_condtionfield selectpicker" data-live-search="true" data-actions-box="true" data-container="body" id="n_condtionfield'+ counter + '" name="fields[]">';
    if (fieldName !== undefined && fieldName !== "") {
            html+='<option value="'+fieldName+'" selected="selected">'+fieldName+'</option>';
    }
    html+= '<option value="">--Select Fields--</option></select>';
    html+= '<label class="error" id="n_condtion_error'+ counter + '"></label>';
    html+='</div>';
    html+='<div class="controls col-md-2">';
    html+='<select class="form-control conoperator " id="condition'+ counter + '" name="condition[]">';
    html+='<option value="">Select Condition</option>';
    if (operand == "==") {
        html+='<option value="==" selected="selected">=</option>';
    } else {
        html+='<option value="==">=</option>';
    }
    
    if (operand == "!=") {
        html+='<option value="!=" selected="selected">!=</option>';
    } else {
        html+='<option value="!=">!=</option>';
    }
    
    if (operand == ">") {
        html+='<option value=">" selected="selected">&gt</option>';
    } else {
        html+='<option value=">">&gt</option>';
    }
    
    if (operand == "<") {
        html+='<option value="<" selected="selected"><</option>';
    } else {
        html+='<option value="<"><</option>';
    }    
    html+='</select>';
    html+= '<label class="error" id="condition_error'+ counter + '"></label>';
    html+='</div>';
    html+='<div class="controls col-md-2">';
    if (value !== "") {            
        html+='<input type="text" class="form-control conValue n_value" name="conValue[]" id="conValue' + counter + '" value="'+value+'">';
    } else {      
        html+='<input type="text" class="form-control conValue n_value" name="conValue[]" id="conValue' + counter + '" value="">';
    }
    html+= '<label class="error" id="conValue_error'+ counter + '"></label>';
    html+='</div>';
    html+='<div class="controls col-md-1" style="padding-top: 8px !important;">';
    html+='<i onclick="delcondition(`fieldsDiv'+counter+'`)" id="removeButton" class="lnr icon-trash2" style="font-size: 16px; color: red;" aria-hidden="true"></i>';
    html+='</div>';

    newTextBoxDiv.after().html(html);
    newTextBoxDiv.appendTo("#conditionGroup");
    if (options !== '') {
        $("#n_condtionfield"+ counter).append(options);
    }
    // $("#n_condtionfield"+ counter).selectpicker();
    $("#n_condtionfield").selectpicker("refresh");
    $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
    $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');                            

    counter++;    
}
  
function delcondition(node)
{
 $('#requiredRemovedDivId').val(node);
 $('#myNotificationModal').modal('show');
}

$('#btnYes').click(function (event) {
  var divId= $('#requiredRemovedDivId').val();
  var position = parseInt(divId.replace('fieldsDiv', '')); 
    var con=$('#conditionID').val();
    var notify=$('#notifyID').val();
    var array = con.split(",").map(Number);
    var notifyArray = notify.split(",").map(Number);
    var delId=array[position];
    // array.pop();
    // notifyArray.pop();
    array.splice(position-1, 1);
    notifyArray.splice(position-1, 1);
    $('#conditionID').val(array);
    $('#notifyID').val(notifyArray);
    if(array.length>0){
      var dataString = 'id='+delId;
      $.ajax({
          type: "POST",
          url: "/email/deleteCondition/"+tracker_id,
          data: dataString,
          datatype:"json",
          success: function(res)
          {
              var response=$.parseJSON(res);
              if(response.responseCode == 1){
                $("#"+$('#requiredRemovedDivId').val()).remove();
                $('#myNotificationModal').modal('hide');
              }else{
                  $(".modal-body").html(response.errMessage);
                  $("#btnYes").hide();
              }
          }
      });
    } else{
        $("#"+$('#requiredRemovedDivId').val()).remove();
        $('#myNotificationModal').modal('hide');
    }
});

$("#formname").on("change", function(event) {
    event.preventDefault();
    var form_id = $(this).val();

    $("#dateFields").find('option').remove() ;
    $("#dateFields").append(' <option value="">--Choose Fields--</option>') ;
    $('[id^="n_condtionfield"]').find('option').remove() ;
    $('[id^="n_condtionfield"]').append(' <option value="">--Select Fields--</option>') ;
    $("#workflowFields").find('option').remove() ;
    $("#workflowFields").append(' <option value="">--Select Workflow--</option>') ;
    $("#fieldsForWorkflow").find('option').remove() ;
    $("#fieldsForWorkflow").append(' <option value="">--Select Fields--</option>') ;
    
    if (form_id !== undefined && form_id !== '' && form_id >0) {
        getFormFields(form_id);    
    }
    $('[id^="n_condtionfield"]').selectpicker("refresh");
    $("#dateFields").selectpicker('refresh');
    $("#workflowFields").selectpicker('refresh');
    $("#fieldsForWorkflow").selectpicker('refresh');
    $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
    $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add'); 
});


$("#workflowFields").change(function(){
    var workflowName=$(this).val();
    workflowName='[['+workflowName+']]'
    $('#summernote').summernote('editor.restoreRange');
    $('#summernote').summernote('editor.focus');
    $('#summernote').summernote('editor.insertText', workflowName);
    $('#summernote').summernote('editor.saveRange');
   
});
$("#fieldsForWorkflow").change(function(){
    var fieldName=$(this).val();
    fieldName='{{'+fieldName+'}}'
    $('#summernote').summernote('editor.restoreRange');
    $('#summernote').summernote('editor.focus');
    $('#summernote').summernote('editor.insertText', fieldName);
    $('#summernote').summernote('editor.saveRange');
});
$('#summernote').on('summernote.blur', function() {
  $('#summernote').summernote('saveRange');
});

$(function() {
    if(cookie.get('uUpdate')){
        $("#uUpdateSuccess").show();
        createCookie('uUpdate', "", -1);
        window.setTimeout("closeUserCreateAlert('uUpdateSuccess')", 3000);
    }
    else{
        $("#uUpdateSuccess").hide();
    }

    $("#bb").submit(function()
    {
        createCookie('uUpdate', 'Yes', 15000);
    });

});

function closeUserCreateAlert(id) {
    $("#"+id).hide();
}

function showAlert(id) {
    $("#"+id).show();
}

function closeAlert(id) {
    $("#"+id).hide();
}

function createCookie(name, value, time) {
    var date = new Date();
    date.setTime(date.getTime()+(time));
    var expires = "; expires="+date.toGMTString();
    document.cookie = name+"="+value+expires+"; path=/";
}

$("#NotificationName").change(function (){
    var re =  new RegExp(/^[a-zA-Z0-9.# ]*$/);
    var notificationName = $.trim($("#NotificationName").val());
    if (notificationName.length === 0) {
        $("#NotificationName_err").html('<font color="#cc0000">Reminder name cannot be blank</font>');
        count++;
    } else if (notificationName.length >= 25) {
        $("#NotificationName_err").html('<font color="#cc0000">Reminder name is too lengthy. Max permissible length is 25 letters.</font>');
        count++;
    } else {
        if(re.test(notificationName)){
          //$("#NotificationName_err").html('&nbsp;Checking...');
          $.ajax({
              type: "POST",
              url: "<?php echo $this->basePath();?>notification/notificationNameCheck",
              data: "notificationName=" + notificationName,
              success: function (msg) {
                  if (msg === 'ok'){
                      $("#NotificationName_err").html('<font color="#088A08">Reminder Name is duplicate</font>');
                      count++;
                  } else {
                      $("#NotificationName_err").html('<font color="#008000">Reminder Name is Unique</font>');
                      (count!=0)?count--:"";

                  }
              }
          });
           //$('#savereminder').prop('disabled',false);
        } else {
                $("#NotificationName_err").html('<font color="#cc0000">Reminder Name is not in proper format</font>');
                count++;
        }
    }
    if (count>0) {
                $('#savereminder').prop('disabled',true);        
    } else {
                $('#savereminder').prop('disabled',false);                
    }
        
});
$("#subject").change(function (){ 
    $('#savereminder').prop('disabled',false);
     var re =  new RegExp(/^[a-zA-Z0-9{}:.\-_# ]*$/);
     var subject = $.trim($("#subject").val());
     if (subject.length === 0) {
        $("#subject_err").html('<font color="#cc0000">Subject cannot be blank</font>');
        count++;
    } else {
        if (re.test(subject)){
            $("#subject_err").html('<font color="#008000">Subject is ok</font>');
            (count!=0)?count--:"";
        } else {
            $("#subject_err").html('<font color="#cc0000">Subject is not in proper format</font>');
            count++;
            
        }
    }
    if (count>0) {
                $('#savereminder').prop('disabled',true);        
    } else {
                $('#savereminder').prop('disabled',false);                
    }
});
  function addNewReminder(tracker_id,template_id) {
    $("#checkduplicate").hide();
    var flag=true;
    var valid = $("#addNewReminder").valid();
    var field_id=$("#notifyWhom").val();
    var ccmail=$("#CCToWhom").val();
    var n_cond=$("#NotificationWhen").val();
    var dateFields=$("#dateFields").val();
    var formValue=$('#formname').val();
    if(formValue==null || formValue==''){
        $("#n_form_first").show().html('This field is required');
        flag = false;
    }
    if(field_id==null || field_id==''){
        $("#notifyWhom_err").show().html('This field is required.');
        flag = false;
    }
    if(n_cond==null || n_cond==''){
        $("#NotificationWhen_error").show().html('This field is required.');
        flag = false;
    }
    if(dateFields==null || dateFields==''){
        $("#dateFields_err").show().html('This field is required.');
        flag = false;
    }

    if(ccmail!=="") {
        var emails = ccmail.split(",");
        var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
        for(var i = 0;i < emails.length;i++) {
            emails[i]=emails[i].trim();
            if(!regex.test(emails[i])) {
                $("#CCToWhom_err").html('<font color="#cc0000">Email id is not valid.</font>');
                flag = false;
            }
        }
    }
    var days = $('#days').val();
    if(days==undefined || days==null || days==""){
         $("#days_err").show().text('This field is required');   
       flag = false;
    } else {
        $("#days_err").hide().html('');   
    }
    var condition = $('#condition').val();
    if(condition== null || condition == ""){
         $("#n_condition_operand_first").show().text('This field is required');   
       flag = false;
    } else {
        $("#n_condition_operand_first").hide().html('');   
    }
    var field = $('#n_condtionfield').val();
    if(field== null || field==""){
         $("#n_condition_first").show().text('This field is required.');   
        flag = false;
    } else {
        $("#n_condition_first").hide().html('');   
    }
    var value=$('#conValue').val();
    if(value== null || value==""){
         $("#n_value_first").show().text('This field is required.');   
        flag = false;
    } else {
        $("#n_value_first").hide().html('');   
    }
    
    var notify=$('#notifyID').val();
    var notifyArray = notify.split(",").map(Number);     
    
    if (notifyArray.length > 0) {
        for(var i=0;i<notifyArray.length;i++) {
            var position = notifyArray[i];
            if (position > 0) {
                var operandVal = $('#condition' + position).val();
                if(operandVal== null || operandVal == ""){
                    $("#condition_error"+position).show().text('This field is required');   
                    flag = false;
                } else {
                    $("#condition_error"+position).hide().html('');   
                }

                var fieldVal = $('#n_condtionfield' + position).val();
                if(fieldVal== null || fieldVal == ""){
                    $("#n_condtion_error"+position).show().text('This field is required');   
                    flag = false;
                } else {
                    $("#n_condtion_error"+position).hide().html('');   
                }

                var conditionVal = $('#conValue' + position).val();
                if(conditionVal== null || conditionVal == ""){
                   $("#conValue_error"+position).show().text('This field is required');   
                   flag = false;
                } else {
                   $("#conValue_error"+position).hide().html('');   
                }    
            }
        }
    }
    
    if(!valid || flag==false) {
        return false;
    } else {
                var field_id=$("#notifyWhom").val();
                var ccmail=$("#CCToWhom").val();
                var n_cond=$("#NotificationWhen").val();
                if(template_id>0){
                    if(field_id==null){
                        $("#notifyWhom_err").html('<font color="#cc0000"><b>This field is required.</b> </font>');
                        return false;
                    }
                    if(n_cond==null){
                        $("#NotificationWhen_error").html('<font color="#cc0000"><b>This field is required.</b> </font>');
                        return false;
                    }
                    if(ccmail!=null){
                        var emails = ccmail.split(",");
                        var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
                        for(var i = 0;i < emails.length;i++) {
                            emails[i]=emails[i].trim();
                            if(!regex.test(emails[i])) {
                                $("#CCToWhom_err").html('<font color="#cc0000">Email id is not valid.</font>');
                                return false;
                            }else{
                                $("#CCToWhom_err").html('')
                            }
                        }
                    }
                    reasonforchange();
                } else {
                    var template_name=$("#NotificationName").val();
                    var form_id=$('#formname').val();
                    var workflow_id=$('#n_workflowname').val();
                    var subject=$('#subject').val();
                    //var msg=$("#summernote").val();
                    var msg= $('#summernote').summernote('code');
                    //var status=$("#n_status").val();
                    if(field_id===null || field_id===''){
                        $("#notifyWhom_err").html('<font color="#cc0000"><b>This field is required.</b> </font>');
                        return false;
                    }

                    if(ccmail!=null){
                        var emails = ccmail.split(",");
                        var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
                        for(var i = 0;i < emails.length;i++) {
                            emails[i]=emails[i].trim();
                            if(!regex.test(emails[i])) {
                                $("#CCToWhom_err").html('<font color="#cc0000"><b>Email id is not valid.</b> </font>');
                                return false;
                            }
                        }
                    }

        //                if(status=='undefined'){
        //                    status='Active'   ;
        //                }
        var days=$("#days").val();
        var beforeAfter=$("#beforeAfter").val();
        var dateFields=$("#dateFields").val();
                
                var elem = document.getElementsByClassName("n_condtionfield");
                var condition_on_field = [];
                for (var i = 0; i < elem.length; ++i) {
                    if (typeof elem[i].value !== "undefined") {
                        condition_on_field.push(elem[i].value);
                    }
                }

                var elem = document.getElementsByClassName("conoperator");
                var condition_operand = [];
                for (var i = 0; i < elem.length; ++i) {
                    if (typeof elem[i].value !== "undefined") {
                        condition_operand.push(elem[i].value);
                    }
                }

                var elem = document.getElementsByClassName("conValue");
                var value = [];
                for (var i = 0; i < elem.length; ++i) {
                    if (typeof elem[i].value !== "undefined") {
                        value.push(elem[i].value);
                    }
                }
                var data = {
                 template_id : template_id,
                 tracker_id : tracker_id,
                 template_name:template_name,
                 form_id:form_id,
                 subject:subject,
                 msg:msg,
                 //status:status,
                 field_id:field_id,
                 days:days,
                 beforeAfter:beforeAfter,
                 dateFields:dateFields,
                 n_cond:n_cond,
                 condition_on_field:condition_on_field,
                 condition_operand:condition_operand,
                 value:value,
                 workflow_id:workflow_id,
                 ccmail:ccmail,
                 comment:$('#reason_for_change').val()
                 };
                 var url = "/email/savereminder/"+tracker_id;
                 $.post(url, data,function(respJson){// alert(respJson);return false;
                 if(respJson=='duplicate')
                 {
                    $("#checkduplicate").show();
                 }
                 else{
                    window.location.assign('/email/index/'+tracker_id);
                 }
                 });
            }
    }

  }

function reasonforchange(){
        if ($('#reason_for_change').val() == ''){
            $('#reason_for_change_error').show();
            return false;
        }
        $('#reason_for_change_error').hide();
        var field_id=$("#notifyWhom").val();
        if(field_id==null){
                    $("#notifyWhom_err").html('<font color="#cc0000"><b>This field is required.</b> </font>');
                    return false;
                }
        var template_name=$("#NotificationName").val();
        var form_id=$('#formname').val();
        var workflow_id=$('#n_workflowname').val();
        var subject=$('#subject').val();
        //var msg=$("#summernote").val();
        var msg= $('#summernote').summernote('code');
            //var status=$("#n_status").val();
        var ccmail=$("#CCToWhom").val();

//            if(status=='undefined'){
//                status='Active'   ;
//            }
            var days=$("#days").val();
            var beforeAfter=$("#beforeAfter").val();
            var dateFields=$("#dateFields").val();
            var n_cond=$("#NotificationWhen").val();
            var elem = document.getElementsByClassName("n_condtionfield");
            var condition_on_field = [];
            for (var i = 0; i < elem.length; ++i) {
                if (typeof elem[i].value !== "undefined") {
                    condition_on_field.push(elem[i].value);
                }
            }

            var elem = document.getElementsByClassName("conoperator");
            var condition_operand = [];
            for (var i = 0; i < elem.length; ++i) {
                if (typeof elem[i].value !== "undefined") {
                    condition_operand.push(elem[i].value);
                }
            }

            var elem = document.getElementsByClassName("conValue");
            var value = [];
            for (var i = 0; i < elem.length; ++i) {
                if (typeof elem[i].value !== "undefined") {
                    value.push(elem[i].value);
                }
            }
            var data = {
             template_id : template_id,
             tracker_id : tracker_id,
             template_name:template_name,
             form_id:form_id,
             subject:subject,
             msg:msg,             
             field_id:field_id,
             days:days,
             beforeAfter:beforeAfter,
             dateFields:dateFields,
             n_cond:n_cond,
             condition_on_field:condition_on_field,
             condition_operand:condition_operand,
             value:value,
             workflow_id:workflow_id,
             ccmail:ccmail,
             comment:$('#reason_for_change').val()
             };
             var url = "/email/savereminder/"+tracker_id;
             $.post(url, data,function(respJson){
                if(respJson ==='duplicate' ) {
                    $("#checkduplicate").show();
                } else {
                    window.location.assign('/email/index/'+tracker_id);
                 }
             });
    }
    
    $("#CCToWhom").change(function(){ 
            var ccmail=this.value;
            if(ccmail!=null){
                if(ccmail.indexOf(";") > -1){
                    $("#CCToWhom_err").html('<font color="#cc0000"><strong>Semicolon(;) is not allowed.</strong> </font>');
                    return false;
                }else{
                    var emails = ccmail.split(","); 
                    var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
                    for(var i = 0;i < emails.length;i++) {                        
                        emails[i]=emails[i].trim();
                        if(!regex.test(emails[i])) {
                            $("#CCToWhom_err").html('<font color="#cc0000">Email id is not valid.</font>');
                            return false;
                        }else{
                            $("#CCToWhom_err").html('');
                        }
                    }
                }
            }
        });

        