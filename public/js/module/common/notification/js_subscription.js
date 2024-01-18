/* jshint sub:true*/
/* jshint shadow:true */
/* jshint -W119 */
/* jshint -W066 */
/* jshint -W033 */
/* jshint -W030 */
/* jshint -W009 */

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
    $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
    $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');
    getReports(0);
});

$("#daily").on('click',function() {
    $("#monthlyresult").hide();
    $("#result").hide();
});

//For displaying days when weekly radio box is selected

$("#weekly").on('click',function() {
        $("#monthlyresult").hide();
        $("#result").show();
});
$(document).ready(function() {
            if($("#weekly").prop("checked") == true){
                $("#monthlyresult").hide();
            } else if ($("#monthly").prop("checked") == true) {
                $("#result").hide();
            } else {
                $("#monthlyresult").hide();
                $("#result").hide();
            }
});


//Option to enter the days of a month

$("#monthly").on('click',function() {
    $("#result").hide();
    $("#monthlyresult").show();
});
  
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

$('#summernote').on('summernote.blur', function() {
  $('#summernote').summernote('saveRange');
});

$(function() {
    if (cookie.get('uUpdate')) {
        $("#uUpdateSuccess").show();
        createCookie('uUpdate', "", -1);
        window.setTimeout("closeUserCreateAlert('uUpdateSuccess')", 3000);
    } else {
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
    var re =  new RegExp(/^[a-zA-Z0-9.#_ ]*$/);
    var notificationName = $.trim($("#NotificationName").val());
    var templateType = 'Subscription';
    var data = {
        notificationName : notificationName,
        templateType : templateType,
        templateId : template_id
    };
    if (notificationName.length === 0) {
        $("#NotificationName_err").html('<font color="#cc0000">Subscription name cannot be blank</font>');
        count++;
    } else {
        if (re.test(notificationName)) {
            $.ajax({
                type: "POST",
                url: "/email/notificationNameCheck",
                data: data,
                async: false,
                success: function (msg) {
                    if (JSON.parse(msg) == 'ok'){
                        $("#NotificationName_err").html('<span class="error">This Name is already exists in the system. Please try with a different Name.</span>');
                        count++;
                    } else {
                        $("#NotificationName_err").html('<span class="error"></span>');
                        (count!=0) ? count-- : "";
                    }
                }
            });
        } else {
            $("#NotificationName_err").html('<font color="#cc0000">Subscription Name is not in proper format</font>');
            count++;
        }
    }
    if (count>0) {
        $('#savesubscription').prop('disabled',true);        
    } else {
        $('#savesubscription').prop('disabled',false);                
    }    
});
$("#subject").change(function (){ 
    $('#savesubscription').prop('disabled',false);
     var re =  new RegExp(/^[a-zA-Z0-9{}:.\-_# ]*$/);
     var subject = $.trim($("#subject").val());
     if (subject.length === 0) {
        $("#subject_err").html('<font color="#cc0000">Subject cannot be blank</font>');
        count++;
    } else {
        if (re.test(subject)){
            $("#subject_err").html('<label class="error"></label>');
            (count!=0)?count--:"";
        } else {
            $("#subject_err").html('<font color="#cc0000">Subject is not in proper format</font>');
            count++;
            
        }
    }
    if (count>0) {
                $('#savesubscription').prop('disabled',true);        
    } else {
                $('#savesubscription').prop('disabled',false);                
    }
});
function addNewSubscription(tracker_id,template_id) {
    $("#checkduplicate").hide();
    var flag=true;
    var valid = $("#addNewSubscription").valid();
    var field_id=$("#notifyWhom").val();
    var ccmail=$("#CCToWhom").val();
    var reportName=$("#selectreport").val();
    var formValue=$('#formname').val();
    var reportValues=$('#selectreport').val();
    var frequency = $('[name="frequency"]:checked').closest('label').text();
    var dailyValue=$('[name="frequency"]:checked').closest('label').text();
    var weeklyValue= $( '[name="week"]:checked' )
    .map(function() {
    return this.id;
    })
    .get()
    .join();
    var monthlyValue=$('#month').val();
    var frequencyValue;
    if ($('#daily').is(':checked')) {
        frequencyValue = dailyValue;
    } else if ($('#weekly').is(':checked')) {
        frequencyValue = weeklyValue.replace(/,$/,'');
    } else {
        frequencyValue = monthlyValue;
    }
    if(frequency==null || frequency==''){
        $("#frequency_err").show().html('This field is required');
        flag = false;
    }
    if($('#weekly').is(':checked') && (weeklyValue==null || weeklyValue=='')){
        $("#frequency_err").show().html('This field is required');
        flag = false;
    }
    if(reportValues == null || reportValues == ''){
        $("#n_report_first").show().html('This field is required.');
        flag = false;
    }
    if(formValue==null || formValue==''){
        $("#n_form_first").show().html('This field is required.');
        flag = false;
    }
    if(field_id==null || field_id==''){
        $("#notifyWhom_err").show().html('This field is required.');
        flag = false;
    }
    if(field_id!=="") {
        var emails = field_id.split(",");
        var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
        for(var i = 0;i < emails.length;i++) {
            emails[i]=emails[i].trim();
            if(!regex.test(emails[i])) {
                $("#notifyWhom_err").html('<font color="#cc0000">Please enter valid email Id.</font>');
                flag = false;
            }
        }
    }

    if(ccmail!=="") {
        var emails = ccmail.split(",");
        var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
        for(var i = 0;i < emails.length;i++) {
            emails[i]=emails[i].trim();
            if(!regex.test(emails[i])) {
                $("#CCToWhom_err").html('<font color="#cc0000">Please enter valid email Id.</font>');
                flag = false;
            }
        }
    }

    if(!valid || flag==false) {
        return false;
    } else {
        var field_id=$("#notifyWhom").val();
        var ccmail=$("#CCToWhom").val();
        if(template_id>0){
            if(field_id!=null){
                var emails = field_id.split(",");
                var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
                for(var i = 0;i < emails.length;i++) {
                    emails[i]=emails[i].trim();
                    if(!regex.test(emails[i])) {
                        $("#notifyWhom_err").html('<font color="#cc0000">Please enter valid email Id.</font>');
                        return false;
                    }else{
                        $("#notifyWhom_err").html('')
                    }
                }
            }
            if(ccmail!=null){
                var emails = ccmail.split(",");
                var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
                for(var i = 0;i < emails.length;i++) {
                    emails[i]=emails[i].trim();
                    if(!regex.test(emails[i])) {
                        $("#CCToWhom_err").html('<font color="#cc0000">Please enter valid email Id.</font>');
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
            var msg= $('#summernote').summernote('code');
            
            if(field_id!=null){
                var emails = field_id.split(",");
                var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
                for(var i = 0;i < emails.length;i++) {
                    emails[i]=emails[i].trim();
                    if(!regex.test(emails[i])) {
                        $("#notifyWhom_err").html('<font color="#cc0000"><b>Please enter valid email Id.</b> </font>');
                        return false;
                    }
                }
            }

            if(ccmail!=null){
                var emails = ccmail.split(",");
                var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
                for(var i = 0;i < emails.length;i++) {
                    emails[i]=emails[i].trim();
                    if(!regex.test(emails[i])) {
                        $("#CCToWhom_err").html('<font color="#cc0000"><b>Please enter valid email Id.</b> </font>');
                        return false;
                    }
                }
            }


                var data = {
                 template_id : template_id,
                 tracker_id : tracker_id,
                 template_name:template_name,
                 form_id:form_id,
                 subject:subject,
                 msg:msg,
                 report_name:reportName,
                 frequency:frequency,
                 frequency_value:frequencyValue,
                 field_id:field_id,
                 workflow_id:workflow_id,
                 ccmail:ccmail,
                 comment:$('#reason_for_change').val()
                 };
                 var url = "/email/savesubscription/"+tracker_id;
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
        var msg= $('#summernote').summernote('code');
        var ccmail=$("#CCToWhom").val();
        var reportName=$("#selectreport").val();
        var frequency = $('[name="frequency"]:checked').closest('label').text();
        var dailyValue=$('[name="frequency"]:checked').closest('label').text();
        var weeklyValue= $( '[name="week"]:checked' )
        .map(function() {
            return this.id;
        }).get()
        .join();
        var monthlyValue=$('#month').val();
        var frequencyValue;
        if ($('#daily').is(':checked')) {
            frequencyValue = dailyValue;
        } else if ($('#weekly').is(':checked')) {
            frequencyValue = weeklyValue.replace(/,$/,'');
        } else {
            frequencyValue = monthlyValue;
        }
            var data = {
             template_id : template_id,
             tracker_id : tracker_id,
             template_name:template_name,
             form_id:form_id,
             subject:subject,
             msg:msg,
             report_name:reportName,
             frequency:frequency,
             frequency_value:frequencyValue,
             field_id:field_id,
             workflow_id:workflow_id,
             ccmail:ccmail,
             comment:$('#reason_for_change').val()
             };
             var url = "/email/savesubscription/"+tracker_id;
             $.post(url, data,function(respJson){
                if(respJson ==='duplicate' ) {
                    $("#checkduplicate").show();
                } else {
                    window.location.assign('/email/index/'+tracker_id);
                 }
             });
    }
    
    $("#notifyWhom").change(function(){ 
        var field_id=this.value;
        if(field_id!=null){
            if(field_id.indexOf(";") > -1){
                $("#notifyWhom_err").html('<font color="#cc0000"><strong>Semicolon(;) is not allowed.</strong> </font>');
                return false;
            }else{
                var emails = field_id.split(","); 
                var regex = /^([\w-\.\#]+@([\w-]+\.)+[\w-]{2,4})?$/;
                for(var i = 0;i < emails.length;i++) {                        
                    emails[i]=emails[i].trim();
                    if(!regex.test(emails[i])) {
                        $("#notifyWhom_err").html('<font color="#cc0000">Please enter valid email Id.</font>');
                        return false;
                    }else{
                        $("#notifyWhom_err").html('');
                    }
                }
            }
        }
    });
    
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
                        $("#CCToWhom_err").html('<font color="#cc0000">Please enter valid email Id.</font>');
                        return false;
                    }else{
                        $("#CCToWhom_err").html('');
                    }
                }
            }
        }
    });
    
    
$("#formname").on("change", function(event) {
    $('#n_form_first').html('');
    var report = $(this).val();
    if(report== null || report==""){
         $("#n_form_first").html('This field is required.');   
    } else {
        $("#n_form_first").html('');   
    }
    
    $("#selectreport").find('option').remove() ;
    $("#selectreport").append(' <option value="">--Choose a Report--</option>') ;
    
    if (report != undefined && report != '' && report >0) {
        getReports(report);    
    }
    $("#selectreport").selectpicker('refresh');
    $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
    $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');              
});

function getReports(identifier) {
    var form_id=$("#formname").val();
    if(form_id>0) {
        var data = {
            form_id: form_id
        };
        var url = "/email/getReports";
        $.ajax({
            url: url,
            data: "tracker_id="+tracker_id+'&form_id='+form_id,
            type: "POST",
            async: false,
            success: function(data) {
                var resp = JSON.parse(data);
                var reportsArray = resp.reportsArray;
                $("#c_hidden_fields").val(data);
                var opt;
                $.each(reportsArray, function (i) {
                    if(opt == undefined) {
                        opt ="<option value='" + reportsArray[i].report_name + "'>" + reportsArray[i].report_name + "</option>";
                    } else {
                        opt +="<option value='" + reportsArray[i].report_name + "'>" + reportsArray[i].report_name + "</option>";
                    }
                });
                options = opt;
                $("#selectreport").append(opt);                    
                $("#selectreport").selectpicker("refresh");
                $('.selectpicker').selectpicker('setStyle', 'btn-light', 'remove');
                $('.selectpicker').selectpicker('setStyle', 'dropdown-select', 'add');                    
                
                }
        });
    }
}     