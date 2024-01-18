/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$(document.body).on('change','#product_name', function(){
    var productName = $("#product_name").val();//alert(actSub.indexOf('\''));return false;
    $("#forProductName").html('');
    if(productName == null || productName == ''){
        $("#forProductName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','product name'));
    }else if ((productName.indexOf('\'') >= 0) || (productName.indexOf('\"') >= 0) ) {
       $("#forProductName").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','product name'));
    } else if (productName.length > 200) {
       $("#forProductName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','product name').replace('#char','200')); 
    }
});

$(document.body).on('change','#product_code', function(){
    var productCode = $("#product_code").val();//alert(actSub.indexOf('\''));return false;
    $("#forProductCode").html('');
    if(productCode == null || productCode == ''){
        $("#forProductCode").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','product code'));
    }else if ((productCode.indexOf('\'') >= 0) || (productCode.indexOf('\"') >= 0) ) {
       $("#forProductCode").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','product code'));
    } else if (productCode.length > 3) {
       $("#forProductCode").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','product code').replace('#char','3')); 
    }
});

$(document.body).on('change','#reason', function(){
    var reason = $("#reason").val();
    $("#forReason").html('');
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    }
});

function addNewProduct(trackerId, formId) {

    var productName = $("#product_name").val();
    var productCode = $("#product_code").val();
    var reason = $("#reason").val();      
        
    var archived = 0;
    
    $("#forProductName").html('');
    $("#forReason").html('');   
    $("#productErrorMessages").html('');
    
    var count = 0;
    if(productName == null || productName == ''){
        $("#forProductName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','product name'));
        count++;
    } else if ((productName.indexOf('\'') >= 0) || (productName.indexOf('\"') >= 0)) {
       $("#forProductName").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','product name'));
        count++; 
    } else if (productName.length > 200) {
       $("#forProductName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','product name').replace('#char','200')); 
    }
    
    if(productCode == null || productCode == ''){
        $("#forProductCode").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','product code'));
        count++;
    } else if ((productCode.indexOf('\'') >= 0) || (productCode.indexOf('\"') >= 0)) {
       $("#forProductCode").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','product code'));
        count++; 
    } else if (productCode.length > 3) {
       $("#forProductCode").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','product code').replace('#char','3')); 
       count++; 
    }
    
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
        count++;
    }    
    
    if (count == 0) {
        var data = {
            productName : productName,
            productCode : productCode,
            archived : archived,
            trackerId : trackerId,
            reason : reason
            }; //alert(productName);return false;
        var url = "/product/saveProduct/"+trackerId;
        $.post(url, data,function(respJson){
            var resp = JSON.parse(respJson);//alert(resp);return false;
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            if((responseCode == 1 || responseCode == 2) && (responseCode != null)){
                window.location.assign('/product/'+trackerId+'/'+formId);
            }
            else{
                $("#forProductName").html(errMessage);
            }
        });
    }
}