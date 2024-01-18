/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var paramsobj;
var columnDefs = [
    {headerName: "Created Date Time", field: "created_date_time", tooltipField: "created_date_time", autoHeight:true, width: 150},
    {
        headerName: "File Name", 
        field: "file_name", 
        autoHeight:true, 
        width: 175, 
        cellRenderer: function(params) {
            return '<a href="javascript:void(0);" title="file_name">'+params.value+'</a>';                    
        }         
    },
    {headerName: "Created By", field: "created_by", autoHeight:true, width: 130}    
];
// Start of POC code
var filterParams = {
    filterOptions: [
     'empty',
    {
        displayKey: 'contains',
        displayName: 'Contains',
        test: function (filterValue, cellValue) {
        var filterValue = filterValue.toLowerCase();
        if (cellValue != null){
            return cellValue.indexOf(filterValue) >= 0;   
        } else {
            return null;
        }
        },
         hideFilterInput: false,
    },
    {
    displayKey: 'notcontains',
        displayName: 'Not Contains',
        test: function (filterValue, cellValue) {
            filterValue = filterValue.toLowerCase();
        if (cellValue != null){
            return cellValue.indexOf(filterValue) <= 0;
        } else {
            return null;
        }
        },
         hideFilterInput: false,
    },
      {
        displayKey: 'equals',
        displayName: 'Equals',
        test: function (filterValue, cellValue) {
           filterValue = filterValue.toLowerCase();
           return cellValue != null && cellValue == filterValue;
        },
        hideFilterInput: false,
    },
    {
        displayKey: 'notequals',
        displayName: 'Not Equals',
        test: function (filterValue, cellValue) {
          filterValue = filterValue.toLowerCase();
          return cellValue != null && cellValue != filterValue;
        },
        hideFilterInput: false,
    },
    {
        displayKey: 'startswith',
        displayName: 'Starts With',
        test: function (filterValue, cellValue) {
          filterValue = filterValue.toLowerCase();
        if (cellValue != null){
            return cellValue.indexOf(filterValue) === 0; 
        } else {
            return null;
        } 
            },
        hideFilterInput: false,
    },
    {
        displayKey: 'endswith',
        displayName: 'Ends With',
        test: function (filterValue, cellValue) {
          filterValue = filterValue.toLowerCase();
            if (cellValue != null){
                var index = cellValue.lastIndexOf(filterValue);
            return index >= 0 && index === (cellValue.length - filterValue.length);
            } else {
            return null;
            }
        },
        hideFilterInput: false,
    },
        {
            displayKey: 'blanks',
            displayName: 'Blanks',
            test: function (filterValue, cellValue) {
            return cellValue == null || cellValue == "";
            },
            hideFilterInput: true,
        },
    ],
    suppressAndOrCondition: false,
    applyButton: true, clearButton:true,
  }
  
  // End of POC code

var gridOptions = {
    defaultColDef: {
        //filterParams: { applyButton: true, clearButton:true}, 
        filterParams: filterParams,
        sortable: true, 
        filter: true, 
        resizable: true, 
        tooltipComponent: "customTooltip"
    },
    columnDefs: columnDefs,
    rowData: null,
    multiSortKey: 'ctrl',
    rowSelection: 'multiple',
    animateRows: true,
    sortingOrder: ['desc','asc',null],
    domLayout: 'autoHeight',
    pagination: true,
    paginationPageSize: 5, 
    paginationNumberFormatter: function(params) {
        return params.value.toLocaleString();
    },
    enableRangeSelection: true, 
    
};
function onPageSizeChanged(newPageSize) {
    var value = document.getElementById('page-size').value;
    gridOptions.api.setDomLayout('normal');
    document.querySelector('#myGrid').style.height = '427px';
    gridOptions.api.paginationSetPageSize(Number(value));
}
function refresh()
{
    gridOptions.api.redrawRows();
}

