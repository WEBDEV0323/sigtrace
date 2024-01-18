/* jshint shadow:true */
/*jshint sub:true*/
function textFields(fieldLabel,fieldName,fieldType,value,readonly,validation,hideField){
   hideField = (hideField == undefined) ? 'block;' : hideField;  
   var html="";
   html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">';  
   html+= '<label for="'+fieldName+'" class="col-sm-4 col-form-label '+validation+'" style="margin-top: 10px;">'+fieldLabel+'</label>';
   html+= '<div class="col-sm-7" style="margin-top: 10px;">';
   html+= '<input type="'+fieldType+'" class="form-control" id="'+fieldName+'" value="'+value+'" name="'+fieldName+'" '+readonly+' >';
   html+= '</div>';
   html+= '</div>';
   return html;
 }

 function hiddenFields(fieldName,value){
    var html="";
    html+= '<input type="hidden" id="'+fieldName+'" value="'+value+'" name="'+fieldName+'">';
    return html;
 }

function readOnlyFields(fieldLabel,fieldName,value,hideField,recordId,validation){
   hideField = (hideField == undefined) ? 'block;' : hideField; 
   var showChar = 100;
	var ellipsestext = "...";
	var moretext = '<span class="lnr icon-eye" aria-hidden="true"></span>';
	var lesstext = "less";
   var html="";
   html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
   html+= '<label for="'+fieldName+'" class="col-sm-4 col-form-label '+validation+'" style="margin-top: 10px;">'+fieldLabel+'</label>';
   html+= '<div class="col-sm-7" style="margin-top: 15px;">';
   html+= '<p class="">';
   if(value.length > showChar) {
        var c = value.substr(0, showChar);
        var h = value.substr(showChar-1, value.length - showChar);
        html+= ''+ c + '<span class="moreellipses">' + ellipsestext+ '&nbsp;</span>&nbsp;&nbsp;<a href="javascript:;" onclick="openValue('+tracker_id+','+form_id+',\''+fieldName+'\', '+'\''+fieldLabel+'\', '+recordId+');return false">'+moretext+'</a></span>';
    } else {
        html+=''+value+'</p>';
    }
   html+= '<input type="';
   if (recordId == 0 ) { html+= 'text'; } else { html+= 'hidden';}
   html+= '" class="form-control" id="'+fieldName+'" name="'+fieldName+'" value="'+value+'">';
   html+= '</div>';textAreaFields
   html+= '</div>';
   return html;
}

function (fieldLabel,fieldName,value,readonly,validation,hideField){
   hideField = (hideField == undefined) ? 'block;' : hideField; 
   var html="";
   html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
   html+= '<label for="'+fieldName+'" class="col-sm-4 col-form-label '+validation+'" style="margin-top: 10px;">'+fieldLabel+'</label>';
   html+= '<div class="col-sm-7" style="margin-top: 10px;">';
   html+= '<textarea class="form-control" id="'+fieldName+'"  placeholder="Comments." name="'+fieldName+'" '+readonly+'>'+value;
   html+= '</textarea></div>';
   html+= '</div>';
   return html;
}

function dropDownSingleSelectFields(fieldLabel,fieldName,options,value,disabled,validation,hideField){
   hideField = (hideField == undefined) ? 'block;' : hideField; 
   var html="";
   html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
   html+= '<label for="'+fieldName+'" class="col-sm-4 col-form-label '+validation+'">'+fieldLabel+'</label>';
   html+= '<div class="col-sm-7" style="margin-top: 10px;">';
   html+= '<select id="'+fieldName+'" class="form-control" name="'+fieldName+'" >';
   // if(value == ''){
        html+= '<option selected value="">Choose...</option>';
   // }
   for(var k in options) {
    if(value== options[k]['optionValue']){
        html+= '<option selected value="'+options[k]['optionValue']+'">'+options[k]['optionLabel']+'</option>';
     }else{
        html+= '<option value="'+options[k]['optionValue']+'">'+options[k]['optionLabel']+'</option>';
     }
   }
   html+= '</select>';
   html+= '</div>';
   html+= '</div>';
   return html;
}

