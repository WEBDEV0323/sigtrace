    
    $(document).ready(function() {
         
        var jsonString = JSON.stringify(jsonFormulaData);
        $('#input_hidden_formula_list').val(jsonString);

        var jsonString = JSON.stringify(jsonFieldsData);
        $('#input_hidden_field_formula_list').val(jsonString);
        field_ids=0;
    });	
    String.prototype.capitalize = function() {
        return this.charAt(0).toUpperCase() + this.slice(1);
    };
    function select_formula_popup(field_id) {
        $("#modelContentAdd").html("");
        $("#Formula_Comment").html("");
        var resp_formulalist = JSON.parse(jsonFormulaData);
        var check_count = resp_formulalist.length;
        var html = "";
        html += "<div class='form-group'>";
        html += '<select onchange="SetValues(this.value)" placeholder="Add Option" class="form-control">';
        html += '<option value="0">Select Formula</option>';
        for(var ck = 0; ck<check_count; ck++){
            var formula_id = resp_formulalist[ck].formula_id;
            var label = resp_formulalist[ck].formula_name;
            html += '<option value="'+formula_id+'">'+label.capitalize()+'</option>';
        }
        html += '</select>';
        html += "</div>";
        $("#modelContentAdd").html(html);
        html = '<span id="formula_comment_span"></span>';
        html +='<input id="formula_field_value" value="" type="hidden"/>';
        html +='<input id="formula_field_id_val" value="'+field_id+'" type="hidden"/>';
        $("#Formula_Comment").html(html);
    }
    function SetValues(id_value) {
        $("#formula_comment_span").html('');
        $("#formula_field_value").val('');
//        if(id_value == 0){
//            $("#formula_comment_span").addClass("error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Formula'));
//        }
        //var respCheckListJson = $('#input_hidden_formula_list').val();
        var resp_formulalist =JSON.parse(jsonFormulaData);
        var check_count = resp_formulalist.length;
        for(var ck = 0; ck<check_count; ck++){
            var formula_id = resp_formulalist[ck].formula_id;
            if(id_value == formula_id){
                var discription = resp_formulalist[ck].discription;
                var formula_value = resp_formulalist[ck].formula_value;
                $("#formula_comment_span").removeClass("error").html('<xmp style="font-size:12px;">Description:\r\n\r\n'+discription+"</xmp>");
                $("#formula_field_value").val(formula_value);
                break;
            }
        }
        
    }
    function setFormula(){
        var formula_field_id_val = $("#formula_field_id_val").val();
        var formula_val = $("#formula_field_value").val();
        if (formula_val == 0) {
           $("#formula_comment_span").addClass("error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Formula')); 
           return false;
        }
        insertAtCursor(formula_val, 'formula_'+formula_field_id_val);
        $("#close_popup").click();
    }

    function select_field_popup(field_id) {
        var html = "";
        html = '<span id="field_comment_span"></span>';
        html +='<input id="formula_field_select_id_val_hidden" value="'+field_id+'" type="hidden"/>';
        $("#Field_Comment").html(html);
        $("#field_select_formula").val(0);
        $(".filter-option-inner-inner").text("Select Field");
    }
    function checkFieldError(value) {
//        if (value == 0) {
//           $("#field_comment_span").addClass("error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Field')); 
//           return false;
//        }
        $("#field_comment_span").removeClass("error").html("");
    }
    function setField(){
        var formula_field_id_val = $("#formula_field_select_id_val_hidden").val();
        var formula_val = $("#field_select_formula").val();
        if (formula_val == 0) {
           $("#field_comment_span").addClass("error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Field')); 
           return false;
        }
        $("#field_comment_span").removeClass("error").html("");
        insertAtCursor(formula_val, 'formula_'+formula_field_id_val);
        $("#close_popup_field").click();
    }
    
    function insertAtCursor(text, id_formula) {   
        var field = document.getElementById(id_formula);

        if (document.selection) {
            var range = document.selection.createRange();

            if (!range || range.parentElement() != field) {
                field.focus();
                range = field.createTextRange();
                range.collapse(false);
            }
            range.text = text;
            range.collapse(false);
            range.select();
        } else {
            field.focus();
            var val = field.value;
            var selStart = field.selectionStart;
            var caretPos = selStart + text.length;
            field.value = val.slice(0, selStart) + text + val.slice(field.selectionEnd);
            field.setSelectionRange(caretPos, caretPos);
        }
    }

    function SaveFormula(){ 
        var field_id = $("#FieldIdForSave").val();
        var reason = $.trim($('#reason_for_change').val());
        var count = 0;
        $("#ffSaveErrorMessage").html("");
        if(reason == null || reason == ''){
        $("#reason_for_change_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            count++;
        } else {
            $("#reason_for_change_error").html('');
        }
        
        if (count === 0) {
            $("#saveFormulaProcessing").html("Processing ...");
            var formula = $.trim($('textarea#formula_'+field_id).val());
            var data = {
                formula : formula,
                fieldId : field_id,
                trackerId:trackerId,
                formId: formId,       
                reason: reason
            };
            var url = "/field/saveFormula/"+trackerId+"/"+formId;
            $.post(url, data,function(respJson){
                var resp =JSON.parse(respJson);
                var responseCode = resp.responseCode;
                var errMessage = resp.errMessage;
                if(responseCode == 1){
                    $("#ffSaveErrorMessage").removeClass("error").addClass("success").html(errMessage);
                    setTimeout(function(){$("#addcommentasreason").modal('toggle');}, 2000);
                    
                }
                else{
                    $("#ffSaveErrorMessage").removeClass("success").addClass("error").html(errMessage);
                }
                $("#saveFormulaProcessing").html("");
            });
        }   
    }
    
    function addcomment(field_id){
        $('#addcommentasreason').modal('show');
        $('#reason_for_change').val("");
        $('#reason_for_change_error').html("");
        $("#ffSaveErrorMessage").html("");
        $("#saveFormulaProcessing").html("");
        $("#FieldIdForSave").val(field_id);
    }