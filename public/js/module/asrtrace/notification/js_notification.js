selectCounter = 0;
required="";
deltrackerId=0;
deltemplateId=0;
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
 
 
    //Add condition for update Notification

   getFormFields(0);
    if (typeof notificationCondition !== 'undefined' && notificationCondition !=='') {  
        var conArray=$.parseJSON(notificationCondition);
        var con=[];
        for(var i=0;i<(conArray.length);i++){
            if(i!==0){
              addConditionFields(i,conArray[i].condition_field_name,conArray[i].condition_operand,conArray[i].condition_value);
            } else {
                $('#n_condtionfield').val(conArray[i].condition_field_name);
                $('#n_condition_operand').val(conArray[i].condition_operand);
                $('#n_value').val(conArray[i].condition_value);
                $('.selectpicker').selectpicker('refresh')                
            }
            con.push(conArray[i].condition_id);
            $('#conditionID').val(con);
        }
    }
    
     $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
     $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');
});


function addConditionFields(i,fieldName='',operand='',value='') { 
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
    if (fieldName !== "") {
            html+='<option value="'+fieldName+'" selected="selected">'+fieldName+'</option>';
    }
    html+= '<option value="">--Select Fields--</option></select>';
    html+= '<label class="error" id="n_condtion_error'+ counter + '"></label>';
    html+='</div>';
    html+='<div class="controls col-md-2">';
    html+='<select class="form-control conoperator n_condition_operand" id="condition'+ counter + '" name="condition[]">';
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
    
    if (operand == "Changes To") {
        html+='<option value="Changes To" selected="selected">Changes To</option>';
    } else {
        html+='<option value="Changes To">Changes To</option>';
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
    $("#n_condtionfield"+counter).selectpicker("refresh");
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
  // if(divId==='fieldsDiv1'){
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
$("#n_form").on("change", function(event) {
    $('#n_form_first').html('');
    var field = $(this).val();
    if(field== null || field==""){
         $("#n_form_first").html('This field is required');   
    } else {
        $("#n_form_first").html('');   
    }
    $('[id^="n_condtionfield"]').find('option').remove() ;
    $('[id^="n_condtionfield"]').append(' <option value="">--Select Fields--</option>') ;
    $("#workflowFields").find('option').remove() ;
    $("#workflowFields").append(' <option value="">--Select Workflow--</option>') ;
    $("#fieldsForWorkflow").find('option').remove() ;
    $("#fieldsForWorkflow").append(' <option value="">--Select Fields--</option>') ;
    
    if (field != undefined && field != '' && field >0) {
        getFormFields(field);    
    }
    $('[id^="n_condtionfield"]').selectpicker("refresh");
    $("#workflowFields").selectpicker('refresh');
    $("#fieldsForWorkflow").selectpicker('refresh');
    $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
    $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');              
});


$("#workflowFields").change(function(){
    var workflowName=$(this).val();
    workflowName='[['+workflowName+']]';
    $('#summernote').summernote('editor.restoreRange');
    $('#summernote').summernote('editor.focus');
    $('#summernote').summernote('editor.insertText', workflowName);
    $('#summernote').summernote('editor.saveRange');        
    
});
$("#fieldsForWorkflow").change(function(){
    var fieldName=$(this).val();
    fieldName='{{'+fieldName+'}}';
    $('#summernote').summernote('editor.restoreRange');
    $('#summernote').summernote('editor.focus');
    $('#summernote').summernote('editor.insertText', fieldName);
    $('#summernote').summernote('editor.saveRange');
});
$('#summernote').on('summernote.focusout', function() {
  $('#summernote').summernote('editor.saveRange');
});

$("#createEmail").click(function(){
  var radioValue = $("input[name='emailRadios']:checked").val();
      if(radioValue){
           window.location=radioValue;
      }
});

$("#reasonfordelete").click(function(){
    if ($('#addcommentfordelete').val() == ''){
        $('#commenterrorfordelete').show();
        return false;
    }
    else {
        $.ajax({
            url: "/email/deletetemplate/"+deltrackerId+'/'+deltemplateId,
            type:'post',
            dataType:'json',
            data:{'template_id':deltemplateId, 'comment':$('#addcommentfordelete').val()},
            success:function(data) { 
                if(data=='deleted')
                {
                    window.location.assign('/email/index/'+deltrackerId);
                }
            }
        })
    }
});

    function deleteTemplate(trackerId,templateId)
    {
        deltrackerId=trackerId;
        deltemplateId=templateId;
        $('#deletecommentasreason').modal('show');
        $('#addcommentfordelete').val('');
        $('#commenterrorfordelete').hide();
    } 

    function getFormFields(identifier){
        var form_id=$("#n_form").val(); //alert(form_id);
        if(form_id>0) {
            var data = {
                tracker_id: tracker_id,
                form_id: form_id,
                template_id:template_id
            }
            var url = "/email/getformfields";

            $.ajax({
                url: url,
                data: "tracker_id="+tracker_id+'&form_id='+form_id+'&template_id='+template_id,
                type: "POST",
                async: false,
                success: function(data) {
                    var resp = JSON.parse(data);
                    var fieldsArray = resp.fieldsArray;
                    var notifyWhom = resp.notifyWhom; 
                    $("#c_hidden_fields").val(data);
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
                                //alert(resp[i].Field);
                                $("#field_id").append("<option value=" + notifyWhom[i].field_id + ">" + notifyWhom[i].label + "</option>");                
                            });
                            $("#field_id").selectpicker("refresh");
                            $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
                            $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');
                        }
                    }
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
                        //selectize_field_name.setValue(result[0].condition_operand);
                        $("#n_value").val(result[0].condition_value);
                        $("#summernote").summernote({"content": result[0].notification_template_msg});
                        $("#n_subject").val(result[0].notification_template_subject);
                        $("#n_condition").val(result[0].notification_template_condition_type);
                    }
                    else{
                       
                        $("#n_value").val('');
                        $("#editor").html('');
                        $("#n_subject").val('');
                        $("#n_condition").val('');
                    }

                    if(identifier!=0) {
                        $.each(result, function (j, item) {
                            if(j>0 && form_id==result[0].notification_template_form_id) {
                                addconditionFields(1, result[j].condition_field_name, result[j].condition_operand, result[j].condition_value);
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
            $('.n_value').val('');
            $.each($('[id^="maindiv_"]'), function() {

                (this ).remove();
            });

        }
    }


    function random_string(size){
        var str = "";
        for (var i = 0; i < size; i++){
            str += random_character();
        }
        return str;
    }

    function random_character() {
        var chars = "lmnopqurstuvwxyzABCDEFGHIJKLMNOPQURSTUVWXYZ";
        return chars.substr( Math.floor(Math.random() * 62), 1);
    }

$('#n_condtionfield').change(function(){
    $('#n_condition_first').html('');
    var field = $(this).val();
    if(field== null || field==""){
         $("#n_condition_first").html('This field is required');   
        //$('#n_condtionfield_first').html('<font color="#cc0000"><b>This field is required.</b> </font>');
    } else {
        $("#n_condition_first").html('');   
    }
});
$('.n_condition_operand').change(function(){
     $('#n_condition_operand_first').html('');
         var condition = $(this).val();
    if(condition== null || condition == ""){
         $("#n_condition_operand_first").html('This field is required');   
        //$('#n_condtionfield_first').html('<font color="#cc0000"><b>This field is required.</b> </font>');
    } else {
        $("#n_condition_operand_first").html('');   
    }
});
$('#field_id').change(function(){
    $('#sendTo_status').html('');
    var field = $(this).val();
    if(field== null || field==""){
         $("#sendTo_status").html('This field is required');   
    } else {
        $("#sendTo_status").html('');   
    }
});    
function addNewTemplate(tracker_id,template_id) {
        $("#checkduplicate").hide();
        var flag=true;
        var valid = $("#NotificationForm").valid();
        var field_id=$("#field_id").val();
        var ccmail=$("#n_cc").val();
        var formValue=$('#n_form').val();

        if(formValue==null || formValue==''){
            $("#n_form_first").show().html('This field is required');
            flag = false;
        }
        if(field_id==null || field_id==''){
                    $("#sendTo_status").show().html('This field is required.');
                    flag = false;
                }
                if(ccmail!=="") {
                    var emails = ccmail.split(",");
                    var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
                    for(var i = 0;i < emails.length;i++) {
                        emails[i]=emails[i].trim();
                        if(!regex.test(emails[i])) {
                            $("#cc_status").html('<font color="#cc0000"><b>Email id is not valid.</b> </font>');
                            flag = false;
                        }else{
                            $("#cc_status").html('')
                        }
                    }
                }
                
        var condition = $('#n_condition_operand').val();
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

        var notifyWhen = $('#n_condition').val();
        if(notifyWhen== null || notifyWhen==""){
             $("#notify_when_error").show().text('This field is required.');   
            flag = false;
        } else {
            $("#notify_when_error").hide().html('');   
        }
    
    if (field !=null && condition != null) {
            var value=$('#n_value').val();
            if(value== null || value==""){
                $("#n_value_first").show().text('This field is required.');   
                flag = false;
            } else {
                $("#n_value_first").hide().html('');   
            }
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
                var field_id=$("#field_id").val();
                var ccmail=$("#n_cc").val();
                if(template_id>0){
                    if(field_id==null){
                        $("#sendTo_status").html('<font color="#cc0000"><b>This field is required.</b> </font>');
                        return false;
                    }
                    if(ccmail!=null){
                        var emails = ccmail.split(",");
                        var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
                        for(var i = 0;i < emails.length;i++) {
                            emails[i]=emails[i].trim();
                            if(!regex.test(emails[i])) {
                                $("#cc_status").html('<font color="#cc0000">Email Id is not valid.</font>');
                                return false;
                            } else {
                                $("#cc_status").html('');
                            }
                        }
                    }
                    reasonforchange();
                } else {
                    var template_name=$("#n_name").val();
                    var form_id=$('#n_form').val();
                    var workflow_id=$('#n_workflowname').val();
                    var subject=$('#n_subject').val();
                    //var msg=$("#summernote").val();
                    var msg= $('#summernote').summernote('code');
                    //var status=$("#n_status").val();
                    if(field_id===null || field_id===''){
                        $("#sendTo_status").html('<font color="#cc0000"><b>This field is required.</b> </font>');
                        return false;
                    }

                    if(ccmail!=null){
                        var emails = ccmail.split(",");
                        var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
                        for(var i = 0;i < emails.length;i++) {
                            emails[i]=emails[i].trim();
                            if(!regex.test(emails[i])) {
                                $("#cc_status").html('<font color="#cc0000"><b>Email id is not valid.</b> </font>');
                                return false;
                            }
                        }
                    }

        //                if(status=='undefined'){
        //                    status='Active'   ;
        //                }
                var n_cond=$("#n_condition").val();
                var elem = document.getElementsByClassName("n_condtionfield");
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

                var elem = document.getElementsByClassName("n_value");
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
                 n_cond:n_cond,
                 condition_on_field:condition_on_field,
                 condition_operand:condition_operand,
                 value:value,
                 workflow_id:workflow_id,
                 ccmail:ccmail,
                 comment:$('#reason_for_change').val()
                 }
                 var url = "/email/savetemplate/"+tracker_id;
                 $.post(url, data,function(respJson){    
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
    
    
    function getFields(identifier){
        var form_id=$("#n_form").val();
        if(form_id>0) {
            var data = {
                tracker_id: tracker_id,
                form_id: form_id,
                template_id:template_id
            }
            var url = "<?php echo $this->url('email', array('action' => 'getformfields')); ?>";
            $.ajax({
                url: url,
                data: "tracker_id="+tracker_id+'&form_id='+form_id+'&template_id='+template_id,
                type: "POST",
                async: false,
                success: function(data) {
                    var resp = JSON.parse(data);
                    $("#c_hidden_fields").val(data);                   
                }
            });
            if(template_id>0){
                var data = {
                    tracker_id: tracker_id,
                    form_id: form_id,
                    template_id:template_id
                }
                var url = "<?php echo $this->url('email', array('action' => 'populatefields')); ?>";

                $.post(url, data, function (respJson) {
                    var result = JSON.parse(respJson);
                   
                    if(form_id==result[0].notification_template_form_id) {                       
                        $("#n_value").val(result[0].condition_value);
                        $("#editor").html(result[0].notification_template_msg);
                        $("#n_subject").val(result[0].notification_template_subject);
                        $("#n_condition").val(result[0].notification_template_condition_type);
                    }
                    else{
                        
                        $("#n_value").val('');
                        $("#editor").html('');
                        $("#n_subject").val('');
                        $("#n_condition").val('');
                    }

                    if(identifier!=0) {
                        $.each(result, function (j, item) {
                            if(j>0 && form_id==result[0].notification_template_form_id) {
                                addconditionFields(1, result[j].condition_field_name, result[j].condition_operand, result[j].condition_value);
                            }
                            else{
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
            $('.n_value').val('');
            $.each($('[id^="maindiv_"]'), function() {
                (this ).remove();
            });

        }
    }

       function reasonforchange(){
        if ($('#reason_for_change').val() == ''){
            $('#reason_for_change_error').show();
            return false;
        }
        $('#reason_for_change_error').hide();
        var field_id=$("#field_id").val();
        if(field_id==null){
                    $("#sendTo_status").html('<font color="#cc0000"><b>This field is required.</b> </font>');
                    return false;
                }
           var template_name=$("#n_name").val();
            var form_id=$('#n_form').val();
            var workflow_id=$('#n_workflowname').val();
            var subject=$('#n_subject').val();
            var msg=$("#summernote").summernote('code');
            //var status=$("#n_status").val();
            var ccmail=$("#n_cc").val();

//            if(status=='undefined'){
//                status='Active'   ;
//            }
            var field_id=$("#field_id").val();
            var n_cond=$("#n_condition").val();

            var elem = document.getElementsByClassName("n_condtionfield");
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

            var elem = document.getElementsByClassName("n_value");
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
             n_cond:n_cond,
             condition_on_field:condition_on_field,
             condition_operand:condition_operand,
             value:value,
             workflow_id:workflow_id,
             ccmail:ccmail,
             comment:$('#reason_for_change').val()
             }
             var url = "/email/savetemplate/"+tracker_id;
             $.post(url, data,function(respJson){ 
                if(respJson=='duplicate')
                {
                $("#checkduplicate").show();
                }
                else{
                    window.location.assign('/email/index/'+tracker_id);
                 }
             });
    }

        $("#n_cc").change(function(){ 
            var ccmail=this.value;
            if(ccmail!=null){
                if(ccmail.indexOf(";") > -1){
                    $("#cc_status").html('<font color="#cc0000"><strong>Semicolon(;) is not allowed.</strong> </font>');
                    return false;
                }else{
                    var emails = ccmail.split(","); 
                    var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
                    for(var i = 0;i < emails.length;i++) {                        
                        emails[i]=emails[i].trim();
                        if(!regex.test(emails[i])) {
                            $("#cc_status").html('<font color="#cc0000">Email id is not valid.</font>');
                            return false;
                        }else{
                            $("#cc_status").html('');
                        }
                    }
                }
            }
        });
