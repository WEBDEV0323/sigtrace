$(document).ready(function() {
               
    var jsonString = JSON.stringify(codelistJsonData);
    $('#input_hidden_codelist').val(jsonString);


    var jsonString = JSON.stringify(rolesJsonData);
    $('#input_hidden_roles').val(jsonString);

    $("#tabledivbody").sortable({
        items: "tr",
        cursor: 'move',
        opacity: 0.6,
        update: function() {
            sendOrderToServer();
        }
    });
    $("#tabledivbody_workflow").sortable({
        items: "tr",
        cursor: 'move',
        opacity: 0.6,
        update: function() {
           addcomment();
        }
    });

    $(".editModelField").click(function()
    {
        $('#commentFormedit').html("");
        var workflowId = this.id;
        var str = random_string(6);
        var selid = 'questionID'+str;
        var html = '';
        var data = {
            workflowId :workflowId
        };
        var url = "/workflow/get_fields_by_workflow_id";
        $.post(url,data,function(respJson){
            var resp =JSON.parse(respJson);
            var responseCode = resp.responseCode;
            var results = resp.results;
            html +='<div class="row error ml-2" id="fieldsErrorMessages"></div>';
            html += '<div class="form-group row" style="padding: 1rem;">';
                html += '<table class="table table-striped">';
                    html += '<thead>';
                        html += '<tr>';
                        html += '<th>Order</th>';
                        html += '<th>Field Label Name</th>';
                        html += '<th>Type</th>';
                        html += '<th>KPI</th>';
                        html += '<th>Default Value</th>';
                        html += '</tr>';
                    html += '</thead>';
                    html += '<tbody id="tabledivbodyedit">';
                        if(responseCode > 0){
                        var countRes  = results.length;
                        for(var i = 0; i<countRes; i++){
                            var arr_res = results[i];
                            var field_id = arr_res.field_id;
                            var field_type = arr_res.field_type;
                            var label = arr_res.label;
                            var field_name=arr_res.field_name;
                            var kpi = arr_res.kpi;
                            var defaultvalue = arr_res.default_value;
                            defaultvalue = (defaultvalue == null) ? '' : defaultvalue;
                            var sort_order = arr_res.sort_order;
                            selid += "_"+field_id;
                            str += "_"+field_id;
                            html += '<tr class="sectionsid">';
                            html += '<td colspan="5">';
                            html += '<div class="row">';
                                html += '<div class="col-1">';
                                    html +='<span class="sort_order_td_edit">#'+sort_order+'</span>';
                                    html += '<input class="sort_rder_edit" value="'+sort_order+'" type="hidden"/><input class="id_values_edit" value="'+field_id+'" type="hidden"/>';
                                html += '</div>';
                                html += '<div class="col-3">';
                                    html += '<input type="text" class="form-control quesiionIDs_edit"  placeholder="Add Field"  fieldname="'+field_name+'" id="'+selid+'" name="questionID_'+selid+'" required value="'+label+'">'
                                    html += '<input type="hidden" class="getfieldname"    value="'+field_name+'" id="getfieldname_'+selid+'">'
                                    html += '<input type="text" class="form-control expected_edit" style="display:none" id="questionType_'+selid+'_expected">'
                                    html += '<span id="error_'+selid+'" class="error"></span>';
                                html += '</div>';
                                html += '<div id="id_'+selid+'">';
                                html += '</div>';
                                html += '<div class="col-3">';
                                    html += '<select onchange="checkMultiple(\'id_'+selid+'\', this.id, 0, \'questionType_'+selid+'\', \'defaultValue_'+str+'\')" type="text" class="form-control quesiionSelect_edit" placeholder="Question type" id="questionType_'+str+'" name="questionStep_'+str+'" required disabled>';
                                        html += '<option value="Integer"';
                                        if(field_type == "Integer"){html+='selected'}
                                        html+='>Integer</option>';
                                        html += '<option value="Text"';
                                        if(field_type == "Text"){html+='selected'}
                                        html += '>Text</option>';
                                        html += '<option value="TextArea"';
                                        if(field_type == "TextArea"){html+='selected'}
                                        html += '>Text Area</option>';
                                        html += '<option value="Date"';
                                        if(field_type == "Date"){html+='selected'}
                                        html += '>Date</option>';
                                        html += '<option value="Date Time"';
                                        if(field_type == "Date Time"){html+='selected'}
                                        html += '>Date Time</option>';
                                        html += '<option value="Check Box"';
                                        if(field_type == "Check Box"){html+='selected'}
                                        html += '>Check Box</option>';
                                        html += '<option value="Combo Box"';
                                        if(field_type == "Combo Box"){html+='selected'}
                                        html += '>Combo Box</option>';
                                        html += '<option value="Formula"';
                                        if(field_type == "Formula"){html+='selected'}
                                        html += '>Formula</option>';
                                        html += '<option value="User"';
                                        if(field_type == "User Role"){html+='selected'}
                                        html += '>User Role</option>';
                                        html += '<option value="Heading"';
                                        if(field_type == "Heading"){html+='selected'}
                                        html += '>Heading</option>';
                                        html += '<option value="Formula Combo Box"';
                                        if(field_type == "Formula Combo Box"){html+='selected'}
                                        html += '>Formula Combo Box</option>';
                                        if(field_type == "DependentText"){html+='selected'}
                                        html += '>DependentText</option>';
                                        if(field_type == "Formula Date"){html+='selected'}
                                        html += '>Formula Date</option>';
                                        html += '<option value="File"';
                                        if(field_type == "File"){html+='selected'}
                                        html += '>File</option>';
                                        html += '<option value="ReadOnly"';
                                        if(field_type == "ReadOnly"){html+='selected'}
                                        html += '>Read Only</option>';
                                    html += '</select>';
                                html += '</div>';
                                html += '<div class="col-2">';
                                    html += '<select type="text" class="form-control quesiionKpi_edit" placeholder="Question Kpi" id="kpiType_'+str+'" name="kpiType_'+str+'" required>';
                                        html += '<option value="0"';
                                        if(kpi == "0"){html+='selected'}
                                        html += '>None</option>';
                                        html += '<option value="1"';
                                        if(kpi == "1"){html+='selected'}
                                        html += '>Critical</option>';
                                        html += '<option value="2"';
                                        if(kpi == "2"){html+='selected'}
                                        html += '>Major</option>';
                                        html += '<option value="3"';
                                        if(kpi == "3"){html+='selected'}
                                        html += '>Important</option>';
                                    html += '</select>';
                                html += '</div>';
                                html += '<div class="col-2">';
                                if(field_type == 'Integer' || field_type == 'Text' || field_type == 'TextArea'  || field_type == 'ReadOnly') {
                                    html += '<input type="text" style="visibility: visible;" class="form-control defaultValue_edit"  id="defaultValue_'+str+'" name="defaultValue_'+str+'" value="'+defaultvalue+'">'
                                } else {
                                    html += '<input type="text" style="visibility: hidden;" class="form-control defaultValue_edit"  id="defaultValue_'+str+'" name="defaultValue_'+str+'" value="'+defaultvalue+'">'
                                }
                                    
                                html += '</div>';
                                
                            html += '</div>';
                            html += '</td>';
                            html += '</tr>';
                        }
                        }
                        else{
                        html +='<tr class="sectionsid">';
                            html += '<td colspan="4"><span class="sort_order_td_edit">No data found</span></td></tr>';
                        }
                    html += '</tbody>';
                    if(countRes > 0){
                        html += '<tbody style="border:0px;">';
                            html += '<tr style="border:0px;">';
                                html += '<td colspan="2">';
                                    html += '<div class="form-group row">';
                                    html += '<label class="col-sm-12 col-form-label">Reason for change<span class="error ml-1">*</span></label>';
                                    html += '</div>';
                                html += '</td>';
                                html += '<td colspan="2">';
                                    html += '<textarea id="addreason" class="form-control" placeholder="Reason for change." name="addcomment"></textarea>';   
                                    html += '<span id="addreasonerror" class="error"></span>';
                                html += '</td>';
                            html += '</tr>';
                        html += '</tbody>';
                    }
                html += '</table>';
            html += '</div>';
            html += '<span id="status"></span>';
            html += '<div style="clear:both"></div>';
            html += '<div class="modal-footer">';
                if(responseCode > 0){
                    html += '<button onclick="editQusetions(\''+workflowId+'\')" type="button" class="btn btn-primary">Save</button>';
                    html += '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
                }else{
                    html += '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
                }
            html += '</div>'
            $('#commentFormedit').html(html);

            $("#tabledivbodyedit").sortable({
                items: "tr",
                cursor: 'move',
                opacity: 0.6,
                update: function() {
                    sendOrderToServeredit();
                }
            });

        });
    });


    $(".audModelAdd").click(function()
    {
        $('#commentForm').html("");
        var workflowId =this.id;
        var str = random_string(6);
        var selid = 'questionID'+str
        var html = '';
        var data = {
            workflowId : workflowId
        };
        var url = "/workflow/getmaxfield";
        $.post(url, data,function(respJson){
            var resp = JSON.parse(respJson);
            var responseCode = resp.responseCode;
            var maxfieldSortNumber = parseInt(resp.maxfieldSortNumber);
            $('#inputMaxFieldHidden').val(maxfieldSortNumber);
            if(responseCode == 1){
                html +='<div class="row error ml-2" id="fieldsErrorMessages"></div>';
                html += '<div class="form-group row" style="padding: 1rem;">';
                    html += '<table class="table table-striped">';
                        html += '<thead>';
                            html += '<tr>';
                                html += '<th colspan="6">';
                                html += '<div class="row">';
                                html += '<div class="col-1 span1 text-center">';
                                html += 'Order';
                                html += '</div>';
                                html += '<div class="col-2 span2 text-center">';
                                html += 'Field Label Name';
                                html += '</div>';
                                html += '<div class="col-2 span2 text-center">';
                                html += 'Field Name';
                                html += '</div>';
                                html += '<div class="col-2 span2 text-center">';
                                html += 'Type';
                                html += '</div>';
                                html += '<div class="col-2 span2 text-center">';
                                html += 'KPI';
                                html += '</div>';
                                html += '<div class="col-2 span2 text-center">';
                                html += 'Default Value';
                                html += '</div>';                                
                                html += '<div class="col-1">';
                                html += '<span>&nbsp;</span>';
                                html += '</div>';
                                html += '</div>';
                                html += '</th>';
                            html += '</tr>';
                        html += '</thead>';
                        html += '<tbody id="tabledivbody">';
                            html += '<tr class="sectionsid">';
                                html += '<td colspan="14">';
                                    html += '<div class="row">';
                                    html += '<div class="col-1">';
                                        html += '<span class="sort_order_td">#'+(maxfieldSortNumber+1)+'</span>';
                                        html += '<input class="sort_rder" value="'+(maxfieldSortNumber+1)+'" type="hidden"/>';
                                    html += '</div>';
                                    
                                    html += '<div class="col-2">';
                                    html += '<input type="text" class="form-control quesiionIDs" onchange="validate_filed_name(this);" placeholder="Add Label" id="'+selid+'" name="questionID_'+selid+'" required>'
                                    html += '<input type="text" class="form-control expected1" style="display:none" id="questionType_'+selid+'_expected">';
                                    html += '<span id="error_'+selid+'" class="error"></span>';
                                    html += '</div>';
                                    
                                    html += '<div class="col-2">';
                                    html += '<input type="text" class="form-control quesiionNames" onchange="validate_filed_name(this);" placeholder="Add Name"  name="quesiionNames_'+selid+'" ><span style="color:red;" id = "message_'+selid+'" class = "error" ></span>'
                                    html += '</div>';
                                    
                                    html += '<div class="col-2">';
                                        html += '<select onchange="checkMultiple(\'id_'+selid+'\', this.id, 0, \'questionType_'+selid+'\', \'defaultValue_'+str+'\')" type="text" class="form-control quesiionSelect" placeholder="Question type" id="questionType_'+str+'" name="questionStep_'+str+'" required>';
                                            html += '<option value="Integer">Integer</option>';
                                            html += '<option value="Text">Text</option>';
                                            html += '<option value="TextArea">Text Area</option>';
                                            html += '<option value="Date">Date</option>';
                                            html += '<option value="Date Time">Date Time</option>';
                                            html += '<option value="Check Box">Check Box</option>';
                                            html += '<option value="Combo Box">Combo Box</option>';
                                            html += '<option value="Formula">Formula</option>';
                                            html += '<option value="User Role">User Role</option>';
                                            html += '<option value="Heading">Heading</option>';
                                            html += '<option value="Formula Combo Box">Formula Combo Box</option>';
                                            html += '<option value="DependentText">DependentText</option>';
                                            html += '<option value="Formula Date">Formula Date</option>';
                                            html += '<option value="File">File</option>';
                                            html += '<option value="ReadOnly">Read Only</option>';
                                        html += '</select>';
                                    html += '</div>';
                                    
                                    html += '<div class="col-2">';
                                        html += '<select type="text" class="form-control quesiionKpi" placeholder="Question Kpi" id="kpiType_'+str+'" name="kpiType_'+str+'" required>';
                                            html += '<option value="0">None</option>';
                                            html += '<option value="1">Critical</option>';
                                            html += '<option value="2">Major</option>';
                                            html += '<option value="3">Important</option>';
                                        html += '</select>';
                                    html += '</div>';
                                    
                                    html += '<div class="col-2">';
                                    html += '<input type="text" style="visibility: visible;" class="form-control defaultValue" value="" id="defaultValue_'+str+'" name="defaultValue_'+str+'">'
                                    html += '</div>';
                                    
                                    html += '<div class="col-1 align-center mb-3">';
                                    html += '<i class="lnr icon-trash2" style="color: red; cursor: pointer;" onClick="delSuspectProduct(this)"></i>';
                                    html += '</div>';
                                    html += '<div class="row">';
                                    html += '<div id="id_'+selid+'" style="display:none">';
                                    html += "<div class='col'>";
                                    html += '<label class="" style="padding-left: 10px;">Options</label>'
                                    html += '<select type="text" placeholder="Add Option" class="form-control expected" required id="id_'+selid+'_expected">';
                                    html += '<option value="0">Select value</option>';
                                    var respCheckListJson = codelistJsonData;
                                    var resp_checklist =JSON.parse(respCheckListJson);
                                    var check_count = resp_checklist.length;
                                    for(var ck = 0; ck<check_count; ck++){
                                        var ck_list_id = resp_checklist[ck].code_list_id;
                                        var ck_list_name = resp_checklist[ck].code_list_name;
                                        html += '<option value="'+ck_list_id+'">'+ck_list_name+'</option>';
                                    }
                                    html += '</select>';
                                    html += "</div>";
                                    html +='<label class="error" style="display:none;" id="id_'+selid+'_expected_option">This field is required.</label>';
                                    html += '</div>';


                                    html += '<div id="id_'+selid+'_role" style="display:none">';
                                    html += "<div class='col'>";
                                    html += '<label class="" style="padding-left: 10px;">Options</label>'
                                    html += '<select type="text" placeholder="Add Option" class="form-control roles" required id="id_'+selid+'_roles">';
                                    html += '<option value="0">Select value</option>';
                                    var respCheckListJson = rolesJsonData;
                                    var resp_checklist = JSON.parse(respCheckListJson);
                                    var check_count = resp_checklist.length;
                                    for(var ck = 0; ck<check_count; ck++){
                                        var ck_list_id = resp_checklist[ck].rid;
                                        var ck_list_name = resp_checklist[ck].role_name;
                                        html += '<option value="'+ck_list_id+'">'+ck_list_name+'</option>';
                                    }
                                    html += '</select>';
                                    html += "</div>";
                                    html +='<label class="error" style="display:none;" id="id_'+selid+'_roles_option">This field is required.</label>';
                                    html += '</div>';

                                    html += '</div>';
                                    html += '</div>';
                                html += '</td>';
                            html += '</tr>';
                        html += '</tbody>';
                        html += '<tbody style="border:0px;">';
                            html += '<tr style="border:0px;">';
                                html += '<td colspan="6" style="border:0px;">';
                                html += '<button style="float:right" type="button"id="add_img" class="btn btn-primary" onClick="insSpec(); return false;" align="center">New Field</button>';
                                html += '</td>';
                            html += '</tr>';
                            html += '<tr style="border:0px;">';
                                html += '<td colspan="2">';
                                    html += '<div class="form-group row">';
                                    html += '<label class="col-sm-12 col-form-label">Reason for change<span class="error ml-1">*</span></label>';
                                    html += '</div>';
                                html += '</td>';
                                html += '<td colspan="4">';
                                    html += '<textarea id="addreason" class="form-control" placeholder="Reason for change." name="addcomment"></textarea>';   
                                    html += '<span id="addreasonerror" class="error"></span>';
                                html += '</td>';
                            html += '</tr>';
                        html += '</tbody>';
                    html += '</table>';
                html += '</div>';
                
                html += '<div style="clear:both"></div>';
                html += '<span id="status"></span>';
                html += '<div style="clear:both"></div>';
                
                html += '<span id="commenterrorforfield" style="display:none;color: red;">Please add reason for change</span>';
                html += '<input type="text" id="workflow_id_edit" value="" style="display:none"/>';
                html += '<span id="statusEdit_wf_name" style="display:none" ></span>';
                
                html += '<div class="modal-footer">';
                    html += '<button onclick="addQusetions(\''+workflowId+'\')" type="button" id="savefields" class="btn btn-primary">Save</button>';
                    html += '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>';
                html += '</div>';
                $('#commentForm').html(html);

                $("#tabledivbody").sortable({
                    items: "tr",
                    cursor: 'move',
                    opacity: 0.6,
                    update: function() {
                        sendOrderToServer();
                    }
                });
            }
        });
    });

    setTimeout(function(){$('#fashMessage').remove();}, 3000);
});
function cancelSort() {
    window.location.replace("/workflow/workflow_management/"+trackerId+"/"+formId);
}
function deleteWorkflow(workflowID)
{
    $('#deletecommentasreason').modal('show');
    $('#addcommentfordelete').val('');
    $('#commenterrorfordelete').html('');
    $("#workflowDeleteErrorMessages").html("");
    $("#reasonfordelete").click(function(){
        $('#commenterrorfordelete').html('');
        $("#workflowDeleteErrorMessages").html("");
        if ($('#addcommentfordelete').val() == ''){
            $('#commenterrorfordelete').html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            return false;
        }
        else{
            $.ajax({
                url: "/workflow/delete/"+trackerId+"/"+formId,
                type:'post',
                dataType:'json',
                data:{ workflowID:workflowID, trackerId:trackerId, formId:formId,reason:$('#addcommentfordelete').val() },
                success:function(data) {
                    location.reload();
                }, error: function(jqXHR, exception) {
                    $("#workflowDeleteErrorMessages").html(jqXHR.responseText);
                }
            });
            return false;    
        }
    });
}

    function sendOrderToServer() {
        var elem = document.getElementsByClassName("sort_order_td");
        var max_sort_num = parseInt($('#inputMaxFieldHidden').val());
        for (var i = 0; i < elem.length; ++i) {
            elem[i].innerHTML = "#"+(max_sort_num+1+i);
        }
        var elem = document.getElementsByClassName("sort_rder");
        for (var i = 0; i < elem.length; ++i) {
            elem[i].value = max_sort_num+1+i;
        }
    }

    function addcomment(){
        $('#addcommentforsort').val('');
        $('#addcommentasreason').modal('show');
        $("#commenterrorforsort").html('');
        $("#workflowSortingErrorMessage").html('');
    }
    
    $(document.body).on('change','#addcommentforsort', function(){
        var reason = $("#addcommentforsort").val();
        $("#commenterrorforsort").html('');
        if(reason == null || reason == ''){
            $("#commenterrorforsort").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        } 
    });
    
    function sendOrderToServer_workflow() {
        
        $("#commenterrorforsort").html('');
        
        var reason = $.trim($("#addcommentforsort").val());
        var count = 0;
        if(reason == null || reason == ''){
            $("#commenterrorforsort").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            count++;
        }
        
        if (count == 0){
            //$("#workflowSortingErrorMessage").html('processing... please wait');
            var elem = document.getElementsByClassName("sort_order_workflow");
            for (var i = 0; i < elem.length; ++i) {
                elem[i].innerHTML = "#"+(1+i);
            }
            var elem = document.getElementsByClassName("sort_rder_value_wf");
            for (var i = 0; i < elem.length; ++i) {
                elem[i].value = 1+i;
            }


            var elem = document.getElementsByClassName("sort_rder_value_wf");
            var sort_rder_value_wf = [];
            for (var i = 0; i < elem.length; ++i) {
                if (typeof elem[i].value !== "undefined") {
                    sort_rder_value_wf.push(elem[i].value);
                }
            }
            var elem = document.getElementsByClassName("wf_id_for_sort");
            var wf_id_for_sort = [];
            for (var i = 0; i < elem.length; ++i) {
                if (typeof elem[i].value !== "undefined") {
                    wf_id_for_sort.push(elem[i].value);
                }
            }
            var elem = document.getElementsByClassName("workflow_names_cls");
            var wf_names = [];
            for (var i = 0; i < elem.length; ++i) {
                if (typeof elem[i].value !== "undefined") {
                    wf_names.push(elem[i].value);
                }
            }

            var data = {
                wf_id_for_sort : wf_id_for_sort,
                wf_sort_order : sort_rder_value_wf,
                wfNames : wf_names,
                trackerId: trackerId,
                formId: formId,
                reason:reason

            };
            var url = "/workflow/change_order/"+trackerId+"/"+formId;
            $.post(url, data,function(respJson){
                var resp =JSON.parse(respJson);
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                if(responseCode == 1){
                    window.setTimeout('window.location.replace("/workflow/workflow_management/'+trackerId+'/'+formId+'")', 1000);
                }
                else{
                    $("#workflowSortingErrorMessage").html(errMessage);
                }
            });
            
        }
    }

    function sendOrderToServeredit() {
        var elem = document.getElementsByClassName("sort_order_td_edit");

        for (var i = 0; i < elem.length; ++i) {
            elem[i].innerHTML = "#"+(1+i);
        }
        var elem = document.getElementsByClassName("sort_rder_edit");
        for (var i = 0; i < elem.length; ++i) {
            elem[i].value = 1+i;
        }
    }

    function insSpec()
    {
        $('#savefields').show();
        var str = random_string(6);
        var selid = 'questionID'+str
        var html = "";
        var maxfieldSortNumber = $('#inputMaxFieldHidden').val();
        maxfieldSortNumber = parseInt(maxfieldSortNumber);
        html += '<tr class="sectionsid">';
            html += '<td colspan="6">';
                html += '<div class="row">';
                html += '<div class="col-1">';
                    html += '<span class="sort_order_td">#'+(maxfieldSortNumber+1)+'</span>';
                    html += '<input class="sort_rder" value="'+(maxfieldSortNumber+1)+'" type="hidden"/>';
                html += '</div>';

                html += '<div class="col-2">';
                html += '<input type="text" class="form-control quesiionIDs" onchange="validate_filed_name(this);" placeholder="Add Label" id="'+selid+'" name="questionID_'+selid+'" required>'
                html += '<input type="text" class="form-control expected1" style="display:none" id="questionType_'+selid+'_expected">';
                html += '<span id="error_'+selid+'" class="error"></span>';
                html += '</div>';

                html += '<div class="col-2">';
                html += '<input type="text" class="form-control quesiionNames" onchange="validate_filed_name(this);" placeholder="Add Name"  name="quesiionNames_'+selid+'" ><span style="color:red;" id = "message_'+selid+'" class = "error" ></span>'
                html += '</div>';

                html += '<div class="col-2">';
                    html += '<select onchange="checkMultiple(\'id_'+selid+'\', this.id, 0, \'questionType_'+selid+'\', \'defaultValue_'+str+'\')" type="text" class="form-control quesiionSelect" placeholder="Question type" id="questionType_'+str+'" name="questionStep_'+str+'" required>';
                        html += '<option value="Integer">Integer</option>';
                        html += '<option value="Text">Text</option>';
                        html += '<option value="TextArea">Text Area</option>';
                        html += '<option value="Date">Date</option>';
                        html += '<option value="Date Time">Date Time</option>';
                        html += '<option value="Check Box">Check Box</option>';
                        html += '<option value="Combo Box">Combo Box</option>';
                        html += '<option value="Formula">Formula</option>';
                        html += '<option value="User Role">User Role</option>';
                        html += '<option value="Heading">Heading</option>';
                        html += '<option value="Formula Combo Box">Formula Combo Box</option>';
                        html += '<option value="DependentText">DependentText</option>';
                        html += '<option value="Formula Date">Formula Date</option>';
                        html += '<option value="File">File</option>';
                        html += '<option value="ReadOnly">Read Only</option>';
                    html += '</select>';
                html += '</div>';

                html += '<div class="col-2">';
                    html += '<select type="text" class="form-control quesiionKpi" placeholder="Question Kpi" id="kpiType_'+str+'" name="kpiType_'+str+'" required>';
                        html += '<option value="0">None</option>';
                        html += '<option value="1">Critical</option>';
                        html += '<option value="2">Major</option>';
                        html += '<option value="3">Important</option>';
                    html += '</select>';
                html += '</div>';

                html += '<div class="col-2">';
                html += '<input type="text" style="visibility: visible;" class="form-control defaultValue" value="" id="defaultValue_'+str+'" name="defaultValue_'+str+'">'
                html += '</div>';

                html += '<div class="col-1 align-center mb-3">';
                html += '<i class="lnr icon-trash2" style="color: red; cursor: pointer;" onClick="delSuspectProduct(this)"></i>';
                html += '</div>';
                html += '<div class="row">';
                html += '<div id="id_'+selid+'" style="display:none">';
                html += "<div class='col'>";
                html += '<label class="" style="padding-left: 10px;">Options</label>'
                html += '<select type="text" placeholder="Add Option" class="form-control expected" required id="id_'+selid+'_expected">';
                html += '<option value="0">Select value</option>';
                var respCheckListJson = codelistJsonData;
                var resp_checklist =JSON.parse(respCheckListJson);
                var check_count = resp_checklist.length;
                for(var ck = 0; ck<check_count; ck++){
                    var ck_list_id = resp_checklist[ck].code_list_id;
                    var ck_list_name = resp_checklist[ck].code_list_name;
                    html += '<option value="'+ck_list_id+'">'+ck_list_name+'</option>';
                }
                html += '</select>';
                html += "</div>";
                html +='<label class="error" style="display:none;" id="id_'+selid+'_expected_option">This field is required.</label>';
                html += '</div>';


                html += '<div id="id_'+selid+'_role" style="display:none">';
                html += "<div class='col'>";
                html += '<label class="" style="padding-left: 10px;">Options</label>'
                html += '<select type="text" placeholder="Add Option" class="form-control roles" required id="id_'+selid+'_roles">';
                html += '<option value="0">Select value</option>';
                var respCheckListJson = rolesJsonData;
                var resp_checklist =JSON.parse(respCheckListJson);
                var check_count = resp_checklist.length;
                for(var ck = 0; ck<check_count; ck++){
                    var ck_list_id = resp_checklist[ck].group_id;
                    var ck_list_name = resp_checklist[ck].group_name;
                    html += '<option value="'+ck_list_id+'">'+ck_list_name+'</option>';
                }
                html += '</select>';
                html += "</div>";
                html +='<label class="error" style="display:none;" id="id_'+selid+'_roles_option">This field is required.</label>';
                html += '</div>';

                html += '</div>';
                html += '</div>';
            html += '</td>';
        html += '</tr>';
        $("#tabledivbody").append(html);
        sendOrderToServer();
    }

    function checkMultiple(id, selID, start, questionType, defaultid){
        var selVal= $('#'+selID).val();
        var html="";
        var str = random_string(6);
        var respCheckListJson = $('#input_hidden_codelist').val();
        var resp_checklist =JSON.parse(respCheckListJson);
        $("#"+id+"_expected").val(0);
        if(selVal == 'Integer' || selVal == 'Text' || selVal == 'TextArea'  || selVal == 'ReadOnly'){
            $("#"+defaultid).css("visibility", "visible");
        } else {
            $("#"+defaultid).val("");
            $("#"+defaultid).css("visibility", "hidden");
        }
        
        if(selVal == 'Check Box' || selVal == 'Combo Box' || selVal == 'Formula Combo Box'){
            $("#"+id).show();
            $("#"+id+"_role").hide();
            $("#"+id+"_roles").val("0");
        }
        else if(selVal == 'User Role'){
            var respCheckListJson = $('#input_hidden_roles').val();
            var resp_checklist =JSON.parse(respCheckListJson);
            $("#"+id+"_role").show();
            $("#"+id).hide();
            $("#"+id+"_expected").val("0");
        }

        else{
            $("#"+id+"_expected").val("0");
            $("#"+id+"_roles").val("0");
            $("#"+id).hide();
            $("#"+id+"_role").hide();

        }
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
    // strats by traz
    function make_field_name(data){
		data = data.replace(/\W+/g, '');
		data = data.replace(/\s/g, '');
		data = data.toLowerCase();
		return data;
	}


    function validate_filed_name(type_flag) {
    var check = "questionID";
    var flag = true;

    $(".sectionsid").each(function (index, e) {
        var data = $.trim($(e).find('td input.quesiionIDs').val());
        var name = "quesiionNames_" + type_flag.id;
        if (type_flag != "") {
            if (type_flag.name.includes("quesiionNames_")) {
                var new_type_flag = (type_flag.name).replace('quesiionNames_', '');
                message = "message_" + new_type_flag;
            } else {
                message = "message_" + type_flag.id;
            }
        }
        if (data != "") {
            if (type_flag == "") {
                data = $(e).find('td input.quesiionNames').val();
            } else {
                if (type_flag.id.indexOf(check) != -1) {
                    data = make_field_name(data);
                    $(e).find('td input[name="' + name + '"]').val(data);
                } else {
                    data = make_field_name($(e).find('td input.quesiionNames').val());

                    $(e).find('td input[name="' + name + '"]').val(data);
                }
            }


            if (data.length > 30) {
                $(e).find('td span#' + message + '').html('Field name can\'t be more than 30 caharacters! ');
                flag = false;
            } else {
                $(e).find('td span.error').html('');
                return true;
            }

        }


    });
    return flag;
}
    // ends by traz
function addQusetions(workflow_id)
{
    var type_flag = "";
    if (validate_filed_name(type_flag)) {
        var expected = [];
        var roles = [];
        var elem = document.getElementsByClassName("quesiionIDs");
        var names = [];
        var que_names = document.getElementsByClassName("quesiionNames");
        var label_names = [];
        for (var i = 0; i < elem.length; ++i) {
            if (typeof elem[i].value !== "undefined" && elem[i].value.match(/^[a-zA-Z][a-zA-Z0-9 \-()]+$/)) {
                names.push(elem[i].value.trim());
                $('#error_'+elem[i].id).html('');
            } else {
               $('#error_'+elem[i].id).html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Field Label Name'));
                return false;
            }
        }
        
        for (var i = 0; i < que_names.length; ++i) {
            if (typeof que_names[i].value !== "undefined") {
                label_names.push(que_names[i].value.trim());
            }
        }

        var elems = document.getElementsByClassName("quesiionSelect");
        var commentField = $.trim($("#addreason").val());
        var types = [];
        for (var j = 0; j < elems.length; ++j) {
            var selectionid = elems[j].id;
            var string = selectionid.split("_");
            if (typeof elems[j].value !== "undefined") {
                if (elems[j].value == 'Check Box' || elems[j].value == 'Combo Box' || elems[j].value == 'Formula Combo Box') {
                    types.push(elems[j].value);
                    roles.push(0);
                    var elem = document.getElementsByClassName("expected");
                    for (var i = 0; i < elem.length; ++i) {
                        var id = $('#' + $(elem[i]).attr('id')).parents().eq(1).attr('id');
                        if (id.indexOf(string[1]) > 0) {

                            if (elem[i].value != 0 && ($('#' + id).css('display')) != 'none') {
                                $('#' + $(elem[i]).attr('id') + '_option').hide();
                                expected.push(elem[i].value);
                            } else {
                                if ($('#' + id).css('display') != 'none') {
                                    $('#' + $(elem[i]).attr('id') + '_option').show();
                                    return false;
                                }
                            }
                        }

                    }
                } else if (elems[j].value == 'User Role') {
                    types.push(elems[j].value);
                    expected.push(0);
                    var elem = document.getElementsByClassName("roles");
                    for (var i = 0; i < elem.length; ++i) {
                        var id = $('#' + $(elem[i]).attr('id')).parents().eq(1).attr('id');
                        if (id.indexOf(string[1]) > 0) {
                            if (typeof elem[i].value !== 0 && ($('#' + id).css('display')) != 'none') {
                                $('#' + $(elem[i]).attr('id') + '_option').hide();
                                roles.push(elem[i].value);
                            } else {
                                if ($('#' + id).css('display') != 'none') {
                                    $('#' + $(elem[i]).attr('id') + '_option').show();
                                    return false;
                                }
                            }
                        }

                    }
                } else {
                    types.push(elems[j].value);
                    expected.push(0);
                    roles.push(0);
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


        var elem = document.getElementsByClassName("defaultValue");
        var defaultvalues = [];
        for (var i = 0; i < elem.length; ++i) {
            if (typeof elem[i].value !== "undefined") {
                defaultvalues.push(elem[i].value);
            }
        }
        
        var elem = document.getElementsByClassName("sort_rder");
        var field_sort_order = [];
        for (var i = 0; i < elem.length; ++i) {
            if (typeof elem[i].value !== "undefined") {
                field_sort_order.push(elem[i].value);
            }
        }
        var $valid = $("#commentForm").valid();
        var bIsReason = true;
        
        if(commentField == null || commentField == ''){
            $("#addreasonerror").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            bIsReason = false;
        } else {
            $("#addreasonerror").html('');
        }
        
        if (!$valid || !bIsReason) {
            return false;
        } else {
            $('#savefields').hide();
            $("#status").html('processing...');
            var wf_id = workflow_id;
            var data = {
                names: names,
                label_names: label_names,
                types: types,
                expected: expected,
                form_id: formId,
                kpivalues: kpivalues,
                tracker_id: trackerId,
                workflow_id: wf_id,
                field_sort_order: field_sort_order,
                subType: 'save',
                formula_id: roles,
                reason: commentField,
                defaultvalues: defaultvalues
            };
            var url = "/workflow/savefields/" + trackerId + "/" + formId;
            $.post(url, data, function (respJson) {
                var resp = JSON.parse(respJson);
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                var formula = resp.formula;
                if (responseCode == 1) {
                   window.setTimeout('window.location.replace("/workflow/workflow_management/' + trackerId + '/' + formId + '")', 1000);
                } else {
                    $("#fieldsErrorMessages").html(errMessage);
                    $('#savefields').show();
                    $("#status").html('');
                }
            });
        }
    } else {
        return false
    }
}


function editQusetions(workflow_id)
{
    var elem = document.getElementsByClassName("quesiionIDs_edit");
    var comment = $.trim($("#addreason").val());
    var names = [];
    var bName = true;
    for (var i = 0; i < elem.length; ++i) {
        if (typeof elem[i].value !== "undefined" && elem[i].value.match(/^[a-zA-Z][a-zA-Z0-9 ]+$/)) {
            $('#error_'+elem[i].id).html('');
            names.push(elem[i].value.trim());
        } else {
           $('#error_'+elem[i].id).html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Field Label Name'));
            return false;
        }
    }
    var elem = document.getElementsByClassName("quesiionSelect_edit");
    var types = [];
    for (var i = 0; i < elem.length; ++i) {
        if (typeof elem[i].value !== "undefined") {
            types.push(elem[i].value);
        }
    }
    var elem = document.getElementsByClassName("quesiionKpi_edit");
    var kpivalues = [];
    for (var i = 0; i < elem.length; ++i) {
        if (typeof elem[i].value !== "undefined") {
            kpivalues.push(elem[i].value);
        }
    }
    var elem = document.getElementsByClassName("defaultValue_edit");
    var defaultvalues = [];
    for (var i = 0; i < elem.length; ++i) {
        if (typeof elem[i].value !== "undefined") {
            defaultvalues.push(elem[i].value);
        }
    }
    var elem = document.getElementsByClassName("sort_rder_edit");
    var field_sort_order = [];
    for (var i = 0; i < elem.length; ++i) {
        if (typeof elem[i].value !== "undefined") {
            field_sort_order.push(elem[i].value);
        }
    }
    var elem = document.getElementsByClassName("id_values_edit");
    var field_id_arr = [];
    for (var i = 0; i < elem.length; ++i) {
        if (typeof elem[i].value !== "undefined") {
            field_id_arr.push(elem[i].value);
        }
    }
    var bIsReason = true;
    if(comment == null || comment == ''){
        $("#addreasonerror").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        bIsReason = false;
    } else {
        $("#addreasonerror").html('');
    }
    
    var $valid = $("#commentFormedit").valid();
    if (!$valid || !bIsReason) {
        return false;
    } else {
        $("#status").html('processing...');
        var wf_id = workflow_id;

        var data = {
            names: names,
            types: types,
            form_id: formId,
            kpivalues: kpivalues,
            tracker_id: trackerId,
            workflow_id: wf_id,
            field_sort_order: field_sort_order,
            field_id_arr: field_id_arr,
            subType: 'save',
            reason: comment,
            defaultvalues: defaultvalues
        };
        var url = "/workflow/savefields/" + trackerId + "/" + formId;
        $.post(url, data, function (respJson) {
            var resp = JSON.parse(respJson);
            var responseCode = resp.responseCode;
            var errMessage = resp.errMessage;
            if (responseCode == 1) {
                window.setTimeout('window.location.replace("/workflow/workflow_management/' + trackerId + '/' + formId + '")', 1000);
            } else {
                $("#fieldsErrorMessages").html(errMessage);
            }
        });
    }
}

    function edit_wf_name(workflow_id,workflow_name){
        $("#statusEdit_wf_name").html('');
        $("#errorForReason").html('');
        $("#errorForWorkflowName").html('');
        $("#workflow_name_edit").val(workflow_name);
        $("#workflow_id_edit").val(workflow_id);
        $("#addcommentforworkflow").val('');
    }

    $(document.body).on('change','#workflow_name_edit', function(){
        var workflowName = $("#workflow_name_edit").val();
        $("#errorForWorkflowName").html("");
        if(workflowName == null || workflowName == ''){
            $("#errorForWorkflowName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Workflow Name'));
        } else if (!workflowName.match(/^[a-zA-Z][a-zA-Z0-9 ]+$/)) {
           $("#errorForWorkflowName").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Workflow Name'));
        } else if (workflowName.length > 200) {
           $("#errorForWorkflowName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Workflow Name').replace('#char','200')); 
        }
    });

    $(document.body).on('change','#addcommentforworkflow', function(){
        var reason = $("#addcommentforworkflow").val();
        $("#errorForReason").html('');
        if(reason == null || reason == ''){
            $("#errorForReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        } 
    });

    function editSaveWorkflow(){

        $("#statusEdit_wf_name").html('');
        $("#errorForReason").html('');
        $("#errorForWorkflowName").html('');
        var workflowName = $("#workflow_name_edit").val();
        var workflowId = $("#workflow_id_edit").val();
        var reason = $("#addcommentforworkflow").val();
        var count = 0;
        if(workflowName == null || workflowName == ''){
            $("#errorForWorkflowName").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Workflow Name'));
            count++;
        } else if (!workflowName.match(/^[a-zA-Z][a-zA-Z0-9 ]+$/)) {
           $("#errorForWorkflowName").html(messageJSON.MSG_FIELD_VALID.replace('#fieldName','Workflow Name'));
           count++;
        } else if (workflowName.length > 200) {
           $("#errorForWorkflowName").html(messageJSON.MSG_FIELD_LENGTH_ABOVE_CHAR.replace('#fieldName','Workflow Name').replace('#char','200'));
           count++;
        }
        
        if(reason == null || reason == ''){
            $("#errorForReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            count++;
        }
        
        if (count == 0) {
            $("#statusEdit_wf_name").html('processing...');
            var data = {
                wfNames : new Array(workflowName),
                workflowId : workflowId,
                reason : reason,
                trackerId : trackerId,
                formId : formId
            };
            var url = "/workflow/addUpdateWorkflow/"+trackerId+"/"+formId+"/"+workflowId;
            $.post(url, data,function(respJson){
                var resp =JSON.parse(respJson);
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                if(responseCode == 1){
                    //$("#statusEdit_wf_name").html('<font color="#088A08">'+errMessage+'</font>');
                    window.setTimeout('window.location.replace("/workflow/workflow_management/'+trackerId+'/'+formId+'")', 1000);
                }
                else if(responseCode == 2){
                    $("#statusEdit_wf_name").html('<font color="#FF0000">'+errMessage+'</font>');
                    //window.setTimeout('window.location.replace("<?php echo $this->url('tracker', array('action' => 'workflow', 'tracker_id' => $tracker_id, 'action_id' => $action_id)) ?>")', 1000);
                }
                else{
                    $("#statusEdit_wf_name").html('<font color="#cc0000">'+errMessage+'</font>');
                }
            });
        }
    }