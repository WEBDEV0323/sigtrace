window.setInterval(function(){
  $('#alert').removeClass('alert-success').hide().html('');
}, 3000);
$(document).ready(function() {
    $('.datatable').dataTable( {
        bDestroy: true,
        aaSorting: [[2, 'desc']],
    });
});
function deleteProduct(id, tid, fid){
    var reason = $("#reason_"+id).val();
    if (reason == null || reason == '') {
        $('#forReason_'+id).html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    } else {
        $.ajax({  
            type: "POST", 
            url: "/product/deleteProduct",             
            data:{'id':id,'trackerId':tid,'reason':reason},
            success: function(respJson){
                var resp =JSON.parse(respJson);
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                if ((responseCode == 1 || responseCode == 2) && (responseCode != null)) {
                window.location.assign('/product/'+tid+'/'+fid);
                }
                else{
                    $("#forActiveSubstanceId").html(errMessage);
                }
            }
        });
    }
}

$(document).ready(function() {
    $('#list_of_substance').dataTable( {
            "bDestroy": true,
            "bScrollInfinite": true,
            "bScrollCollapse": true,
            "paging":         true,
            "aLengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
            "order": [],
            "columnDefs": [
                { targets: 0, orderable: false }
            ]
    });
    
    setTimeout(function(){$('#fashMessage').remove();}, 3000);
});

function reloadPopUp(id) {
   $('#reason_'+id).val(""); 
   $('#forReason_'+id).html(""); 
}

$(document.body).on('change','#product_name', function(){
    var prodName = $("#product_name").val();//alert(actSub.indexOf('\''));return false;
    $("#forProductName").html('');
    if(prodName == null || prodName == ''){
        $("#forProductName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','product'));
    }else if ((prodName.indexOf('\'') >= 0) || (prodName.indexOf('\"') >= 0) ) {
       $("#forProductName").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','product'));
    } else if (prodName.length > 200) {
       $("#forProductName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','product').replace('#char','200')); 
    }
});

$(document.body).on('change','#reason', function(){
    var reason = $("#reason").val();
    $("#forReason").html('');
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    }
});


function editProduct(trackerId, productIds, formId) {
    
    var productName = $("#product_name").val();
    var productCode = $("#product_code").val();
    var reason = $("#reason").val();      
        
    var archived = 0;
    
    $("#forProductName").html('');
    $("#forReason").html('');   
    $("#productErrorMessages").html('');
    
    var count = 0;
    if(productName == null || productName == ''){
        $("#forActiveSubstanceId").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','product'));
        count++;
    } else if ((productName.indexOf('\'') >= 0) || (productName.indexOf('\"') >= 0)) {
       $("#forActiveSubstanceId").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','product'));
        count++; 
    } else if (productName.length > 200) {
       $("#forActiveSubstanceId").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','product').replace('#char','200')); 
    }
    
    if(reason == null || reason == ''){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
        count++;
    }    
    
    if (count == 0) {
        var data = {
            productName : productName,
            productCode : productCode,
            productIds : productIds,
            archived : archived,
            trackerId : trackerId,
            reason : reason
            };
        var url = "/product/productCheck/"+trackerId;
        $.post(url, data,function(respJson){
            var resp = JSON.parse(respJson);
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