function userRoleFields(fieldLabel,fieldName,options,value,disabled,validation,hideField){
   hideField = (hideField == undefined) ? 'block;' : hideField; 
   var html="";
   html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
   html+= '<label for="'+fieldName+'" class="col-sm-4 col-form-label '+validation+'">'+fieldLabel+'</label>';
   html+= '<div class="col-sm-7" style="margin-top: 10px;">';
   html+= '<select id="'+fieldName+'" class="form-control" name="'+fieldName+'" '+disabled+'>';
   //if(value == ''){
        html+= '<option selected value="">Choose...</option>';
   //}
   for(var k in options) {
    if(value== options[k]['u_name']){
        html+= '<option selected value="'+options[k]['u_name']+'">'+options[k]['u_name']+'</option>';
     }else{
        html+= '<option value="'+options[k]['u_name']+'">'+options[k]['u_name']+'</option>';
     }
   }
   html+= '</select>';
   html+= '</div>';
   html+= '</div>';
   return html;
}

function currentUserFields(fieldLabel,fieldName,value,hideField){
   hideField = (hideField == undefined) ? 'block;' : hideField; 
   var html="";
   html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
   html+= '<label for="'+fieldName+'" class="col-sm-4 col-form-label" style="margin-top: 10px;">'+fieldLabel+'</label>';
   html+= '<div class="col-sm-7" style="margin-top: 15px;">';
   html+= '<p class="text-justify text-sm-left">'+value+'</p>';
   html+= '</div>';
   html+= '</div>';
   html+= '<input type="hidden" class="form-control" id="'+fieldName+'" name="'+fieldName+'" value="'+value+'">';
   return html;
}

function integerFields(fieldLabel,fieldName,value,readonly,validation,hideField){
    hideField = (hideField == undefined) ? 'block;' : hideField; 
    var html="";
    html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
    html+= '<label for="'+fieldName+'" class="col-sm-4 col-form-label '+validation+'" style="margin-top: 10px;">'+fieldLabel+'</label>';
    html+= '<div class="col-sm-7" style="margin-top: 10px;">';
    html+= '<input type="number" class="form-control" id="'+fieldName+'" value="'+value+'" name="'+fieldName+'" '+readonly+' >';
    html+= '</div>';
    html+= '</div>';
    return html;
 }

function dateTypeFields(fieldLabel,fieldName,fieldType,value,dateFormat,validation,hideField){
    hideField = (hideField == undefined) ? 'block;' : hideField; 
    var html="";
    html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
    html+= '<label for="" class="col-sm-4 col-form-label '+validation+'" style="margin-top: 10px;">'+fieldLabel+'</label>';
    html+= '<div class="col-sm-7" style="margin-top: 10px;">';
    html+= '<input type="text" class="form-control datepick" id="'+fieldName+'" value="'+value+'" name="'+fieldName+'" format="'+dateFormat+'" fieldtype="'+fieldType+'"readOnly>';
    html+= '</div>';
    html+= '</div>';
    return html;
 }

function dateTimeTypeFields(fieldLabel,fieldName,fieldType,value,dateTimeFormat,validation,hideField){
    hideField = (hideField == undefined) ? 'block;' : hideField; 
    var html="";
    html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
    html+= '<label for="" class="col-sm-4 col-form-label '+validation+'" style="margin-top: 10px;">'+fieldLabel+'</label>';
    html+= '<div class="col-sm-7" style="margin-top: 10px;">';
    html+= '<input type="text" class="form-control datetimepick" id="'+fieldName+'" value="'+value+'" name="'+fieldName+'" format="'+dateTimeFormat+'" fieldtype="'+fieldType+'" readOnly>';
    html+= '</div>';
    html+= '</div>';
    return html;
 }

function headingFields(value){
    var html="";
    html+= '<div class="col-md-12 col-form-label" style="margin-top: 15px;">';
    html+= '<h4>'+value+'</h4>';
    html+= '<hr></div>';
    return html;
 }

