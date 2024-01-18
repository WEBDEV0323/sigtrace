/**
 * Created by saritatewari on 26/11/15.
 */


function save_role_settings(role_id, form_id)
{

    var can_insert = $("#id_can_insert_"+role_id+"_"+form_id).val();
    var can_delete = $("#id_can_delete_"+role_id+"_"+form_id).val();
    $("#status_"+role_id+"_"+form_id).html('processing...');

    if(can_insert == ""  && can_delete == ""){
        $("#status_"+role_id+"_"+form_id).html('<font color="red">Select Values</font>');
        window.setTimeout("closeUserCreateAlert('status_"+role_id+"_"+form_id+"')", 3000);
        return false;
    }
    var data = {
        //can_read : can_read,
        can_insert : can_insert,
        can_delete: can_delete,
        role_id:role_id,
        form_id : form_id
    }
    var url = "/tracker/saveaccesssetting";
    $.post(url, data,function(respJson){
        var resp =JSON.parse(respJson);
        var responseCode = resp.responseCode;
        var errMessage = resp.errMessage;
        if(responseCode == 1){
            $("#status_"+role_id+"_"+form_id).html('<font color="#088A08">'+errMessage+'</font>');
            window.setTimeout("closeUserCreateAlert('status_"+role_id+"_"+form_id+"')", 3000);
        }
        else{
            $("#status").html('<font color="#cc0000">'+errMessage+'</font>');
            window.setTimeout("closeUserCreateAlert('status_"+role_id+"_"+form_id+"')", 3000);
        }
    });
}
function closeUserCreateAlert(id) {
    $("#"+id).html('');
}

function save_update_settings(form_id,role_id, workflow_id)
{

    var update='';
    var workflow_ids = [];
    var can_updates=[];
    $("select[id^='id_can_update_"+form_id+"_"+role_id+"']").each(function () {
        var res=(this.id).split('_');
        var id=this.id;
        var can_update = $("#"+id).val();
        if( can_update != ""){
            update=can_update;
        }
        workflow_ids.push(res[5]);
        can_updates.push(can_update);

    });
    $("#status_update_"+form_id+"_"+role_id+"_"+workflow_id).html('processing...');
    if( update == ""){
        $("#status_update_"+form_id+"_"+role_id+"_"+workflow_id).html('<font color="red">Select Values</font>');
        window.setTimeout("closeUserCreateAlert('status_"+form_id+"_"+role_id+"_"+workflow_id+"')", 3000);
        return false;
    }
    var data = {
        can_update : can_updates,
        role_id:role_id,
        workflow_id : workflow_ids
    }
    var url = "/tracker/saveupdatesetting";
    $.post(url, data,function(respJson){
        var resp =JSON.parse(respJson);
        var responseCode = resp.responseCode;
        var errMessage = resp.errMessage;
        if(responseCode == 1){
            $("#status_update_"+form_id+"_"+role_id+"_"+workflow_id).html('<font color="#088A08">'+errMessage+'</font>');
            window.setTimeout("closeUserCreateAlert('status_"+form_id+"_"+role_id+"_"+workflow_id+"')", 3000);
        }
        else{
            $("#status_update_").html('<font color="#cc0000">'+errMessage+'</font>');
            window.setTimeout("closeUserCreateAlert('status_update_"+form_id+"_"+role_id+"_"+workflow_id+"')", 3000);
        }
    });
}



function save_read_settings(form_id,role_id, workflow_id)
{
    var read='';
    var workflow_ids = [];
    var can_reads=[];

    $("select[id^='id_can_read_"+form_id+"_"+role_id+"']").each(function () {
        var res=(this.id).split('_');
        var id=this.id;
        var can_read = $("#"+id).val();
        if( can_read != ""){
            read=can_read;
        }
        workflow_ids.push(res[5]);
        can_reads.push(can_read);

    });
    $("#status_read_"+form_id+"_"+role_id+"_"+workflow_id).html('processing...');
    if( read == ""){
        $("#status_read_"+form_id+"_"+role_id+"_"+workflow_id).html('<font color="red">Select Values</font>');
        window.setTimeout("closeUserCreateAlert('status_read_"+form_id+"_"+role_id+"_"+workflow_id+"')", 3000);
        return false;
    }
    var data = {
        can_read: can_reads,
        role_id:role_id,
        workflow_id : workflow_ids
    }
    var url = "/tracker/savereadsetting";
    $.post(url, data,function(respJson){
        var resp =JSON.parse(respJson);
        var responseCode = resp.responseCode;
        var errMessage = resp.errMessage;
        if(responseCode == 1){
            $("#status_read_"+form_id+"_"+role_id+"_"+workflow_id).html('<font color="#088A08">'+errMessage+'</font>');
            window.setTimeout("closeUserCreateAlert('status_read_"+form_id+"_"+role_id+"_"+workflow_id+"')", 3000);
        }
        else{
            $("#status_read_").html('<font color="#cc0000">'+errMessage+'</font>');
            window.setTimeout("closeUserCreateAlert('status_read_"+form_id+"_"+role_id+"_"+workflow_id+"')", 3000);
        }
    });
}



function closeUserCreateAlert(id) {
    $("#"+id).html('');
}
