/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var paramsobj;
var columnDefs = [
    {headerName: "Id", field: "id", tooltipField: "id", autoHeight:true, width: 150},
    {headerName: "Created Date Time", field: "created_date_time", tooltipField: "created_date_time", autoHeight:true, width: 150},
    {headerName: "File Name", field: "file_name", tooltipField: "file_name", autoHeight:true, width: 150},
    {headerName: "Created By", field: "created_by", tooltipField: "created_by", autoHeight:true, width: 150}

            
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
           // $("#startDate").val(data.startDateOrig);
            //$("#endDate").val(data.endDateOrig);
           // $("#active_substance_id").html('Quantitative Analysis For ' + data.activeSubstance);
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