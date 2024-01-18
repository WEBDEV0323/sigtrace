/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$(document.body).on('change','#medical_concept', function(){
    var medicalConcept = $("#medical_concept").val();//alert(actSub.indexOf('\''));return false;
    $("#forMedicalConceptId").html('');
    if(medicalConcept == null || medicalConcept == ''){
        $("#forMedicalConceptId").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','medical concept'));
    }else if ((medicalConcept.indexOf('\'') >= 0) || (medicalConcept.indexOf('\"') >= 0) ) {
       $("#forMedicalConceptId").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','medical concept'));
    } else if (medicalConcept.length > 200) {
       $("#forMedicalConceptId").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','medical concept').replace('#char','200')); 
    }
});

$(document.body).on('change','#pt_type', function(){
    var type = $("#pt_type").val();
    $("#forPreferredTermType").html('');
    if(type == null || type == ''){
        $("#forPreferredTermType").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','preferred term type'));
    }
});

//$(document.body).on('change','#preferredTermIds', function(){
//    var preferredTermIds = $("#preferredTermIds").val();
//    var type = $("#pt_type").val();
//    $("#forPreferredTermIds").html('');
//    if((preferredTermIds == null || preferredTermIds == '') && type == 'Medical Concept' ){
//        $("#forPreferredTermIds").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','preferred term'));
//    }
//});

$(document.body).on('change','#reason', function(){
    var reason = $("#reason").val();
    $("#forReason").html('');
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    }
});

$(document.body).on('change','#pt_type', function(){
    var type = $("#pt_type").val();//alert(type);return false;
    if (type == 'Medical Concept') {
        $('#pt_id').show();
    } else {
        $('#pt_id').hide();
    }    
});

function addNewMedicalConcept(trackerId, formId, actSubId) {

    var medicalConceptName = $("#medical_concept").val();
    var preferredTermIds = $("#preferredTermIds").val();
    var reason = $("#reason").val();      
    var type = $("#pt_type").val();
        
    var archived = 0;
    
    $("#forMedicalConceptId").html('');
    $("#forPreferredTermType").html('');
    $("#forPreferredTermIds").html('');
    $("#forReason").html('');   
    $("#medicalConceptErrorMessages").html('');
    
    var count = 0;
    if(medicalConceptName == null || medicalConceptName == ''){
        $("#forMedicalConceptId").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','medical concept'));
        count++;
    } else if ((medicalConceptName.indexOf('\'') >= 0) || (medicalConceptName.indexOf('\"') >= 0)) {
       $("#forMedicalConceptId").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','medical concept'));
        count++; 
    } else if (medicalConceptName.length > 200) {
       $("#forMedicalConceptId").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','medical concept').replace('#char','200')); 
    }
    
    if((type == null || type == '')){
        $("#forPreferredTermType").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','preferred term type'));
        count++;
    }
    
//    if((preferredTermIds == null || preferredTermIds == '') && type == 'Medical Concept'){
//        $("#forPreferredTermIds").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','preferred term'));
//        count++;
//    }
    
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
        count++;
    }    
    
    if (count == 0) {
        var data = {
            medicalConceptName : medicalConceptName,
            preferredTermIds : preferredTermIds,
            actSubId : actSubId,
            type : type,
            archived : archived,
            trackerId : trackerId,
            formId : formId,
            reason : reason
            }
        var url = "/medicalconcept/medicalConceptCheck/"+trackerId;
        $.post(url, data,function(respJson){
            var resp = JSON.parse(respJson);//alert(resp);return false;
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            if((responseCode == 1 || responseCode == 2) && (responseCode != null)){
                window.location.assign('/medicalconcept/medicalconcept_management/'+trackerId+'/'+formId+'/'+actSubId);
            }
            else{
                $("#forMedicalConceptId").html(errMessage);
            }
        });
    }
}