function checkBoxFields(fieldLabel,fieldName,options,valueArr,textAreaValue,disabled,validation,hideField){ 
   hideField = (hideField == undefined) ? 'block;' : hideField; 
   var html="";
   html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
   html+= '<label for="" class="col-sm-4 col-form-label '+validation+'" style="margin-top: 10px;">'+fieldLabel+'</label>';
   html+= '<div class="col-sm-7 col-form-label">';
   var checkedStatus=0;
   valueArr = valueArr.split(',');
   for(var k in options) {
      html+= '<div class="form-check">';
      var id = options[k]['optionValue'].replace(/[^a-zA-Z0-9]/g, "");
      
      if (valueArr.indexOf(options[k]['optionValue']) > -1) {
         checkedStatus=1;
         html+= '<input class="form-check-input" type="checkbox" kpi="'+options[k]['kpi']+'" id="'+fieldName+id+'" name="'+fieldName+'" value="'+options[k]['optionValue']+'" checked '+disabled+'>';
      } else {
         html+= '<input class="form-check-input" type="checkbox" kpi="'+options[k]['kpi']+'" id="'+fieldName+id+'" name="'+fieldName+'" value="'+options[k]['optionValue']+'" '+disabled+'>';
      }
      html+= '<label class="form-check-label" for="'+fieldName+options[k]['optionValue']+'">';
      html+= options[k]['optionLabel'];
      html+= '</label>';
      html+= '</div>';
   }
   
   html+= '</div>';
   /*html+= '<div class="col-12 row">';
   html+= '<div class="col-sm-4 col-form-label" style="margin-top: 10px;"></div>';
   if(checkedStatus==1){
      html+= '<div class="col-sm-7" style="margin-top: 10px;" id="comment_checkbox_div_'+fieldName+'">';
   } else {
      html+= '<div class="col-sm-7" style="margin-top: 10px;display:none" id="comment_checkbox_div_'+fieldName+'">';
   }
   html+= '<textarea class="form-control" id="comment_checkbox_'+fieldName+'" name="comment_checkbox_'+fieldName+'" '+disabled+' placeholder="Comments." >'+textAreaValue;
   html+= '</textarea></div>';
   html+= '</div>';*/
   html+= '</div>';
   
   return html;
 }

function formulaFields(fieldLabel,fieldName,fieldType,value,formula,formula_dep,hideField){
   hideField = (hideField == undefined) ? 'block;' : hideField; 
   var html="";
   html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
   html+= '<label for="'+fieldName+'" class="col-sm-4 col-form-label" style="margin-top: 10px;">'+fieldLabel+'</label>';
   html+= '<div class="col-sm-7" style="margin-top: 10px;">';
   html+= '<input type="'+fieldType+'" class="form-control formula" id="'+fieldName+'" value="'+value+'" name="'+fieldName+'" formula="'+formula+'" formula_dep="'+formula_dep+'" ReadOnly>';
   html+= '</div>';
   html+= '</div>';
   return html;
}

function formulaeDropDownFields(fieldLabel,fieldName,options,value,formula,formula_dep,hideField){
   hideField = (hideField == undefined) ? 'block;' : hideField; 
   var html="";
   html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
   html+= '<label for="'+fieldName+'" class="col-sm-4 col-form-label">'+fieldLabel+'</label>';
   html+= '<div class="col-sm-7" style="margin-top: 10px;">';
   html+= '<select id="'+fieldName+'" class="form-control formulacombobox" name="'+fieldName+'" formula="'+formula+'" formula_dep="'+formula_dep+'" >';
   //if(value == ''){
        html+= '<option selected value="">Choose...</option>';
   //}
   for(var k in options) {
    if(value== options[k]['optionValue']){
        html+= '<option selected value="'+options[k]['optionValue']+'">'+options[k]['optionLabel']+'</option>';
     }else{
        html+= '<option value="'+options[k]['optionValue']+'">'+options[k]['optionLabel']+'</option>';
     }
   }
   html+= '</select>';
   html+= '</div>';
   html+= '</div>';
   return html;
}

function formulaDateFields(fieldLabel,fieldName,fieldType,value,formula,formula_dep,dateFormat,hideField){
   hideField = (hideField == undefined) ? 'block;' : hideField; 
   var html="";
   html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
   html+= '<label for="'+fieldName+'" class="col-sm-4 col-form-label" style="margin-top: 10px;">'+fieldLabel+'</label>';
   html+= '<div class="col-sm-7" style="margin-top: 10px;">';
   html+= '<input type="'+fieldType+'" class="form-control formuladate" id="'+fieldName+'" value="'+value+'" name="'+fieldName+'" formula="'+formula+'" formula_dep="'+formula_dep+'" format="'+dateFormat+'" ReadOnly>';
   html+= '</div>';
   html+= '</div>';
   return html;
}

