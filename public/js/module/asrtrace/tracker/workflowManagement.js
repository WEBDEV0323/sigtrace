/* jshint sub:true*/
/* jshint shadow:true */
/* jshint -W061 */
$(function () {
   var tracker_id=$("#trackerId").val();
   var form_id=$("#formId").val();
   var record_id=$("#recordId").val();
   var workflowId=$('#workflowId').val();
   $('a[data-toggle="tab"]').on('hide.bs.tab', function (e) {
      var workflowId = $(e.relatedTarget).attr("wid"); // activated tab
      var relatedTarget  = $(e.target ).attr("wid"); // previous active tab
      if (getHasChanges()){
         if(confirm("Are you sure want to leave this page? You have changes that have not been saved."))
         {
            $('#workflowId').val(workflowId);
            $("#fieldsDiv"+relatedTarget).empty();
            $("#alertWorkflow").empty();
            $('#alertWorkflow').removeClass('alert alert-danger');
            $('#accessHeader').removeClass('alert alert-info');
            $('#accessHeader').html("");
            ajaxCallAfterPageLoad(tracker_id,form_id,workflowId,record_id,relatedTarget);
         } else {
            return false;
         }
      } else {
         $('#workflowId').val(workflowId);
         $("#fieldsDiv"+relatedTarget).empty();
         $("#alertWorkflow").empty();
         $('#alertWorkflow').removeClass('alert alert-danger');
         $('#accessHeader').removeClass('alert alert-info');
         $('#accessHeader').html("");
         ajaxCallAfterPageLoad(tracker_id,form_id,workflowId,record_id,relatedTarget);
      }
   });
   ajaxCallAfterPageLoad(tracker_id,form_id,workflowId,record_id,workflowId);
 });