// setup the grid after the page has finished loading
document.addEventListener('DOMContentLoaded', function() {
    var gridDiv = document.querySelector('#myGrid');
    new agGrid.Grid(gridDiv, gridOptions);
    var data = {
            trackerId : trackerId,
            formId : formId,
            dashboardId : dashboardId,
            productId : productId,
            filter : filter
    };    
    $.ajax({
        url:'/SignalCalendar/getData/'+trackerId+'/'+formId+'/'+dashboardId+'/'+productId, // point to server-side PHP script 
        dataType: 'json',  // what to expect back from the PHP script, if anything
        cache: false,
        // contentType: false,
        // processData: false,
        data:data,
        type: 'post',
        success: function(data) {
            // $("#startDate").val(data.startDateOrig);
            // $("#endDate").val(data.endDateOrig);
            //$("#active_substance_id").html('Quantitative Analysis For ' + data.activeSubstance);
            gridOptions.api.setRowData(data.data);
        }
    });
});

function downloadCSV()
{
    $('#downloadReport').attr('action', "/signalcalendar/downloadCSV/"+trackerId+'/'+formId+'/'+productId);
    document.getElementById('downloadReport').submit();
}
function downloadEXCEL()
{
    $('#downloadReport').attr('action', "/signalcalendar/downloadEXCEL/"+trackerId+'/'+formId+'/'+productId);
    document.getElementById('downloadReport').submit();
}
function downloadPDF()
{
    $('#downloadReport').attr('action', "/signalcalendar/downloadPDF/"+trackerId+'/'+formId+'/'+productId);
    document.getElementById('downloadReport').submit();
}
function editMedicalEvaluation(params)
{
    paramsobj = params;
    $('#medical_evaluation').html("");
        var html = '<div class="form-group row">';
        html += '<label class="col-sm-4"></label>';
        html += '<div id="medicalEvaluationErrorMessage" class="error col-sm-7"></div>';
        html += '</div>';
        html += '<div class="form-group row">';
        html += '<label class="col-sm-4" style="padding-left: 10px;">Medical Evaluation Value<span class="error ml-1">*</span></label>';
        html += '<div class="col-sm-7">';
        
        html += '<select type="text" class="form-control medical_evaluation_value" placeholder="Medical Evaluation Value" id="medical_evaluation_value" name="medical_evaluation_value">';
        if (params.value == 0) {
            html += '<option value="0" selected>0</option>';
        } else {
            html += '<option value="0">0</option>';
        }
        if (params.value == 1) {
            html += '<option value="1" selected>1</option>'; 
        } else {
            html += '<option value="1">1</option>'; 
        }
        if (params.value == 2) {
            html += '<option value="2" selected>2</option>';
        } else {
            html += '<option value="2">2</option>';
        }
        if (params.value == 3) {
            html += '<option value="3" selected>3</option>';
        } else {
            html += '<option value="3">3</option>';
        }
        if (params.value == 4) {
            html += '<option value="4" selected>4</option>';
        } else {
            html += '<option value="4">4</option>';
        }
        if (params.value == 'NA') {
            html += '<option value="NA" selected>NA</option>';
        } else {
            html += '<option value="NA">NA</option>';
        }
        html += '</select>';
        
        html += '<span id="medical_evaluation_value_error" class="error"></span>';
        html += '</div>';
        html += '</div>';
        html += '<div class="form-group row">';
        html += '<label class="col-sm-4 col-form-label">Reason for change<span class="error ml-1">*</span></label>';
        html += '<div class="col-sm-7">';
        html += '<textarea id="reason_for_change" class="form-control" placeholder="Reason for change." name="reason_for_change"></textarea>';   
        html += '<span id="reason_for_change_error" class="error"></span>';
        html+= '<input type="hidden" id="paramsobj" value="'+params+'" name="paramsobj">';        
        html += '</div>';
        html += '</div>';                
        $("#medical_evaluation").append(html);
        $("#status_add_cl").html('');
        $('#edit_medical_evaluation').modal('toggle');
        $('#edit_medical_evaluation').modal('show');
}
function changeMedicalEvaluation()
{    
    var count = 0;
    $("#status_add_cl").html('');
    $("#codelistAddErrorMessage").html("");
    var newvalue = $.trim($("#medical_evaluation_value").val());
    var comment = $.trim($("#reason_for_change").val());
    if(newvalue == '0' || newvalue == 'NA' || newvalue == '1' || newvalue == '2' || newvalue == '3' || newvalue == '4'){
        $("#medical_evaluation_value_error").html('');
    } else {
        $("#medical_evaluation_value_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Medical Evaluation'));
        count++;
    }
    if(comment == null || comment == ''){
        $("#reason_for_change_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        count++;
    } else {
        $("#reason_for_change_error").html('');
    }
    if (count === 0) {
        $("#status_add_cl").html('Processing...');
        var data = {
            trackerId : paramsobj.data.trackerId,
            formId : paramsobj.data.formId,
            productId : paramsobj.data.productId,
            ptId : paramsobj.data.pt_id,
            oldValue : paramsobj.value,
            newValue  : newvalue, 
            reason : comment
        };
        
        $.ajax({
            url:'/quantitative/updateMedicalEvaluation', // point to server-side PHP script 
            dataType: 'json',  // what to expect back from the PHP script, if anything
            cache: false,
            data:data,
            type: 'post',
            success: function(data) {
                if(data.responseCode == 1){
                    $("#status_add_cl").html('Success..');
                    var rowNode = gridOptions.api.getDisplayedRowAtIndex(paramsobj.rowIndex);
                    rowNode.setDataValue('medical_evaluation', newvalue);  
                    rowNode.setDataValue('rationale', data.reason+'#'+paramsobj.data.rationale);
                    gridOptions.api.redrawRows(paramsobj.rowIndex);
                    $('#edit_medical_evaluation').modal('toggle');
                    $('#edit_medical_evaluation').modal('hide');                    
                }
                else{
                    $("#reason_for_change_error").html(data.message);
                }
            }
        });        
    }
}
function editPriority(params)
{
    paramsobj = params;
    $('#priority').html("");
        var html = '<div class="form-group row">';
        html += '<label class="col-sm-4"></label>';
        html += '<div id="priorityErrorMessage" class="error col-sm-7"></div>';
        html += '</div>';
        html += '<div class="form-group row">';
        html += '<label class="col-sm-4" style="padding-left: 10px;">Priority Value<span class="error ml-1">*</span></label>';
        html += '<div class="col-sm-7">';
        
        html += '<select type="text" class="form-control priority_value" placeholder="Priority Value" id="priority_value" name="priority_value">';
        if (params.value == 0) {
            html += '<option value="0" selected>0</option>';
        } else {
            html += '<option value="0">0</option>';
        }
        if (params.value == 1) {
            html += '<option value="1" selected>1</option>'; 
        } else {
            html += '<option value="1">1</option>'; 
        }
        if (params.value == 2) {
            html += '<option value="2" selected>2</option>';
        } else {
            html += '<option value="2">2</option>';
        }
        if (params.value == 3) {
            html += '<option value="3" selected>3</option>';
        } else {
            html += '<option value="3">3</option>';
        }
        if (params.value == 4) {
            html += '<option value="4" selected>4</option>';
        } else {
            html += '<option value="4">4</option>';
        }
        if (params.value == 5) {
            html += '<option value="5" selected>5</option>';
        } else {
            html += '<option value="5">5</option>';
        }
        if (params.value == 'NA') {
            html += '<option value="NA" selected>NA</option>';
        } else {
            html += '<option value="NA">NA</option>';
        }
        html += '</select>';
        
        html += '<span id="priority_value_error" class="error"></span>';
        html += '</div>';
        html += '</div>';
        html += '<div class="form-group row">';
        html += '<label class="col-sm-4 col-form-label">Reason for change<span class="error ml-1">*</span></label>';
        html += '<div class="col-sm-7">';
        html += '<textarea id="reason_for_change_priority" class="form-control" placeholder="Reason for change." name="reason_for_change_priority"></textarea>';   
        html += '<span id="reason_for_change_priority_error" class="error"></span>';
        html+= '<input type="hidden" id="paramsobj" value="'+params+'" name="paramsobj">';        
        html += '</div>';
        html += '</div>';                
        $("#priority").append(html);
        $("#status_priority").html('');
        $('#edit_priority').modal('toggle');
        $('#edit_priority').modal('show');
}
function changePriority()
{    
    var count = 0;
    $("#status_priority").html('');
    $("#codelistAddErrorMessage").html("");
    var newvalue = $.trim($("#priority_value").val());
    var comment = $.trim($("#reason_for_change_priority").val());
    if(newvalue == '0' || newvalue == 'NA' || newvalue == '1' || newvalue == '2' || newvalue == '3' || newvalue == '4' || newvalue == '5'){
        $("#priority_value_error").html('');
    } else {
        $("#priority_value_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Priority'));
        count++;
    }

    if(comment == null || comment == ''){
        $("#reason_for_change_priority_error").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        count++;
    } else {
        $("#reason_for_change_priority_error").html('');
    }
    if (count === 0) {
        $("#status_priority").html('Processing...');
        var data = {
            trackerId : paramsobj.data.trackerId,
            formId : paramsobj.data.formId,
            productId : paramsobj.data.productId,
            ptId : paramsobj.data.pt_id,
            oldValue : paramsobj.value,
            newValue  : newvalue, 
            reason : comment
        };
        
        $.ajax({
            url:'/quantitative/updatePriority', // point to server-side PHP script 
            dataType: 'json',  // what to expect back from the PHP script, if anything
            cache: false,
            data:data,
            type: 'post',
            success: function(data) {
                if(data.responseCode == 1){
                    $("#status_priority").html('Success..');
                    var rowNode = gridOptions.api.getDisplayedRowAtIndex(paramsobj.rowIndex);
                    rowNode.setDataValue('priority', newvalue);  
                    rowNode.setDataValue('rationale', data.reason+'#'+paramsobj.data.rationale);
                    gridOptions.api.redrawRows(paramsobj.rowIndex);                    
                    $('#edit_priority').modal('toggle');
                    $('#edit_priority').modal('hide');                    
                }
                else{
                    $("#reason_for_change_priority_error").html(data.message);
                }
            }
        });        
    }
}
function CustomTooltip () {}
CustomTooltip.prototype.init = function(params) {
    var eGui = this.eGui = document.createElement('div');
    var color = '#ececec';
    var data = params.api.getDisplayedRowAtIndex(params.rowIndex).data;


    eGui.classList.add('custom-tooltip');
    eGui.style['background-color'] = color;
    if (params.column.colId == 'rationale') {
        var temp = [];
        if(params.value != '') {
            temp = params.value.split("#");
        }
        if(temp.length > 0) {
            for (i = 0; i < temp.length; i++) {
                eGui.innerHTML += '<p><span>' + temp[i] + '</span></p>';                    
            }
        } else {
            eGui.innerHTML = '' ;
        }
    } else {
        eGui.innerHTML = '<p><span>' + params.value + '</span></p>';        
    }
};

CustomTooltip.prototype.getGui = function() {
    return this.eGui;
};

$(".daterange").daterangepicker({
    showDropdowns: true,
    autoUpdateInput: false,
    autoClose: true,
    linkedCalendars: false,
    "autoApply": true,
    locale: {
        format: 'DD-MMM-YYYY',
        separator: " To ",
        cancelLabel: 'Clear'
    },
    ranges: {
        'Today': [moment(), moment()],
        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
        'This Month': [moment().startOf('month'), moment().endOf('month')],
        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
        'This Quarter': [moment().startOf('quarter'), moment().endOf('quarter')],
        'Last Quarter': [moment().subtract(1, 'Q').startOf('quarter'), moment().subtract(1, 'Q').endOf('quarter')],
        'This Year': [moment().startOf('year'), moment().endOf('year')],
        'Last Year': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')],
    },
}).on('apply.daterangepicker', function(ev, picker) { 
    $(this).val(picker.startDate.format('DD-MMM-YYYY') + ' to ' + picker.endDate.format('DD-MMM-YYYY'));
}).on('cancel.daterangepicker', function(ev, picker) {
    $(this).val('');
});    

$('#filterButtonListPage').on('click',function(e){
    e.preventDefault();
    var fieldData = JSON.stringify($('#listFilterForm').serializeArray());
    var filter = encodeURIComponent(window.btoa(fieldData));
    window.location.href = '/quantitative/view/'+trackerId+'/'+formId+'/'+dashboardId+'/'+productId+'?filter='+filter;
});