/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$(document.body).on('change','#active_substaance', function(){
    var actSub = $("#active_substaance").val();//alert(actSub.indexOf('\''));return false;
    $("#forActiveSubstanceId").html('');
    if(actSub == null || actSub == ''){
        $("#forActiveSubstanceId").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','active substance'));
    }else if ((actSub.indexOf('\'') >= 0) || (actSub.indexOf('\"') >= 0) ) {
       $("#forActiveSubstanceId").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','active substance'));
    } else if (actSub.length > 200) {
       $("#forActiveSubstanceId").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','active substance').replace('#char','200')); 
    }
});

$(document.body).on('change','#productIds', function(){
    var productIds = $("#productIds").val();
    $("#forProductIds").html('');
    if(productIds == null || productIds == ''){
        $("#forProductIds").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','product'));
    }else if (!productIds.toString().match(/^[a-zA-Z0-9\,.-]+$/)) {
       $("#forProductIds").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','product'));
    } 
});

$(document.body).on('change','#reason', function(){
    var reason = $("#reason").val();
    $("#forReason").html('');
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    }
});

function addNewActiveSubstance(trackerId, formId) {

    var actSubName = $("#active_substaance").val();
    var productIds = $("#productIds").val();
    var csrf = $("#csrf").val();
    var reason = $("#reason").val();      
        
    var archived = 0;
    
    $("#forActiveSubstanceId").html('');
    $("#forProductIds").html('');
    $("#forReason").html('');   
    $("#activeSubstanceErrorMessages").html('');
    
    var count = 0;
    if(actSubName == null || actSubName == ''){
        $("#forActiveSubstanceId").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','active substance'));
        count++;
    } else if ((actSubName.indexOf('\'') >= 0) || (actSubName.indexOf('\"') >= 0)) {
       $("#forActiveSubstanceId").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','active substance'));
        count++; 
    } else if (actSubName.length > 200) {
       $("#forActiveSubstanceId").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','active substance').replace('#char','200')); 
    }
    
    
    if(productIds == null || productIds == ''){
        $("#forProductIds").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','product'));
        count++;
    }else if (!productIds.toString().match(/^[a-zA-Z0-9\,.-]+$/)) {
       $("#forProductIds").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','product'));
       count++;
    }
    
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
        count++;
    }    
    
    if (count == 0) {
        var data = {
            actSubName : actSubName,
            productIds : productIds,
            archived : archived,
            trackerId : trackerId,
            reason : reason
            };
        var url = "/activesubstance/activeSubstanceCheck/"+trackerId;
        $.post(url, data,function(respJson){
            var resp = JSON.parse(respJson);//alert(resp);return false;
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            if((responseCode == 1 || responseCode == 2) && (responseCode != null)){
                window.location.assign('/activesubstance/activesubstance_management/'+trackerId+'/'+formId);
            }
            else{
                $("#forActiveSubstanceId").html(errMessage);
            }
        });
    }
}

function editActiveSubstance(trackerId, activeSubstancesId, formId) {
    
    var actSubName = $("#active_substaance").val();
    var productIds = $("#productIds").val();
    var reason = $("#reason").val();      
        
    var archived = 0;
    
    $("#forActiveSubstanceId").html('');
    $("#forProductIds").html('');
    $("#forReason").html('');   
    $("#activeSubstanceErrorMessages").html('');
    
    var count = 0;
    if(actSubName == null || actSubName == ''){
        $("#forActiveSubstanceId").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','active substance'));
        count++;
    } else if ((actSubName.indexOf('\'') >= 0) || (actSubName.indexOf('\"') >= 0)) {
       $("#forActiveSubstanceId").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','active substance'));
        count++; 
    } else if (actSubName.length > 200) {
       $("#forActiveSubstanceId").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','active substance').replace('#char','200')); 
    }
    
    
    if(productIds == null || productIds == ''){
        $("#forProductIds").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','product'));
        count++;
    }else if (!productIds.toString().match(/^[a-zA-Z0-9\,.-]+$/)) {
       $("#forProductIds").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','product'));
       count++;
    }
    
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
        count++;
    }    
    
    if (count == 0) {
        var data = {
            actSubName : actSubName,
            activeSubstancesId : activeSubstancesId,
            productIds : productIds,
            archived : archived,
            trackerId : trackerId,
            formId : formId,
            reason : reason
            };
        var url = "/activesubstance/saveEditActiveSubstance/"+trackerId;
        $.post(url, data,function(respJson){
            var resp = JSON.parse(respJson);
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            if((responseCode == 1 || responseCode == 2) && (responseCode != null)){
                window.location.assign('/activesubstance/activesubstance_management/'+trackerId+'/'+formId);
            }
            else{
                $("#forActiveSubstanceId").html(errMessage);
            }
        });
    }
}