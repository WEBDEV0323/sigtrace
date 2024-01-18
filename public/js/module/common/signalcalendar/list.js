/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var paramsobj;
var columnDefs = [
    {headerName: "SOC Name", field: "soc_name", tooltipField: "soc_name", autoHeight:true, width: 150},
    {headerName: "Preferred Term", field: "preferred_term", tooltipField: "preferred_term", autoHeight:true, width: 165},
    {headerName: "Rank", field: "rank", tooltipField: "rank", autoHeight:true, width: 165}, 
    {headerName: "Medical Concept", field: "mc_name", tooltipField: "mc_name", autoHeight:true, width: 165},
    {
        headerName: "Medical Evaluation", 
        field: "medical_evaluation", 
        autoHeight:true, 
        width: 175, 
        cellRenderer: function(params) {
            return '<a href="javascript:void(0);" title="Medical Evaluation">'+params.value+'</a>';                    
        }         
    },    
    {
        headerName: "Priority", 
        field: "priority", 
        autoHeight:true, 
        width: 110, 
        cellRenderer: function(params) { 
            return '<a href="javascript:void(0);" title="Priority">'+params.value+'</a>';                    
        }         
    },   
    {   headerName: "Rationale", field: "rationale", tooltipField: "rationale", autoHeight:true, width: 150 
    },
    {
        headerName: "Selected Frequency", 
        children: [
            {
                headerName: "Serious", 
                field: "current_serious_frequency", 
                width: 115, 
                autoHeight:true, 
                cellRenderer: function(params) { 
                    if (params.value > 0) {
                        return '<a href="javascript:void(0);" onclick="window.location.href=\'/dashboard/list/'+params.data.trackerId+'/'+params.data.formId+'/'+params.data.dashboardId+'/'+params.data.productId+'?filter='+params.data.base64_cur_sf+'\'" title="Current Frequency - Serious">'+params.value+'</a>';
                    } else {
                        return params.value;
                    }                    
                }
            },
            {
                headerName: "Not Serious", 
                field: "current_nonserious_frequency", 
                width: 150, 
                autoHeight:true, 
                cellRenderer: function(params) {
                    if (params.value > 0) {
                        return '<a href="javascript:void(0);" onclick="window.location.href=\'/dashboard/list/'+params.data.trackerId+'/'+params.data.formId+'/'+params.data.dashboardId+'/'+params.data.productId+'?filter='+params.data.base64_cur_not_sf+'\'" title="Current Frequency - Not Serious">'+params.value+'</a>';
                    } else {
                        return params.value;
                    }                    
                }                
            },
            {
                headerName: "Total", 
                field: "current_total", 
                width: 100, 
                autoHeight:true, 
                cellRenderer: function(params) {
                    if (params.value > 0) {
                        return '<a href="javascript:void(0);" onclick="window.location.href=\'/dashboard/list/'+params.data.trackerId+'/'+params.data.formId+'/'+params.data.dashboardId+'/'+params.data.productId+'?filter='+params.data.base64_cur_total+'\'" title="Current - Total">'+params.value+'</a>';
                    } else {
                        return params.value;
                    }                    
                }                                
            }
    ]},
    {
        headerName: "Cumulative Frequency",
        children: [
            {
                headerName: "Serious", 
                field: "cumulative_serious_frequency", 
                width: 115, 
                autoHeight:true, 
                cellRenderer: function(params) {
                    if (params.value > 0) {
                        return '<a href="javascript:void(0);" onclick="window.location.href=\'/dashboard/list/'+params.data.trackerId+'/'+params.data.formId+'/'+params.data.dashboardId+'/'+params.data.productId+'?filter='+params.data.base64_cum_sf+'\'" title="Cumulative Frequency - Serious">'+params.value+'</a>';
                    } else {
                        return params.value;
                    }                    
                }                                
            },
            {
                headerName: "Not Serious", 
                field: "cumulative_non_serious_frequency", 
                width: 150, 
                autoHeight:true, 
                cellRenderer: function(params) {
                    if (params.value > 0) {
                        return '<a href="javascript:void(0);" onclick="window.location.href=\'/dashboard/list/'+params.data.trackerId+'/'+params.data.formId+'/'+params.data.dashboardId+'/'+params.data.productId+'?filter='+params.data.base64_cum_not_sf+'\'" title="Cumulative Frequency - Not Serious">'+params.value+'</a>';
                    } else {
                        return params.value;
                    }                    
                }                                                
            },
            {
                headerName: "Total", 
                field: "cumulative_total", 
                width: 100, 
                autoHeight:true, 
                cellRenderer: function(params) { 
                    if (params.value > 0) {
                        return '<a href="javascript:void(0);" onclick="window.location.href=\'/dashboard/list/'+params.data.trackerId+'/'+params.data.formId+'/'+params.data.dashboardId+'/'+params.data.productId+'?filter='+params.data.base64_cum_total+'\'" title="Cumulative - Total">'+params.value+'</a>';
                    } else {
                        return params.value;
                    }                    
                }                                                
            } 
        ]},
    {headerName: "ROR - (All)", field: "ror_all", autoHeight:true, width: 130}    
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
    onCellClicked: changeMedicalValue, 
    components: {
        customTooltip: CustomTooltip,
    }
};
function changeMedicalValue(params){
    if (params.colDef.field == 'medical_evaluation') {
        editMedicalEvaluation(params);
    } else if (params.colDef.field == 'priority') {
        editPriority(params);
    }
}
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
        url:'/signalcalendar/getData/'+trackerId+'/'+formId, // point to server-side PHP script 
        dataType: 'json',  // what to expect back from the PHP script, if anything
        cache: false,
        // contentType: false,
        // processData: false,
        data:data,
        type: 'post',
        success: function(data) {
            $("#startDate").val(data.startDateOrig);
            $("#endDate").val(data.endDateOrig);
            $("#active_substance_id").html('Quantitative Analysis For ' + data.activeSubstance);
            gridOptions.api.setRowData(data.data);
        }
    });
});

function downloadCSV()
{
    $('#downloadReport').attr('action', "/quantitative/downloadCSV/"+trackerId+'/'+formId+'/'+productId);
    document.getElementById('downloadReport').submit();
}
function downloadEXCEL()
{
    $('#downloadReport').attr('action', "/quantitative/downloadEXCEL/"+trackerId+'/'+formId+'/'+productId);
    document.getElementById('downloadReport').submit();
}
function downloadPDF()
{
    $('#downloadReport').attr('action', "/quantitative/downloadPDF/"+trackerId+'/'+formId+'/'+productId);
    document.getElementById('downloadReport').submit();
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