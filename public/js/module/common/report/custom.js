/*jshint multistr: true */
/*jshint -W033 */
/*jslint evil: true */

$(document).ready(function() {
    $('select[name^="select_field_names"]').lwMultiSelect();
    $('.where-field').select2({ multiple: true,placeholder: "Select Multiple Filter",allowClear: true});   
});


$(document).on('change', '.where-field', function() {
   
    var workflow = $(this).attr("id");
   
    var custom_report_filter = ""; 
    var other_details = "";
    var condFieldNames = $("#"+workflow).val();
   
    var a1 = [];
    var a2 = []; 
    var a3 = [];
    var dateTypeVal = '';
    if(condFieldNames != null){ 
        for(var i = 0; condFieldNames.length > i; i++){
            if($("#custom-field_"+condFieldNames[i]+workflow).val() != undefined){
                a1.push($("#custom-field_"+condFieldNames[i]+workflow).val());
            }
            if($("#custom-condition_"+condFieldNames[i]+workflow).val() != undefined){
                a2.push($("#custom-condition_"+condFieldNames[i]+workflow).val());
            }
            if($("#custom-value_"+condFieldNames[i]+workflow).val() != undefined){
                a3.push($("#custom-value_"+condFieldNames[i]+workflow).val());
            }
        }
    } 
 
    if($('.where-field option:selected').val() != undefined) {
        
        $("#"+workflow+" option:selected").each(function () {
           
            var value = $(this).text();                        
            var index = $(this).val();            
            var filed_id = (index+workflow); 
            var k = $.inArray(value,a1);
            custom_report_filter += '<div class="form-row" id="custom_report_filter">\n\
                                <div class="form-group col-md-4" >\n\
                                    <input type="text" class="form-control" name="custom_report_filter['+workflow+']['+index+'][]" id="custom-field_'+filed_id+'" readonly="readonly" value="'+value+'" />\n\
                                </div>\n\
                                <div class="form-group col-md-4" >\n\
                                    <select name="custom_report_filter['+workflow+']['+index+'][]" class="form-control filter-type" id="custom-condition_'+filed_id+'" required="required">\n\
                                        <option value="">Select Condition</option>\n\
                                        <option value="=" ';
                                        if(a2[k] == "="){
                                           custom_report_filter += " selected " 
                                        }
                                        custom_report_filter +=
                                                '>=</option>\n\
                                        <option value="!="';
                                        if(a2[k] == "!="){
                                           custom_report_filter += " selected " 
                                        }
                                        custom_report_filter +=
                                                '>!=</option>\n\
                                        <option value="LIKE %...%" ';
                                        if(a2[k] == "LIKE %...%"){
                                           custom_report_filter += " selected " 
                                        }
                                        custom_report_filter +=
                                                '>LIKE %...%</option>\n\
                                        <option value="LIKE" ';
                                        if(a2[k] == "LIKE"){
                                           custom_report_filter += " selected " 
                                        }
                                        custom_report_filter +=
                                                '>LIKE</option>\n\
                                        <option value="NOT LIKE" ';
                                        if(a2[k] == "NOT LIKE"){
                                           custom_report_filter += " selected " 
                                        }
                                        custom_report_filter +=
                                                '>NOT LIKE</option>\n\
                                        <option value="IN" ';
                                        if(a2[k] == "IN"){
                                           custom_report_filter += " selected " 
                                        }
                                        custom_report_filter +=
                                                '>IN</option>\n\
                                        <option value="DATE BETWEEN" ';
                                        if(a2[k] == "DATE BETWEEN"){
                                           custom_report_filter += " selected " 
                                        }
                                        custom_report_filter +=
                                                '>DATE BETWEEN</option>\n\
                                    </select>\n\
                                </div>\n\
                                <div class="form-group col-md-4" >\n\
                                    <input type="text" class="form-control" name="custom_report_filter['+workflow+']['+index+'][]" id="custom-value_'+filed_id+'" required="required" ';
                                        if(a2[k] == "IN"){
                                           custom_report_filter += ' placeholder=\'EX:"ABC","CDF","EWR" \'' ;
                                        }
                                        custom_report_filter +=' value="';
                                        if(a3[k] != undefined){custom_report_filter += a3[k];}else{custom_report_filter +='';}
                                        custom_report_filter +='" />\n\
                                    <input type="hidden" class="form-control" name="custom_report_filter['+workflow+']['+index+'][]" value = "'+index+'" />\n\
                                </div>\n\
                        </div>';
                        if(a2[k] =='DATE BETWEEN'){ dateTypeVal += '$("#custom-value_'+filed_id+'").daterangepicker();';}
                     
        });
       
        $("."+workflow).html(custom_report_filter);
        if(dateTypeVal != ''){
            var theInstructions = dateTypeVal ;
            var F=new Function (theInstructions);
            return(F());
        }
        $(document).on('change',".filter-type", function(){

            var res = $(this).attr('id').split("_");                                 
            $("#custom-value_"+res[1]).val("");
            $("#custom-value_"+res[1]).attr('placeholder', '' );
            if($(this).val() == "DATE BETWEEN") {
               $("#custom-value_"+res[1]).daterangepicker();                    

            }
            else{
                if($(this).val() == "IN"){
                    $("#custom-value_"+res[1]).attr('placeholder', 'EX:"ABC","CDF","EWR"' );
                }
                $("#custom-value_"+res[1]).data('daterangepicker').container.css('visibility', 'hidden');
            }
        });

        other_details += '<div class="col-md-6" >\n\
                <div class="form-group row">\n\ \n\
                    <label class="col-md-6 control-label" >\n\
                        Do you want to save this report\n\
                    </label>\n\
                    <div class="col-md-4" >\n\
                        <label class="radio-inline"><input type="radio" class="is_save" name="is_save" required="required" value="Yes">Yes</label>\n\
                        <label class="radio-inline"><input type="radio" class="is_save" name="is_save" required="required" value="No">No</label>\n\
                    </div>\n\
                </div>\n\
        </div>\n\
         <div class="col-md-6" id="c_report_name">\n\
            <div class="form-group row">\n\
                <label for="custom_report_name" class="col-md-4 control-label" >\n\
                    Custom Report Name\n\
                </label>\n\
                <div class="col-md-8" >\n\
                    <input class="form-control" type="text" disabled="disabled" id="custom_report_name" name="custom_report_name">\n\</div>\n\
                </div>\n\
                </div>\n\
                <div class="col-md-12 text-center">\n\
                    <input class="btn btn-primary center-block mb-2" id="report_submit" name="report_submit" type="submit" value="Generate Report">\n\
                </div>';
        $("#other-details").html(other_details);  

        $('input:radio[name="is_save"]').filter('[value="No"]').attr('checked', true);

    } else {      
        $("."+workflow).html("");
        if($('.where-field option:selected').val() == undefined){
            $("#other-details").html("");
        }
    }
});

