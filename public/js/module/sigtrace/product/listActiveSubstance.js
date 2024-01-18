window.setInterval(function(){
  $('#alert').removeClass('alert-success').hide().html('');
}, 3000);
$(document).ready(function() {
    $('.datatable').dataTable( {
        bDestroy: true,
        aaSorting: [[2, 'desc']],
    });
});

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
function deleteActiveSubstanceAction(id,tid,act_sub_name,fid) { 
    var reason = $("#reason_"+id).val();
    $('#forReason_'+id).html("");
    $('#substanceErrorMessages').html("");
    if (reason == null || reason == '') {
        $('#forReason_'+id).html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','reason'));
    } else {
        $.ajax({
            url: "/activesubstance/deleteactivesubstance/"+tid+"/"+fid+"/"+id,
            type:'post',
            dataType:'json',
            data:{'substance_id':id,'tracker_id':tid,'form_id':fid,'substance_name':act_sub_name,'comment':reason},
            success:function(data) {//alert(data);return false;
                if(data == 'deleted')
                {
                    window.location.assign('/activesubstance/activesubstance_management/'+tid+'/'+fid);
                } else if (data == 'error') {
                    $('#activeSubstanceErrorMessages').html("Due to some error could not able to active substance.");
                }
            }
         })
    }
}

function reloadPopUp(id) {
   $('#reason_'+id).val(""); 
   $('#forReason_'+id).html(""); 
}

