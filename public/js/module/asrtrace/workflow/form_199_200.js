/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function() {
    
});  
    
$("input[name='qualitycheck[]']").on("click", function(){
    var res = 10 - Math.floor((($("input[name='qualitycheck[]']:checked").length)/($("input[name='qualitycheck[]']").length))*10);
    $('#qualitycount').val(res);
}); 

$("input[name='reviewerrors[]']").on("click", function(){
    var res = 10 - Math.floor((($("input[name='reviewerrors[]']:checked").length)/($("input[name='reviewerrors[]']").length))*10);
    $('#dslreviewqualitycount').val(res);
});

$(function() {        
    var res = 10 - Math.floor((($("input[name='qualitycheck[]']:checked").length)/($("input[name='qualitycheck[]']").length))*10);
    $('#qualitycount').val(res);
    var res = 10 - Math.floor((($("input[name='reviewerrors[]']:checked").length)/($("input[name='reviewerrors[]']").length))*10);
    $('#dslreviewqualitycount').val(res);
    $(".formulacombo").each(function() {
        var val = $(this).val();
        if(val=="completed"||val=="Completed") {
            $("#" + this.id + " option:not(:selected)").attr('disabled', true);
        }
    });

    $('.comboclass').each(function() {
        var val = $(this).val();
        if(val=="") {
                $("#" + this.id ).prop("disabled","disabled");
        }
    });
});

Number.prototype.mod = function(n) {
    return ((this%n)+n)%n;
};

$('#validatedsignal').on("change",function(){
    if(this.value == 'No'){
        $('#ifnovalidatedsignalreason').parent().parent().parent().parent().parent().show();  
    }else{
        $('#ifnovalidatedsignalreason').parent().parent().parent().parent().parent().hide(); 
    }
});
    