$(document).on('click', '.is_save', function (event) {
    if($(this).val() == "Yes") {   
         $('#report_submit').val("Save and Generate Report");   
         $("#custom_report_name").attr("required", true);
         $("#custom_report_name").removeAttr('disabled');
    } else {
         $('#report_submit').val("Generate Report");
         $("#custom_report_name").val("");
         $("#custom_report_name").attr("disabled", true);
         $("#custom_report_name").removeAttr('required');
    }
});

$("#customReportform" ).on("submit",function( e ) {
    if ($("#customReportform").validate()) {
        $("#report-section").show();
        $('#report-div').html('');
        $("#report-div").html('<img id = "ajax-loader" src="/images/ajax-loader.gif" />');
        $("#format").val('html');
        e.preventDefault();

        var href = '/report/customReport/'+trackerId+'/'+formId+'/'+reportId;
        var data = $('form').serialize()+'&submit_type=create';
        $.post(href,data,function(respJson){
            var resp = JSON.parse(respJson);
            if ($.isNumeric(resp)) { console.log("Y");
                window.location.assign("/report/"+trackerId+"/"+formId+"/"+resp);
            } else { console.log("N");
                window.location.assign("/report/"+trackerId+"/"+formId+"/"+reportId+"?url=" + encodeURIComponent(window.btoa(respJson)));
            }
            console.log("success");
        }).fail(function(){
                 $('#alert_custom').html("Something went wrong!!");
                 $('#alert_custom').addClass('alert alert-danger');
                console.log("fail");
        }).always(function(){
            console.log("always");
            window.setTimeout(function () {
                $(".alert").fadeTo(500, 0).slideUp(500, function () {
                    $('#alert_csv').removeClass('alert');
                    $(this).remove();
                    location.reload();
                });
            }, 5000);
        });
    }
});