function ajaxCallAfterPageLoad(tracker_id,form_id,workflowId,record_id,relatedTarget) {

   if (typeof workflowId === 'undefined') {
      $('#accessHeader').html("You dont have access to this record");
      $('#accessHeader').addClass('alert alert-danger');
   } else {
      $.ajax({
         url:'/workflowmgm/getFieldsByWorkflowId/'+tracker_id+'/'+form_id+'/'+record_id+'/'+type+'/'+filter+'/'+workflowId, 
         dataType: 'json',  // what to expect back from the PHP script, if anything
         cache: false,
         contentType: false,
         processData: false,
         async: false,                    
         type: 'post',
         beforeSend: function(){ 
            //  var html = '<div id="load" class="spinner-grow m-5 text-primary" style="width: 5rem; height: 5rem;" role="status">';
            //     html += '<span class="sr-only">Loading...</span>';
            //     html += '</div>';
            var html = '<div class="loading mt-5 pt-5" id ="load"><img src="/assets/dashboard_spinner.gif" width="10%" class="mx-auto d-block pt-5" alt="loading..." /></div>';
               $("#fieldsDiv"+relatedTarget).append(html); 
         }, 
         complete: function() { 
            $('#load').remove(); 
         },
         success: renderCallbackFields,
      });
   } 
   utility();
}

 function renderCallbackFields(response) {
   var workflowId=$('#workflowId').val();
   var currentUser=$("#currentUser").val();
   var showSaveButton = 0;
   for(var i in response) {
      if (response[i]['can_update']=='Yes') {
         var validation = response[i]['validation_required'];
         if (validation == 1){validation = "required";}
         showSaveButton = 1;
         switch (response[i]['field_type']) {
            case 'Text': 
               var value = response[i]['fieldValue'];
               if(value == null) {value = '';}
               var textField=textFields(response[i]['fieldlabel'],response[i]['field_name'],'text',value,"",validation);
               $("#fieldsDiv"+workflowId).append(textField);
               break;
            case 'Integer': 
               var integerField=integerFields(response[i]['fieldlabel'],response[i]['field_name'],response[i]['fieldValue'],"",validation);
               $("#fieldsDiv"+workflowId).append(integerField);
               break;
            case 'Date': 
               var dateFormat = response[i]['dateFormat'];
               var fieldType = response[i]['field_type'];
               var value = formatDateDDMMMYYYY(response[i]['fieldValue']);
               if(value == 'Invalid date' || value == '0000-00-00') {value = '';}
               var dateField=dateTypeFields(response[i]['fieldlabel'],response[i]['field_name'],fieldType,value,dateFormat,validation);
               $("#fieldsDiv"+workflowId).append(dateField);
               break;
            case 'Date Time': 
               var dateTimeFormat=response[i]['dateTimeFormat'];  
               var fieldType = response[i]['field_type'];
               var value= moment(response[i]['fieldValue'],'YYYY-MM-DD HH:mm:ss').format('DD-MMM-YYYY hh:mm A');
               if(value == 'Invalid date') {value = '';}
               var dateTimeField=dateTimeTypeFields(response[i]['fieldlabel'],response[i]['field_name'],fieldType,value,dateTimeFormat,validation);
               $("#fieldsDiv"+workflowId).append(dateTimeField);
               break;
            case 'ReadOnly':
               var value= moment(response[i]['fieldValue'],'YYYY-MM-DD',true);
               var isValid=value.isValid();
                if(isValid) {
                    value=moment(value).format('DD-MMM-YYYY');
                    var readOnlyField=readOnlyFields(response[i]['fieldlabel'],response[i]['field_name'],value);
                    $("#fieldsDiv"+workflowId).append(readOnlyField);
                } else {
                    var readOnlyField=readOnlyFields(response[i]['fieldlabel'],response[i]['field_name'],response[i]['fieldValue']);
                    $("#fieldsDiv"+workflowId).append(readOnlyField);                   
                }
               break;
            case 'TextArea':
               var value = response[i]['fieldValue'];
               if(value == null) {value = '';}
               var textAreaField=textAreaFields(response[i]['fieldlabel'],response[i]['field_name'],value,'',validation);
               $("#fieldsDiv"+workflowId).append(textAreaField);
               break;
            case 'Combo Box':
               var options=response[i]['options'];
               var comboBoxField=dropDownSingleSelectFields(response[i]['fieldlabel'],response[i]['field_name'],options,response[i]['fieldValue'],"",validation);
               $("#fieldsDiv"+workflowId).append(comboBoxField);
               break;
            case 'User Role':
               if(response[i]['formula'] == 'CurrentUser'){
                  var currentUserField=currentUserFields(response[i]['fieldlabel'],response[i]['field_name'],currentUser);
                  $("#fieldsDiv"+workflowId).append(currentUserField);
               } else {
                  var options=response[i]['options'];
                  var userRoleField=userRoleFields(response[i]['fieldlabel'],response[i]['field_name'],options,response[i]['fieldValue'],"",validation);
                  $("#fieldsDiv"+workflowId).append(userRoleField);
               }
               break;
            case 'Heading': 
               var headingField=headingFields(response[i]['fieldlabel']);
               $("#fieldsDiv"+workflowId).append(headingField);
               break;
            case 'Check Box': 
               var checkBoxOptions=response[i]['options'];
               var fieldValue = response[i]['fieldValue'];
               var commentvalue=response[i]['comment'];
               var fieldValueArr;
               if(fieldValue!=''){
                  fieldValueArr = fieldValue.split(',');
               }
               if(commentvalue == null) {commentvalue = '';}
               var checkBoxField=checkBoxFields(response[i]['fieldlabel'],response[i]['field_name'],checkBoxOptions,response[i]['fieldValue'],commentvalue,"",validation);
               $("#fieldsDiv"+workflowId).append(checkBoxField);
               break;
            case 'Formula': 
               var value = response[i]['fieldValue'];
               if(value == null) {value = '';}
               var formula=escape(response[i]['formula']);
               var formula_dependent=response[i]['formula_dependent'];
               var formulaField=formulaFields(response[i]['fieldlabel'],response[i]['field_name'],'text',value,formula,formula_dependent);
               $("#fieldsDiv"+workflowId).append(formulaField);
               break;
            case 'Formula Combo Box': 
               var options=response[i]['options'];
               var formula=escape(response[i]['formula']);
               var formula_dependent=response[i]['formula_dependent'];
               var formulaeDropDownField=formulaeDropDownFields(response[i]['fieldlabel'],response[i]['field_name'],options,response[i]['fieldValue'],formula,formula_dependent);
               $("#fieldsDiv"+workflowId).append(formulaeDropDownField);
               break;
            case 'Formula Date': 
               var dateFormat=response[i]['dateFormat'];
               var formula=escape(response[i]['formula']);
               var formula_dependent=response[i]['formula_dependent'];
               var formulaDateField=formulaDateFields(response[i]['fieldlabel'],response[i]['field_name'],'text',response[i]['fieldValue'],formula,formula_dependent,dateFormat);
               $("#fieldsDiv"+workflowId).append(formulaDateField);
               break;
            case 'File': 
               var fileField=fileFields(response[i]['fieldlabel'],response[i]['field_name'],response[i]['fieldValue'],validation);
               $("#fieldsDiv"+workflowId).append(fileField);
               break;
            case 'DependentText': 
               var formula=escape(response[i]['formula']);
               var formula_dependent=response[i]['formula_dependent'];
               var dependentTextField=dependentTextFields(response[i]['fieldlabel'],response[i]['field_name'],response[i]['fieldValue'],formula,formula_dependent);
               $("#fieldsDiv"+workflowId).append(dependentTextField);
               break;
            case 'hidden': console.log(response[i]);
               var hiddenField=hiddenFields(response[i]['field_name'],response[i]['fieldValue']);
               $("#fieldsDiv"+workflowId).append(hiddenField);
               break;
            default:
               var value = response[i]['fieldValue'];
               if(value == null) {value = '';}
               var textFieldDefault=textFields(response[i]['fieldlabel'],response[i]['field_name'],'text',value, validation);
               $("#fieldsDiv"+workflowId).append(textFieldDefault);
         }
      } else if ((response[i]['can_update']=='No' || response[i]['can_update']=='') && response[i]['can_read']=='Yes') {
         $('#buttonDiv'+workflowId).hide();
         showSaveButton = 1;
         switch (response[i]['field_type']) {
            case 'Text':
            case 'Integer': 
            case 'TextArea':
            case 'Combo Box':
            case 'User Role':
            case 'Formula': 
            case 'Formula Combo Box':
            case 'Formula Date': 
            case 'DependentText':
            case 'ReadOnly': 
                  var readOnlyField=readOnlyFields(response[i]['fieldlabel'],response[i]['field_name'],response[i]['fieldValue']);
                  $("#fieldsDiv"+workflowId).append(readOnlyField);
                  break;
            case 'Heading':
                  var headingField=headingFields(response[i]['fieldlabel']);
                  $("#fieldsDiv"+workflowId).append(headingField);
                  break;
            case 'File': 
                  var fileField=fileFieldsDownload(response[i]['fieldlabel'],response[i]['field_name'],response[i]['fieldValue']);
                  $("#fieldsDiv"+workflowId).append(fileField);
                  break;
            case 'Date': 
                  var dateFormat = response[i]['dateFormat'];
                  var fieldType = response[i]['field_type'];
                  var value = formatDateDDMMMYYYY(response[i]['fieldValue']);
                  if(value == 'Invalid date' || value == '0000-00-00') {value = '';}
                  var dateField=readOnlyFields(response[i]['fieldlabel'],response[i]['field_name'],value);
                  $("#fieldsDiv"+workflowId).append(dateField);
                  break;
            case 'Date Time': 
                  var dateTimeFormat=response[i]['dateTimeFormat'];  
                  var fieldType = response[i]['field_type'];
                  var value= moment(response[i]['fieldValue'],'YYYY-MM-DD HH:mm:ss').format('DD-MMM-YYYY hh:mm A');
                  if(value == 'Invalid date') {value = '';}
                  var dateTimeField=readOnlyFields(response[i]['fieldlabel'],response[i]['field_name'],value);
                  $("#fieldsDiv"+workflowId).append(dateTimeField);
                  break;
            case 'Check Box':
                  var checkBoxOptions=response[i]['options'];
                  var fieldValue = response[i]['fieldValue'];
                  var commentvalue=response[i]['comment'];
                  var fieldValueArr;
                  if(fieldValue!=''){
                      fieldValueArr = fieldValue.split(',');
                  }
                  var fieldLabel=response[i]['fieldlabel'];
                  var fieldName=response[i]['field_name'];
                  var fieldValue=response[i]['fieldValue'];
                  var disabled = 'disabled';
                  var checkBoxField=checkBoxFields(fieldLabel,fieldName,checkBoxOptions,fieldValue,commentvalue,disabled);
                  $("#fieldsDiv"+workflowId).append(checkBoxField);
                  break;
            default:
                //$("#fieldsDiv"+workflowId).append();
          }
         
      }  else if (i=='hidden') {
        
         for (var j in response['hidden'][0]) {
            var value = response['hidden'][0][j];
            if(value == null) {value = '';}
            var hiddenField=hiddenFields(j,value);
            $("#fieldsDiv"+workflowId).append(hiddenField);
         }
      } else if(response[i]['display']=='No') {
      
         var formula=escape(response[i]['formula']);
         var formula_dependent=response[i]['formula_dependent'];
         var hiddenFormulaField=hiddenFormulaFields(response[i]['field_name'],formula,formula_dependent);
         $("#fieldsDiv"+workflowId).append(hiddenFormulaField);
         
      } else {
         $('#accessHeader').html("You dont have access to edit this record");
         $('#accessHeader').addClass('alert alert-info');
      } 
   }
   if(showSaveButton == 0){
      $('#buttonDiv'+workflowId).fadeOut("slow");
      $("#fieldsDiv"+workflowId).append("<b>No fields available...</b>");
   }
 }
 
