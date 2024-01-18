$(document).ready(function() {
    $("#codeList_add_new").click(function()
    {
        $('#commentForm_newDodeList').html("");
        $('#codelistAddErrorMessage').html("");
        var str = random_string(6);
        var selid = 'questionID' + str;
        var html = '';
        html += '<div class="form-group row">';
        html += '<label class="col-sm-4"></label>';
        html += '<div id="codelistAddErrorMessage" class="error col-sm-7"></div>';
        html += '</div>';
        html += '<div class="form-group row">';
        html += '<label class="col-sm-4" style="padding-left: 10px;">Code List Name<span class="error ml-1">*</span></label>';
        html += '<div class="col-sm-7">';
        html += '<input type="text" class="form-control"  placeholder="Code List Name" id="new_code_list" name="new_code_list" required value="">';
        html += '<span id="new_code_list_error" class="error"></span>';
        html += '</div>';
        html += '</div>';
        html += '<div class="form-group row">';
        html += '<label class="col-sm-4 col-form-label">Reason for change<span class="error ml-1">*</span></label>';
        html += '<div class="col-sm-7">';
        html += '<textarea id="add_reason_for_change" class="form-control" placeholder="Reason for change." name="reason_for_change"></textarea>';   
        html += '<span id="add_reason_for_change_error" class="error"></span>';
        html += '</div>';
        html += '</div>';
        $('#commentForm_newDodeList').html(html);
    });
    setTimeout(function(){$('#fashMessage').remove();}, 3000);
    
    $(".addOptionsCode").click(function()
    {
        var code_list_id = this.id;
        $('#new_codelist_options').html("");
        var str = random_string(6);
        var selid = 'questionID'+str;
        var html = '<div class="modal-body">';
        html += '<div class="error" id="addCodeListOptionError"></div>';
        html += '<form data-toggle="validator" id="commentForm_newOptions" method="get" action="" name="myForm" class="form-horizontal">';
        html += '<table class="table table-striped table-sm table-borderless">';
        html += '<thead>';
            html += '<tr>';
                html += '<th>Option<span class="error ml-1">*</span></th>';
                html += '<th>KPI</th>';
                html += '<th></th>';
            html += '</tr>';
        html += '</thead>';
        html += '<tbody id="tabledivbody_code_list_add">';
            html += '<tr class="sectionsid">';
                html += '<td>';
//                    html += '<div class="row">';
                    html += '<input type="text" class="form-control quesiionIDs"  placeholder="Option" id="'+selid+'" name="questionID_'+selid+'" required>';
                    html += '<span id="'+selid+'_error" class="error">&nbsp;</span>';
//                    html += '</div>';
                html += '</td>';
                html += '<td>';
//                    html += '<div class="row">';
                    html += '<select type="text" class="form-control quesiionKpi" placeholder="Question Kpi" id="kpiType_'+str+'" name="kpiType_'+str+'" required>';
                    html += '<option value="0">None</option>';
                    html += '<option value="3">Critical</option>';
                    html += '<option value="2">Major</option>';
                    html += '<option value="1">Important</option>';
                    html += '</select>';
                    html += '<span id="kpiType_'+str+'_error" class="error">&nbsp;</span>';
//                    html += '</div>';
                html += '</td>';
                html += '<td align="center">';
                html += '</td>';
            html += '</tr>';
        html += '</tbody>';
        html += '</table>';
        html += '<button style="float:right" type="button"id="add_img" class="btn btn-primary mr-3" onClick="insSpec_new_code_list(); return false;" align="center">New Option</button>';
        html += '<div style="clear:both;">&nbsp;</div>';
        html += '<div class="form-group row">';
        html += '<label class="col-sm-4 col-form-label">Reason for change<span class="error ml-1">*</span></label>';
        html += '<div class="col-sm-7">';
        html += '<textarea id="add_codelist_option_reason_for_change" class="form-control" placeholder="Reason for change." name="reason_for_change"></textarea>';   
        html += '<span id="add_codelist_option_reason_for_change_error" class="error"></span>';
        html += '</div>';
        html += '</div>';
        html += '</form>';
        html += '</div>';
        html += '<div id="status_add_cl_option" class="ml-3"></div>';
        html += '<div class="modal-footer">';
        html += '<button onclick="addCodeListOptions(\''+code_list_id+'\')" type="button" class="btn btn-primary">Save</button>';
        html += '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
        html += '</div>';
        $('#new_codelist_options').html(html);
    });
});

    function insSpec_new_code_list()
    {
        var str = random_string(6);
        var selid = 'questionID'+str;
        var html = "";
        html += '<tr class="sectionsid">';
            html += '<td>';
//                html += '<div class="row">';
                html += '<input type="text" class="form-control quesiionIDs"  placeholder="Option" id="'+selid+'" name="questionID_'+selid+'" required>';
                html += '<span id="'+selid+'_error" class="error">&nbsp;</span>';
//                html += '</div>';
            html += '</td>';
        html += '<td>';
//            html += '<div class="row">';
            html += '<select type="text" class="form-control quesiionKpi" placeholder="Question Kpi" id="kpiType_'+str+'" name="kpiType_'+str+'" required>';
            html += '<option value="0">None</option>';
            html += '<option value="3">Critical</option>';
            html += '<option value="2">Major</option>';
            html += '<option value="1">Important</option>';
            html += '</select>';
            html += '<span id="kpiType_'+str+'_error" class="error">&nbsp;</span>';
//            html += '</div>';
        html += '</td>';
        html += '<td>';
            html += '<div class="align-center mb-3">';
                html += '<i class="lnr icon-trash2 error" style="cursor: pointer;" onClick="delSuspectProduct(this)"></i>';
            html += '</div>';
        html += '</td>';
        html += '</tr>';
        $("#tabledivbody_code_list_add").append(html);
    }

    function addCodeListNew(trackerId)
    {
        var count = 0;
        $("#status_add_cl").html('');
        $("#codelistAddErrorMessage").html("");
        var newCodeList = $.trim($("#new_code_list").val());
        var comment = $.trim($("#add_reason_for_change").val());
        if(newCodeList == null || newCodeList == ''){
            $("#new_code_list_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Code List Name'));
            count++;
        } else if (newCodeList.length > 150) {
           $("#new_code_list_error").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Code List Name').replace('#char','150'));
           count++;
        } else {
            $("#new_code_list_error").html('');
        }
        
        if(comment == null || comment == ''){
            $("#add_reason_for_change_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            count++;
        } else {
            $("#add_reason_for_change_error").html('');
        }
        if (count === 0) {

            $("#status_add_cl").html('Processing...');
            var data = {
                newCodeList : newCodeList,
                trackerId : trackerId,
                reason : comment
            };
            var url = "/codelist/add_new_codelist/"+trackerId;
            $.post(url, data,function(respJson){
                var resp = JSON.parse(respJson);
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                console.log(resp);
                if(responseCode == 1){
                    window.setTimeout('window.location.replace("/codelist/codelist_management/'+trackerId+'")', 1000);
                }
                else{
                    $("#codelistAddErrorMessage").html(errMessage);
                }
            });
        }
    }
    function editCodeList()
    {
        $("#status_edit").html("");
        $("#codelistEditErrorMessage").html("");
        var editCodeListId = $.trim($("#edit_code_list_id").val());
        var editCodeList = $.trim($("#edit_code_list").val());
        var reason = $.trim($("#edit_reason_for_change").val());
        var count = 0;
        
        if (editCodeList == null || editCodeList == ''){
            $("#edit_code_list_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Code List Name'));
            count++;
        } else if (editCodeList.length > 150) {
           $("#edit_code_list_error").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Code List Name').replace('#char','150'));
           count++;
        } else {
            $("#edit_code_list_error").html('');
        }
        
        if (reason == null || reason == ''){
            $("#edit_reason_for_change_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            count++;
        } else {
            $("#edit_reason_for_change_error").html('');
        }
        
        if (count === 0) {
            $("#status_edit").html('Processing...');
            var tracker_id = tracker_id;
            var data = {
                editCodeList : editCodeList,
                trackerId : trackerId,
                editCodeListId : editCodeListId,
		reason:reason
            };
            var url = "/codelist/editcodelist/"+trackerId;
            $.post(url, data,function(respJson){
                var resp =JSON.parse(respJson);
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                if(responseCode == 1){
                    window.setTimeout('window.location.replace("/codelist/codelist_management/'+trackerId+'")', 1000);
                } else {
                    $("#codelistEditErrorMessage").html(errMessage);
                }
            });
        }
    }

    function deleteCodeListOptionsClear(coldeListId) {
        $("#addcommentfordelete").val("");
        $("#commenterrorfordelete").html("");
        $("#codelistDeleteErrorMessage").html("");
        $("#codeListIDToDelete").val(coldeListId);
    }
    
    function deleteCodelist() {
        var count = 0;
        $("#codelistDeleteErrorMessage").html("");
        var codeListID = $.trim($("#codeListIDToDelete").val());
        var reason = $.trim($('#addcommentfordelete').val());
        $('#commenterrorfordelete').html("");
        $('#fieldDeleteErrorMessage').html("");
        if (reason == ''){
            $('#commenterrorfordelete').html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            count++;
        }
        if (codeListID == 0) {
           count++;
        }
        if (count === 0) {
            $.ajax({
                url: "/codelist/delete_codelist/"+trackerId,
                type:'post',
                data:{ codeListID:codeListID, trackerId:trackerId, reason:reason },
                success:function(respJson) {
                    var resp = JSON.parse(respJson);
                    var responseCode = resp.responseCode;
                    var errMessage = resp.errMessage;
                    if(responseCode == 1){
                        topFunction();
                        location.reload();
                    }
                    else{
                        $("#codelistDeleteErrorMessage").html(errMessage);
                    }
                }
            });
        }
    }
    
    function addCodeListOptions(code_list_id){
        
        var count = 0;
        $("#addCodeListOptionError").html("");
        $("#status_add_cl_option").html('');
        var comment = $.trim($("#add_codelist_option_reason_for_change").val());
        var elem = document.getElementsByClassName("quesiionIDs");
        var names = [];
        for (var i = 0; i < elem.length; ++i) {
            if (typeof elem[i].value !== "undefined") {
                var option= elem[i].value.trim(); 
                if (option == '') {
                    $("#"+elem[i].id+"_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Option'));
                    count++;
                } else {
                    $("#"+elem[i].id+"_error").html("&nbsp;");
                    if (names.indexOf(option) == -1) {
                        names.push(option);
                    } else {
                        $("#addCodeListOptionError").removeClass("success").addClass("error").html('Duplicate Options are not allowed');
                        count++;
                    } 
                }                
            }
        }
        var elem = document.getElementsByClassName("quesiionKpi");
        var kpivalues = [];
        for (var i = 0; i < elem.length; ++i) {
            if (typeof elem[i].value !== "undefined") {
                kpivalues.push(elem[i].value);
            }
        }
        
        if(comment == null || comment == ''){
            $("#add_codelist_option_reason_for_change_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            count++;
        } else {
            $("#add_codelist_option_reason_for_change_error").html('');
        }

        if(count == 0) {
            $("#status_add_cl_option").html('Processing...');
            var data = {
                kpi : kpivalues,
                names : names,
                tracker_id : trackerId,
                code_list_id : code_list_id,
                reason : comment
            };
            var url = "/codelist/add_codelist_options/"+trackerId;
            $.post(url, data,function(respJson){
                var resp =JSON.parse(respJson);
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                $("#status_add_cl_option").html('');
                if(responseCode == 1){
                    $("#addCodeListOptionError").removeClass("error").addClass("success").html(errMessage);
                    setTimeout(function(){$('#addOptionsCode').modal('toggle');}, 3000); 
                }
                else{
                    $("#addCodeListOptionError").removeClass("success").addClass("error").html(errMessage);
                }
            });
        }
    }

    function edit_codeList_name(code_list_id, code_list_name){
        $("#edit_code_list").val(code_list_name);
        $("#edit_code_list_id").val(code_list_id);
        $("#edit_code_list_error").html("");
        $("#edit_reason_for_change_error").html("");
        $("#codelistEditErrorMessage").html("");
    }
    function viewOptionsModel(code_list_id, tracker_id){
        $("#optionsView").html("");
        var data = {
            code_list_id : code_list_id
        };
        var url = "/codelist/getoptionsbycodelist/"+tracker_id;
        $.post(url, data,function(respJson){
            var resp =JSON.parse(respJson);
            var responseCode = resp.responseCode;
            var results = resp.results;
            var html = '';
            html += '<table class="table table-striped table-sm table-borderless"><thead><tr><th>Option</th><th>KPI</th></tr></thead>';
            html += '<tbody id="tabledivbody_code_list_option_view">';
            var resCount = results.length;
            if(resCount > 0){
                for(var i = 0; i<resCount; i++){
                    var option_id = results[i].option_id;
                    html += '<tr class="sectionsid">';
                    html += '<td>';
                    html += '<div class="form-group row">';
                    html += results[i].label;
                    html += '</div></td><td>';
                    html += '<div class="form-group row">';
                    var kpi = results[i].kpi;
                    switch (kpi) {
                        case '3':
                            html += 'Critical';
                            break;
                        case '2':
                            html += 'Major';
                            break;
                        case '1':
                            html += 'Important';
                            break;
                        default :
                            html += 'None';
                            break;
                    }
                    html += '</div>';
                    html += '</td>';
//                    html += '<td>';
//                    if(tracker_id != 0){
//                        html += '<div class="align-center mb-3">';
//                        html += '<i class="lnr icon-trash2 error" style="cursor: pointer;" onclick="deletecodeListOption(\''+option_id+'\',\''+code_list_id+'\',\'view\')"></i>';
//                        html += '</div>';
//                    }
//                    html += '</td>';
                    html += '</tr>';
                }
            }else{
                html += "<tr><td>No records found</td><td></td></tr>";
            }
            html += '</tbody>';
            html += '</table>';
            $("#optionsView").html(html);
        });
    }

    function editOptionsModel(code_list_id){
        $("#edit_codelist_options").html("");
        var data = {
            code_list_id : code_list_id
        };
        var url = "/codelist/getoptionsbycodelist";
        $.post(url, data,function(respJson){
            var resp =JSON.parse(respJson);
            var responseCode = resp.responseCode;
            var results = resp.results;
            var html = '';
            html += '<div class="modal-body">';
            html += '<div class="error" id="editCodeListOptionError"></div>';
            html +='<form data-toggle="validator" id="commentForm_editOptions" method="get" action="" name="myForm" class="form-horizontal">';
            html += '<table class="table table-striped table-sm table-borderless" style="margin-bottom: 10px;">';
            html += '<thead>';
            html += '<tr>';
            html += '<th>Option<span class="error ml-1">*</span></th>';
            html += '<th>KPI</th>';
            html += '<th></th>';
            html += '</tr>';
            html += '</thead>';
            html += '<tbody id="tabledivbody_code_list_option_view">';

            var resCount = results.length;
            if(resCount > 0){

                for(var i = 0; i<resCount; i++){
                    var option_id = results[i].option_id;
                    var label = results[i].label;
                    var kpi = results[i].kpi;
                    html += '<tr class="sectionsid" id="sectionsid_'+option_id+'">';
                    html += '<td>';
//                        html += '<div class="form-group">';
                        html += '<input type="text" class="form-control quesiionIDs_option_edit"  placeholder="Option" id="op_edit' + option_id + '" name="op_edit' + option_id + '" required value="' + (label) + '">';
                        html += '<input type="text" class="form-control option_ids_edit" style="display:none" id="option_ids' + option_id + '" value="' + option_id + '">';
                        html += '<span id="op_edit'+option_id+'_error" class="error">&nbsp;</span>';
//                        html += '</div>';
                    html += '</td>';
                    html += '<td>';
//                        html += '<div class="form-group">';
                        html += '<select type="text" class="form-control quesiionKpi_option_edit" placeholder="Question Kpi" id="edit_kpiType_'+option_id+'" name="edit_kpiType_'+option_id+'" required>';
                        html += '<option value="0"';
                            if(kpi == "0"){html+='selected'}
                            html += '>None</option>';
                            html += '<option value="3"';
                            if(kpi == "3"){html+='selected'}
                            html += '>Critical</option>';
                            html += '<option value="2"';
                            if(kpi == "2"){html+='selected'}
                            html += '>Major</option>';
                            html += '<option value="1"';
                            if(kpi == "1"){html+='selected'}
                            html += '>Important</option>';
                        html += '</select>';
                        html += '<span id="edit_kpiType_'+option_id+'_error" class="error">&nbsp;</span>';
//                        html += '</div>';
                    html += '</td>';
                    html += '<td>';
                    html += '<div class="align-center mb-3">';
                    html += '<i class="lnr icon-trash2 error" style="cursor: pointer;" onClick="deletecodeListOption(\''+option_id+'\',\''+code_list_id+'\',\'edit\')"></i>';
                    html += '</div>';
                    html += '</td>';
                    html += '</tr>';
                }
            }else{
                html += "<tr>";
                html += '<td>';
                html += "No records found";
                html += '</td>';
                html += '<td>';
                html += '</td>';
                html += '<td>';
                html += '</td>';
                html += "</tr>";
            }
            html += '</tbody>';
            html += '</table>';
            if (resCount > 0) {
                html += '<div class="form-group row">';
                html += '<label class="col-sm-4 col-form-label">Reason for change<span class="error ml-1">*</span></label>';
                html += '<div class="col-sm-7">';
                html += '<textarea id="edit_codelist_option_reason_for_change" class="form-control" placeholder="Reason for change." name="reason_for_change"></textarea>';   
                html += '<span id="edit_codelist_option_reason_for_change_error" class="error"></span>';
                html += '</div>';
                html += '</div>'; 
            }
            html += '</form>';
            html += '</div>';
                
            html += '<span id="status_option_edit" class="ml-3"></span><br/>';
            html += '<div style="clear:both"></div>';
            html += '<div class="modal-footer">';
            if (resCount > 0) {    
                html += '<button onclick="editCodeListOptions(\'' + code_list_id + '\')" type="button" class="btn btn-primary">Save</button>';
            }
            html += '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
            html += '</div>';
            $("#edit_codelist_options").html(html);

        });
    }

    function editCodeListOptions(code_list_id){
        var count = 0;
        $("#editCodeListOptionError").html("");
        $("#status_option_edit").html('');
        var comment = $.trim($("#edit_codelist_option_reason_for_change").val());
        
        var elem = document.getElementsByClassName("quesiionIDs_option_edit");
        var names = [];
        for (var i = 0; i < elem.length; ++i) {
            if (typeof elem[i].value !== "undefined") {
                var option= elem[i].value.trim(); 
                if (option == '') {
                    $("#"+elem[i].id+"_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Option'));
                    count++;
                } else {
                    $("#"+elem[i].id+"_error").html("&nbsp;");
                    if (names.indexOf(option) == -1) {
                        names.push(option);
                    } else {
                        $("#editCodeListOptionError").removeClass("success").addClass("error").html('Duplicate Options are not allowed');
                        count++;
                    } 
                }     
                //names.push(elem[i].value);
            }
        }
        var elem = document.getElementsByClassName("option_ids_edit");
        var option_ids = [];
        for (var i = 0; i < elem.length; ++i) {
            if (typeof elem[i].value !== "undefined") {
                option_ids.push(elem[i].value);
            }
        }
        var elem = document.getElementsByClassName("quesiionKpi_option_edit");
        var kpivalues = [];
        for (var i = 0; i < elem.length; ++i) {
            if (typeof elem[i].value !== "undefined") {
                kpivalues.push(elem[i].value);
            }
        }
        if(comment == null || comment == ''){
            $("#edit_codelist_option_reason_for_change_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            count++;
        } else {
            $("#edit_codelist_option_reason_for_change_error").html('');
        }
        
        if(count == 0) {
            $("#status_option_edit").html('Processing...');
            var data = {
                kpi : kpivalues,
                names : names,
                option_ids : option_ids,
                code_list_id : code_list_id,
		tracker_id: trackerId,
                reason: comment
            };
            var url = "/codelist/editCodelistOptions/"+trackerId;
            $.post(url, data,function(respJson){
                var resp =JSON.parse(respJson);
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                if(responseCode == 1){
                    $("#editCodeListOptionError").removeClass("error").addClass("success").html(errMessage);
                    setTimeout(function(){$('#editOptionsCode').modal('toggle');}, 3000); 
                }
                else{
                    $("#editCodeListOptionError").removeClass("success").addClass("error").html(errMessage);
                }
                $("#status_option_edit").html('');
            });
        }
        return false;
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
        r = node.parentNode.parentNode.parentNode;
        r.parentNode.removeChild(r);
        sendOrderToServer();
    }

    function deletecodeListOption(option_id, codeListID, type)
    {
        $("#sectionsid_"+option_id).remove();
//        $('#deletecommentasreason').modal('show');
//        $("#codeListOptionIDToDelete").val(option_id);
//        $("#codeListIDToRemoveOptionDiv").val(codeListID);
//        $("#addCommentForOptionsDelete").val("");
//        $("#addCommentForOptionsDeleteError").html("");
//        $("#codelistOptionDeleteErrorMessage").html("");
//        $("#TypeToRemoveOptionDiv").val(type);
    }
    
    $("#reasonfordelete").click(function() {
        var optionId = $("#codeListOptionIDToDelete").val();
        var reason = $("#addCommentForOptionsDelete").val();
        var codeListID = $("#codeListIDToRemoveOptionDiv").val();
        var type = $("#TypeToRemoveOptionDiv").val();
        var count = 0;
        $("#codelistOptionDeleteErrorMessage").html("");
        if(reason == null || reason == ''){
            $("#addCommentForOptionsDeleteError").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            count++;
        } else {
            $("#addCommentForOptionsDeleteError").html('');
        }
        if (count == 0){
            $("#status_option_delete").html('Processing...');
            $.ajax({
                url: "/codelist/deletecodelistoption/"+trackerId,
                type:'post',
                data:{ optionId: optionId, tracker_id:trackerId, reason: reason },
                success:function(data) {
                    var resp =JSON.parse(data);
                    var responseCode = resp.responseCode;
                    var errMessage = resp.errMessage;
                    if(responseCode == 1)
                    {
                        $("#codelistOptionDeleteErrorMessage").removeClass("error").addClass("success").html(errMessage);
                        
                        setTimeout(function(){
                            $('#deletecommentasreason').modal('toggle');
                        }, 2000);
                        setTimeout(function(){
                            if (type == 'view') {
                                $('#viewOptionsCode').modal('toggle');
                            } else if (type == 'edit') {
                                $('#editOptionsCode').modal('toggle');
                            }
                        }, 2500);
                        if (type == 'view') {
                            $("button#viewid_"+codeListID).trigger("click");
                        } else if (type == 'edit') {
                            $("button#editid_"+codeListID).trigger("click");
                        }
                    } else {
                        $("#codelistOptionDeleteErrorMessage").removeClass("success").addClass("error").html(errMessage);
                    }
                $("#status_option_delete").html('');
                }
            });
            return false;
        }
    });
    
    function topFunction() {
        document.body.scrollTop = 0; // For Safari
        document.documentElement.scrollTop = 0; // For Chrome, Firefox, IE and Opera
    } 