function fileFields(fieldLabel,fieldName,value,validation,hideField){
   hideField = (hideField == undefined) ? 'block;' : hideField; 
   var html="";
   html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
   html+= '<label for="file" class="col-sm-4 col-form-label '+validation+'" style="margin-top: 10px;">'+fieldLabel+'</label>';
   html+= '<div class="col-sm-7" style="margin-top: 10px;">';
   var keyname=btoa('workflowFiles/'+value);
   if (value!='') {
      html+= '<div style="margin-top: 10px;" id="file_upload_link_'+fieldName+'">';
      html+= '<a href="/awsfiles/downloadFilesFromAws/'+keyname+'/'+btoa(value)+'" class="stretched-link" title="'+value+'">Download attachment</a>';
      html+= '<button type="button" class="btn btn-link text-danger deleteUploadedFile" data-file="'+fieldName+'">Delete File</button>';
      html+= '</div>';
      html+= '<div id="file_upload_div_'+fieldName+'" class="custom-file">';
   
      html+= '</div>';
   } else {
      html+= '<div class="custom-file">';
      html+= '<input type="file" class="custom-file-input form-control" id="'+fieldName+'"  name="'+fieldName+'">';
      html+= '<label class="custom-file-label" for="'+fieldName+'">Choose file</label>';
      html+= '</div>';
   }
   html+= '<span id="file_error_'+fieldName+'" class="error"></span>';
   html+= '</div>';
   html+= '</div>';
   return html;
}

function dependentTextFields(fieldLabel,fieldName,value,formula,formula_dep,hideField){
   hideField = (hideField == undefined) ? 'block;' : hideField; 
   var html="";
   html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
   html+= '<label for="'+fieldName+'" class="col-sm-4 col-form-label" style="margin-top: 10px;">'+fieldLabel+'</label>';
   html+= '<div class="col-sm-7" style="margin-top: 10px;">';
   html+= '<input type="text" class=" dependentText form-control" id="'+fieldName+'" value="'+value+'" name="'+fieldName+'" formula="'+formula+'" formula_dep="'+formula_dep+'">';
   html+= '</div>';
   html+= '</div>';
   return html;
}

function fileFieldsDownload(fieldLabel,fieldName,value,hideField){
   hideField = (hideField == undefined) ? 'block;' : hideField; 
   var html="";
   html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
   html+= '<label for="'+fieldName+'" class="col-sm-4 col-form-label" style="margin-top: 10px;">'+fieldLabel+'</label>';
   html+= '<div class="col-sm-7" style="margin-top: 15px;">';
   var keyname=btoa('workflowFiles/'+value);
   html+= '<a href="/aws/downloadFilesFromAws/'+keyname+'/'+btoa(value)+'">'+value+'</a>';
   html+= '</div>';
   html+= '</div>';
   html+= '<input type="hidden" class="form-control" id="'+fieldName+'" name="'+fieldName+'" value="'+value+'">';
   return html;
}

function hiddenFormulaFields(fieldName,formula,formula_dep,value){
   var html="";
   html+= '<input type="hidden" class="form-control formula" id="'+fieldName+'" value="'+value+'" name="'+fieldName+'" formula="'+formula+'" formula_dep="'+formula_dep+'">';
   return html;
}
function MultivalueFields(fieldLabel,fieldName,value,hideField,multipleValues,recordId,validation){
    hideField = (hideField == undefined) ? 'block;' : hideField; 
    var html="";
    html+= '<div class="col-6 row" id="id_'+fieldName+'" style="display:'+hideField+'">'; 
    html+= '<label for="'+fieldName+'" class="col-sm-4 col-form-label '+validation+'" style="margin-top: 10px;">'+fieldLabel+'</label>';
    html+= '<div class="col-sm-7" style="margin-top: 15px;">';
        $.each(multipleValues, function(key,val){
            if (value == val) {
                html+= '<p class="" style="font-weight:bold;">'+val+'</p>';
            } else {
                html+= '<p class="">'+val+'</p>';
            }            
        });
    html+= '<input type="';
    if (recordId == 0 ) { html+= 'text'; } else { html+= 'hidden';}
    html+= '" class="form-control" id="'+fieldName+'" name="'+fieldName+'" value="'+value+'">';
    html+= '</div>';
    html+= '</div>';
    return html;                       
}
