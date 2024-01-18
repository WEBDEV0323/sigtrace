// specify the columns agNumberColumnFilter
var columnDefs = [];
function dateComparator(date1, date2) {
    var date1Number = monthToComparableNumber(date1);
    var date2Number = monthToComparableNumber(date2);

    if (date1Number===null && date2Number===null) {
        return 0;
    }
    if (date1Number===null) {
        return -1;
    }
    if (date2Number===null) {
        return 1;
    }
    return date1Number - date2Number;
}

// eg 29/08/2004 gets converted to 20040829
function monthToComparableNumber(date) {
    if (date === undefined || date === null || date.length !== 10) {
        return null;
    }
    var yearNumber = date.substring(6,10);
    var monthNumber = date.substring(3,5);
    var dayNumber = date.substring(0,2);

    var result = (yearNumber*10000) + (monthNumber*100) + dayNumber;
    return result;
}
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
    },
    columnDefs: columnDefs,
    rowData: null,
    enableSorting: true,
    multiSortKey: 'ctrl',
    enableFilter: true,
    rowSelection: 'multiple',
    animateRows: true,
    sortingOrder: ['desc','asc',null],
    domLayout: 'autoHeight',
    pagination: true,
    paginationPageSize: 5,
    paginationNumberFormatter: function(params) {
        return params.value.toLocaleString();
    },
    enableColResize: true,
    enableRangeSelection: true, 
    enableBrowserTooltips: true,
    components: {
        'myCellRenderer': MyCellRenderer
    } 
};
function onPageSizeChanged(newPageSize) {
    var value = document.getElementById('page-size').value;
    gridOptions.api.setDomLayout('normal');
    document.querySelector('#myGrid').style.height = '370px';
    gridOptions.api.paginationSetPageSize(Number(value));
}

function getBooleanValue(cssSelector) {
    return document.querySelector(cssSelector).checked === true;
}

function onBtExport() {
    var params = {};
    gridOptions.api.exportDataAsCsv(params);
}

// setup the grid after the page has finished loading
document.addEventListener('DOMContentLoaded', function() {
    $("#alert").html('');
    var gridDiv = document.querySelector('#myGrid');
    new agGrid.Grid(gridDiv, gridOptions);
    var condition1 = encodeURIComponent(condition);
    var httpRequest = new XMLHttpRequest();
    httpRequest.open('POST', '/report/fetchReportData/'+trackerId+'/'+formId+'/'+reportId, true);
    httpRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    httpRequest.send('condition='+condition1+"&headBreadcrumb="+headBreadcrumb+"&urlQuery="+urlQuery);
    httpRequest.onreadystatechange = function() {
        if (httpRequest.readyState === 4 && httpRequest.status === 200) {
            var httpResult = JSON.parse(httpRequest.responseText);
            if (httpResult.max_count == '0') {
                gridOptions.api.setRowData(httpResult.data);
                gridOptions.api.setColumnDefs(httpResult.labels);
            } else {
                gridOptions.localeText = {
                    noRowsToShow: 'Returned records greater than maximum rows to display. Please refine your filters to return less records'
                };                
                gridOptions.api.setColumnDefs(httpResult.labels);
                gridOptions.api.setRowData(httpResult.data);
            }
        }
    };
});

function MyCellRenderer () {}
// gets called once before the renderer is used
MyCellRenderer.prototype.init = function(params) {
    // create the cell
    this.eGui = document.createElement('div');
    if(canRead != 'No') {
        this.eGui.innerHTML='<button type="button" onclick="window.open(\'/wp/viewrecord/'+trackerId+'/'+formId+'/0/'+reportId+'/'+params.value+'?filter='+baseEncode+'\')" class="btn btn-default mr-1 btn-sm" aria-label="Left Align" title="View"><span class="lnr icon-eye" aria-hidden="true"></span></button>'; 
    }
    if(canEdit != 'No') {
        this.eGui.innerHTML+='<button type="button" onclick="window.location.href=\'/wp/editrecord/'+trackerId+'/'+formId+'/0/'+reportId+'/'+params.value+'?filter='+baseEncode+'\'" class="btn btn-default mr-1 btn-sm" aria-label="Left Align" title="Edit"><span class="lnr icon-pencil" aria-hidden="true"></span></button>';
    }
};

// gets called once when grid ready to insert the element
MyCellRenderer.prototype.getGui = function() {
    return this.eGui;
};

function refresh() {
    gridOptions.api.redrawRows();
}
function openInNewTab(url) {
  var win = window.open(url, '_blank');
  win.focus();
}

function downloadCSV() {
    var headBreadcrumb1 = encodeURIComponent(headBreadcrumb);	
    $("#idcondition").val(condition);	
    $("#idheadBreadcrumb").val(headBreadcrumb1);    
    $('#downloadReport').attr('action', "/report/download/"+trackerId+'/'+formId+'/'+reportId+'/CSV');
    document.getElementById('downloadReport').submit();
}
function downloadEXCEL() {
    var headBreadcrumb1 = encodeURIComponent(headBreadcrumb);	
    $("#idcondition").val(condition);	
    $("#idheadBreadcrumb").val(headBreadcrumb1);    
    $('#downloadReport').attr('action', "/report/download/"+trackerId+'/'+formId+'/'+reportId+"/EXCEL");
    document.getElementById('downloadReport').submit();
}
function downloadPDF() {
    var headBreadcrumb1 = encodeURIComponent(headBreadcrumb);	
    $("#idcondition").val(condition);	
    $("#idheadBreadcrumb").val(headBreadcrumb1);    
    $('#downloadReport').attr('action', "/report/download/"+trackerId+'/'+formId+'/'+reportId+"/PDF");
    document.getElementById('downloadReport').submit();
}