function submitAfterValidate() {
   
   var tracker_id=$("#trackerId").val();
   var form_id=$("#formId").val();
   var record_id=$("#recordId").val();
   var workflowId=$('#workflowId').val();
   var formDataField = $("#workflowForm").serializeArray();
   
   $('input[type="text"].datepick').each(function () {
      var key = $(this).attr('name');
      var newFormat;
      if($(this).val()==''){
         newFormat ='';
      } else {
         newFormat = formatDateToYYYYMMDD($(this).val());
      }
      formDataField.forEach(function(item) {
         if (key === item.name) {
           item.value = newFormat;
         }
      });
   });

   $('input[type="text"].datetimepick').each(function () {
      var key = $(this).attr('name');
      var newDateTime;
      if($(this).val()==''){
         newDateTime ='';
      } else {
         newDateTime= moment($(this).val(), dateTimeFormat).format('YYYY-MM-DD HH:mm:ss');
      }
      formDataField.forEach(function(item) {
         if (key === item.name) {
           item.value = newDateTime;
         }
      });
   });

   var fileData=$('input[type=file]');
   var formData = new FormData();
   
   for(var i=0; i < fileData.length; i++){ 
      
      if(fileData[i].value !=''){
         var oFile = fileData[i].files[0];
         if(oFile.size >= allowedFileSize){
            $('#file_error_'+fileData[i].name).html("Please upload file of max size "+allowedFileSize/(1024*1024) +" MB.");
            return false;
         } 
         $('#file_error_'+fileData[i].name).html('');
         formData.append(fileData[i].name, oFile);
      } else {
         $('#file_error_'+fileData[i].name).html('');
      }
   }

   formData.append('post', JSON.stringify(formDataField));
   var checkBox = [];
   $.each($("input[type=checkbox]"), function(index, value){  
      if ($(this).is(":checked")) {
         checkBox.push({
            field_name: value.name, 
            option_name:  $(this).val(),
            kpi: $(this).attr('kpi'),
            checked: 'Checked'
         });
      } else {
         checkBox.push({
            field_name: value.name, 
            option_name:  $(this).val(),
            kpi: $(this).attr('kpi'),
            checked: 'Unchecked'
         });
      }         
   });
   
   formData.append('checkbox', JSON.stringify(checkBox));
   $.ajax({
      url: '/workflowmgm/updateAndSaveWorkflowData/'+tracker_id+'/'+form_id+'/'+record_id+'/'+type+'/'+filter+'/'+workflowId,
      type: 'post',
      dataType: 'json',
      data: formData,
      processData: false,
      contentType: false,
      beforeSend: function(){
         acceptChanges();
         $("#submitButton"+workflowId).attr("disabled", true);
         var html= '<span class="spinner-border spinner-border-sm" role="status" id="load" aria-hidden="true"></span>';
         html+='<span class="sr-only">Saving...</span>';
         $("#submitButton"+workflowId).append(html);
      }, 
      complete: function() { 
         $("#submitButton"+workflowId).attr("disabled", false);
         $('#load').remove(); 
      },
      success: function(data) {
         
         $("#alertWorkflow").html(data.result);
         if(data.flag!=0){
            $('#alertWorkflow').addClass('alert alert-success');
         }else{
            $('#alertWorkflow').addClass('alert alert-danger');
         }
         $('#alertWorkflow').fadeIn('slow');
         $('html, body').animate({ scrollTop: 0 }, 0);
         window.setTimeout(function () {
            $(".alert").fadeTo(500, 1).slideUp(500, function () {
               $(this).empty();
            });
         }, 5000);
      },
      error: function(xhr, result, errorThrown) {
         $("#alertWorkflow").html("Something went wrong!");
         $('#alertWorkflow').addClass('alert alert-danger');
         $('#alertWorkflow').fadeIn('slow');
         $('html, body').animate({ scrollTop: 0 }, 0);
         window.setTimeout(function () {
            $(".alert").fadeTo(500, 1).slideUp(500, function () {
               $(this).empty();
               $(this).removeClass('alert alert-danger');
            });
         }, 5000);
         
      }
   });
}

