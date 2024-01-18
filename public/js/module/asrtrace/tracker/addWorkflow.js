$(document).ready(function() {
    var jsonString = JSON.stringify(jsonData);
    count=1;
    $('#inputWfHidden').val(jsonString);
    $("#tabledivbody").sortable({
        items: "tr",
        cursor: 'move',
        opacity: 0.6,
        update: function() {
            sendOrderToServer();
        }
    });
    setTimeout(function(){$('#fashMessage').remove();}, 3000);
});	
$(document.body).on('change','#reason', function(){
    var reason = $("#addcommentforworkflow").val();
    $("#commenterrorforworkflow").html('');
    if(reason == null || reason == ''){
        $("#commenterrorforworkflow").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
    } 
});
    function sendOrderToServer() {
        var elem = document.getElementsByClassName("sort_order_td");
        var max_sort_num = parseInt($('#inputMaxWfHidden').val());
        for (var i = 0; i < elem.length; ++i) {
            elem[i].innerHTML = "#"+(max_sort_num+1+i);
        }
        var elem = document.getElementsByClassName("sort_rder");
        
        for (var i = 0; i < elem.length; ++i) {
            elem[i].value = max_sort_num+1+i;
        }
    }
    function insSpec()
    {
        var wf_arr = JSON.parse(jsonData);
        var selid = random_string(6);
        //var selid = 'questionID'+str;
        var html = "";
        html += '<tr class="sectionsid">';
        html += '<td><span class="sort_order_td">#1</span><input class="sort_rder" value="" type="hidden"/></td>';
        html += '<td>';
        html += '<input list="workflowType_'+selid+'" name="workflowStep_'+selid+'" class="form-control workflowSelect"/>';
//        html += '<datalist placeholder="Workflow name" id="workflowType_'+selid+'">';
//        var countWf = wf_arr.length;
//        for(var m=0; m < countWf; m++){
//            var wf_name = wf_arr[m];
//            html += '<option value="'+wf_name+'">'+wf_name+'</option>';
//        }
//        html += '</datalist>';
        html +='<div class="error" id="forWorkflowName_'+selid+'"></div>';
        html += '</td>';
        html += '<td align="center">';
        html+= '<i class="lnr icon-circle-minus" style="color: red; cursor: pointer;" onClick="delSuspectProduct(this)" title="Delete"></i>';
        html += '</td>';
        $("#tabledivbody").append(html);
        sendOrderToServer();
    }
    function random_string(size){
        var str = "";
        for (var i = 0; i < size; i++){
            str += random_character();
        }
        return str;
    }
    
    function random_character() {
        var chars = "lmnopqurstuvwxyzABCDEFGHIJKLMNOPQURSTUVWXYZ";
        return chars.substr( Math.floor(Math.random() * 62), 1);
    }
    function delSuspectProduct(node)
    {
        r = node.parentNode.parentNode;
        r.parentNode.removeChild(r);
        sendOrderToServer();
    }

    function addWorkflow(subType)
    {
        $("#workflowErrorMessages").html('');
        $("#commenterrorforworkflow").html('');
        $("#forWorkflowName").html('');
        $("#status").html('');
        
        var count = 0;
        var duplicate = 0;
        
        var reason = $("#addcommentforworkflow").val();
        if(reason == null || reason == ''){
            $("#commenterrorforworkflow").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            count++;
        }
        var elem = document.getElementsByClassName("workflowSelect");
        
        var wf_names = [];
        for (var i = 0; i < elem.length; ++i) {  
            if (typeof elem[i].value !== "undefined") {
                var workflow_name = elem[i].value.trim();
                var errId = elem[i].name.split('_')[1];
                $('#forWorkflowName_'+errId).html('');
                if (workflow_name == '') {
                   $('#forWorkflowName_'+errId).html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Workflow Name')); 
                   count++;
                } else if (!workflow_name.match(/^[a-zA-Z][a-zA-Z0-9 ]+$/)) {
                    $('#forWorkflowName_'+errId).html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Workflow Name'));
                    count++;
                } else if (workflow_name.length > 200) {
                    $('#forWorkflowName_'+errId).html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Workflow Name').replace('#char','200'));
                    count++;
                } else if (wf_names.indexOf(workflow_name) == -1) {
                    wf_names.push(workflow_name);
                } else {
                    duplicate++;
                }
            }
        }

        if(count === 0) {
            if (duplicate > 0) {
                $("#workflowErrorMessages").html('Duplicate Workflow Names are not allowed');
                return false;
            }
            var elem = document.getElementsByClassName("sort_rder");
            var wf_sort_order = [];
            for (var i = 0; i < elem.length; ++i) {
                 if (typeof elem[i].value !== "undefined") {
                    wf_sort_order.push(elem[i].value);
                }
            }

            var error_index = document.getElementsByClassName("errorworkflow");
            var index_of_error = [];
            for (var i = 0; i < error_index.length; ++i) {
                    index_of_error.push(error_index[i].id);
            }
            $("#status").html('processing...');
            var data = {
                wfNames : wf_names,
                indexOfError : index_of_error,
                formId: formId,
                trackerId: trackerId,
                subType : subType,
                wfSortOrder : wf_sort_order,
                reason : reason
            };
            var url = "/workflow/addUpdateWorkflow/"+trackerId+"/"+formId;
            $.post(url, data,function(respJson){
                var resp = JSON.parse(respJson);
                $("#status").html('');
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                if(responseCode == 1){
                    window.setTimeout('window.location.replace("/workflow/workflow_management/'+trackerId+'/'+formId+'")', 1000);
                }
                else if(responseCode == 2){
                    $.each(errMessage, function(idx, obj){ 
                        $("#"+idx).show();
                        $("#"+idx).html('<font color="#cc0000">'+obj+'</font>');
                    });
                }  
                else{
                    $("#workflowErrorMessages").html(errMessage);
                }
            });
        } else {
            return false;
        }
    }