function utility() 
{
   var dateFormat = $('.datepick').attr('format');
   var dateTimeFormat = $('.datetimepick').attr('format');
   $('.datepick').daterangepicker({
      autoUpdateInput: false,
      locale: {
         format: dateFormat,
      },
      singleDatePicker: true,
      linkedCalendars: false
   },function(start, end, label) {
      $(this.element).val(start.format(dateFormat));
      changedValue(this.element.attr('id'),start.format(dateFormat));
   });
  
   $("#workflowForm").change(function(e){
      var selectedFields = $(event.target);
      var id=selectedFields.attr('id');
      var value=$('#'+id).val();
      changedValue(id,value);
   });

   $('.datetimepick').daterangepicker({
      timePicker: true,
      autoUpdateInput: false,
      autoClose: true,
      singleDatePicker: true,
      "autoApply": true,
      linkedCalendars: false,
      locale: {
         format: dateTimeFormat,
      },
   },function(start, end, label) {
      $(this.element).val(start.format(dateTimeFormat));
      changedValue(this.element.attr('id'),start.format(dateTimeFormat));
   });

   $('input[type="checkbox"]').change(function() {
      var kpi=$(this).attr("kpi");
      var fieldName=$(this).attr("name");
      
      if(this.checked){
         $('#comment_checkbox_div_'+fieldName).show();
         if(kpi == 3){
            var criticalVal = $('#critical_checkbox_'+fieldName).val();
            if(criticalVal == null || criticalVal == '') criticalVal = 0;
            criticalVal=isNaN(criticalVal) ? 0:criticalVal;
            criticalVal = parseInt(criticalVal);
            criticalVal+=1;
            $('#critical_checkbox_'+fieldName).val(criticalVal);
            var cId='critical_checkbox_'+fieldName;
			   changedValue(cId,'');
         } else if(kpi == 2){
            var majorVal=$('#major_checkbox_'+fieldName).val();
            if(majorVal == null || majorVal == '') majorVal = 0;
            majorVal=isNaN(majorVal) ? 0:majorVal;
            majorVal = parseInt(majorVal);
            majorVal+=1;
            $('#major_checkbox_'+fieldName).val(majorVal);
            var cId='major_checkbox_'+fieldName;
			   changedValue(cId,'');
         }else if(kpi == 1){
            var minorVal = $('#minor_checkbox_'+fieldName).val();
            if(minorVal == null || minorVal == '') minorVal = 0;
            minorVal=isNaN(minorVal) ? 0:minorVal;
            var minorVal = parseInt(minorVal);
            minorVal+=1;
            $('#minor_checkbox_'+fieldName).val(minorVal);
            var cId='minor_checkbox_'+fieldName;
			   changedValue(cId,'');
         }
      }else{ 
         $('#comment_checkbox_div_'+fieldName).hide();
         if(kpi == 3){
            var criticalVal = $('#critical_checkbox_'+fieldName).val();
            if(criticalVal == null || criticalVal == '') criticalVal = 0;
            criticalVal=isNaN(criticalVal) ? 0:criticalVal;
            criticalVal = parseInt(criticalVal);
            if(criticalVal!=0)criticalVal-=1;
            $('#critical_checkbox_'+fieldName).val(criticalVal);
            var cId='critical_checkbox_'+fieldName;
			   changedValue(cId,'');
         } else if(kpi == 2){
            var majorVal=$('#major_checkbox_'+fieldName).val();
            if(majorVal == null || majorVal == '') majorVal = 0;
            majorVal=isNaN(majorVal) ? 0:majorVal;
            majorVal = parseInt(majorVal);
            if(majorVal!=0)majorVal-=1;
            $('#major_checkbox_'+fieldName).val(majorVal);
            var cId='major_checkbox_'+fieldName;
			   changedValue(cId,'');
         }else if(kpi == 1){
            var minorVal = $('#minor_checkbox_'+fieldName).val();
            if(minorVal == null || minorVal == '') minorVal = 0;
            minorVal=isNaN(minorVal) ? 0:minorVal;
            minorVal = parseInt(minorVal);
            if(minorVal!=0)minorVal-=1;
            $('#minor_checkbox_'+fieldName).val(minorVal);
            var cId='minor_checkbox_'+fieldName;
			   changedValue(cId,'');
         }
      }
      $('input:checkbox[name='+fieldName+']:checked').each(function () {
         $('#comment_checkbox_div_'+fieldName).show();
      });
      var chk = document.getElementsByName(fieldName);
      var len = chk.length;
      var visibility = 0;
      for(i=0;i<len;i++)
      {
         if(chk[i].checked){
            visibility++;
         } 
      }
      if (visibility==0){
         $('#comment_checkbox_'+fieldName).val(''); 
      }
   
   });

   $('textarea ').bind('input change', function() {
     var id = $(this).attr('id');
     var val = $(this).val();
     var commentId = id.replace('comment_textarea','comment_checkbox');
     $('#'+commentId).val(val);
   });

   $('input[type=text].dependentText').on('change', function (e) {
         var formulaDependentText=unescape($('.dependentText').attr("formula"));  
         var formulaDependentTextJson = JSON.parse(formulaDependentText);
         populatefields(formulaDependentTextJson,$(this).val());
   });

   $('.deleteUploadedFile').click(function (e) {
      var button = $(e.target); 
      var fieldName = button.data('file');
      var tracker_id=$("#trackerId").val();
      var form_id=$("#formId").val();
      var record_id=$("#recordId").val();
      if(confirm("Are you sure you want to delete this file?"))
      {
         var data = {
            id:record_id,
            file:fieldName,
            tracker_id:tracker_id,
            form_id:form_id,
         };
         var url = "/wp/removefile";
         $.post(url, data,function(data){
            if(data=='true'){
               $("#file_upload_link_"+fieldName).hide();
               var html= '<input type="file" class="custom-file-input form-control" id="'+fieldName+'"  name="'+fieldName+'">';
               html+= '<label class="custom-file-label" for="'+fieldName+'">Choose file</label>';
               $("#file_upload_div_" + fieldName).html(html);
               $("#file_upload_div_" + fieldName).css('margin-top',10);
            }
         }).fail(function(){
            $('#file_upload_div_'+fieldName).html("Something went wrong!!");
            $('#file_upload_div_'+fieldName).addClass('alert alert-danger');
         }).always(function(){
            $("#file_upload_link_"+fieldName).hide();
            $('input[type=file]').on('change',function(){
               var fileName = $(this).val();
               var cleanFileName = fileName.replace('C:\\fakepath\\', " ");
               $(this).siblings('.custom-file-label').addClass("selected").html(cleanFileName);
            });
         });
      }
   });

   $('input[type=file]').on('change',function(){
      var fileName = $(this).val();
      var cleanFileName = fileName.replace('C:\\fakepath\\', " ");
      $(this).siblings('.custom-file-label').addClass("selected").html(cleanFileName);
   });


}
function changedValue(id,value) {
   
   $('.formuladate').each(function () {
      var formulavalDate=unescape($(this).attr("formula"));  
      var formulaDepDate=$(this).attr("formula_dep");
      if (typeof formulaDepDate !== 'undefined' && formulavalDate !== 'null' && formulavalDate != '') {
         var formulaDepDateArr = formulaDepDate.trim().split(',');
         var formulaDateId=$(this).attr("id");
         if(formulaDepDateArr.includes(id)){
            var formulaExpression= formulavalDate;
            $.each(formulaDepDateArr, function (index, value_dep) {
               var formulaId="{{"+value_dep+"}}";
               formulaExpression = formulaExpression.replace(formulaId,$('#'+value_dep).val());
            });
            dateFieldValue = eval(formulaExpression);
            if(dateFieldValue == 'Invalid date' || dateFieldValue == ''){
               $('#'+formulaDateId).addClass('datepick');
               $('#'+formulaDateId).removeClass('disabled-picker');
               var dateFormat = $('.datepick').attr('format');
               $('body').on('focus',".datepick", function(){
                  $(this).daterangepicker({
                     autoUpdateInput: false,
                     locale: {
                        format: dateFormat,
                     },
                     singleDatePicker: true,
                  },function(start, end, label) {
                     $(this.element).val(start.format(dateFormat));
                     changedValue(this.element.attr('id'),start.format(dateFormat));
                  });
               });
            } else {
               $('#'+formulaDateId).addClass('disabled-picker');
               $('#'+formulaDateId).val(dateFieldValue);               
            }
         } 
      }
   });

   $('.formula').each(function () {
      var formulaval=unescape($(this).attr("formula")); 
      var formulaDep=$(this).attr("formula_dep");
      if (typeof formulaDep !== 'undefined' && formulaval !== 'null' && formulaval != '') {
         var formulaDepArr = formulaDep.split(',');
         var formulaId=$(this).attr("id");
         if(formulaDepArr.includes(id)){
            var formulaExpression= formulaval;
            $.each(formulaDepArr, function (index, value_dep) {
               var formulaId="{{"+value_dep+"}}";
               var depValue=$('#'+value_dep).val();
               if(isNaN(depValue) || depValue ==''){
                  formulaExpression = formulaExpression.replace(formulaId,$('#'+value_dep).val());
               } else {
                  formulaExpression = formulaExpression.replace('"'+formulaId+'"',$('#'+value_dep).val());
               }
            }); 
            formulaExpression=formulaExpression.replace(/\"\"\+/g,'');
            formulaExpression=formulaExpression.replace(/\+\"\"/g,''); 
            var val = eval(formulaExpression);
            if(val!==null){ //commenting this because of qc formula to update blank if it is blank on change.
               $('#'+formulaId).val(val);
            }             
         } 
      }
   });

   $('.formulacombobox').each(function () {
      var formulavalCombo=unescape($(this).attr("formula"));  
      var formulaDepCombo=$(this).attr("formula_dep");
      
      if (typeof formulaDepCombo !== 'undefined' && formulavalCombo !== 'null' && formulavalCombo != '') {
         var formulaDepComboArr = formulaDepCombo.split(',');
         var formulaComboId=$(this).attr("id");
         if(formulaDepComboArr.includes(id)){
            var formulaExpression= formulavalCombo;
            $.each(formulaDepComboArr, function (index, value_dep) {
               var formulaId="{{"+value_dep+"}}";
               formulaExpression = formulaExpression.replace(formulaId,$('#'+value_dep).val());
            });
            var val = eval(formulaExpression);
            if(val){
               $('#'+formulaComboId).val(val);
            }
         }
      }
   });
}

function getHasChanges() {
   var hasChanges = false;
   $(":input:not(:button):not([type=hidden])").each(function () {
      if ((this.type == "text" || this.type == "textarea" || this.type == "hidden" || this.type == "number") && this.defaultValue != this.value) {
         hasChanges = true;
         return false;             
      } else {
         if ((this.type == "radio" || this.type == "checkbox") && this.defaultChecked != this.checked) {
            hasChanges = true;
            return false;                 
         } else {
            if ((this.type == "select-one" || this.type == "select-multiple")) {
               for (var x = 0; x < this.length; x++) {
                  if (this.options[x].selected != this.options[x].defaultSelected) {
                     hasChanges = true;
                     return false;
                  }
               }
            } else if(this.type == "file"){
               if(this.value.length){
                  hasChanges = true;
                  return false;
               }
            }
         }
      }
   });
   return hasChanges;
}

function acceptChanges() {
   $(":input:not(:button):not([type=hidden])").each(function () {
       if (this.type == "text" || this.type == "textarea" || this.type == "hidden" || this.type == "number") {
           this.defaultValue = this.value;
       }
       if (this.type == "radio" || this.type == "checkbox") {
           this.defaultChecked = this.checked;
       }
       if (this.type == "select-one" || this.type == "select-multiple") { 
           for (var x = 0; x < this.length; x++) {
               this.options[x].defaultSelected = this.options[x].selected;
           }
       }
   });
}

function clearFileInput(id) 
{ 
    var oldInput = document.getElementById(id); 

    var newInput = document.createElement("input"); 

    newInput.type = "file"; 
    newInput.id = oldInput.id; 
    newInput.name = oldInput.name; 
    newInput.className = oldInput.className; 
    newInput.style.cssText = oldInput.style.cssText; 
    // TODO: copy any other relevant attributes 

    oldInput.parentNode.replaceChild(newInput, oldInput); 
}