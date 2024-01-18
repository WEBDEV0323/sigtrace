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
var gridOptions = {
    defaultColDef: {
        filterParams: { applyButton: true, clearButton:true}
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
    enableBrowserTooltips: true
};
function onPageSizeChanged(newPageSize) {
    var value = document.getElementById('page-size').value;
    gridOptions.api.paginationSetPageSize(Number(value));
}

function getBooleanValue(cssSelector) {
    return document.querySelector(cssSelector).checked === true;
}

function onBtExport() {
    var params = {};
    gridOptions.api.exportDataAsCsv(params);
}

// setup the grid after the page has finished loading1
document.addEventListener('DOMContentLoaded', function() {
    var gridDiv = document.querySelector('#myGrid');
    new agGrid.Grid(gridDiv, gridOptions);
    
    var httpRequest = new XMLHttpRequest();
    httpRequest.open('POST', '/report/fetchReportData/'+trackerId+'/'+formId+'/'+reportId, true);
    httpRequest.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    httpRequest.send('condition='+condition+"&headBreadcrumb="+headBreadcrumb+"&urlQuery="+urlQuery);
    httpRequest.onreadystatechange = function() {
        if (httpRequest.readyState === 4 && httpRequest.status === 200) {
            var httpResult = JSON.parse(httpRequest.responseText);
            gridOptions.api.setRowData(httpResult.data);
            gridOptions.api.setColumnDefs(httpResult.labels);
        }
    };
});

function refresh()
{
    gridOptions.api.redrawRows();
}
function openInNewTab(url) {
  var win = window.open(url, '_blank');
  win.focus();
}

function downloadCSV()
{
    $('#downloadReport').attr('action', "/report/downloadCSV/"+trackerId+'/'+formId+'/'+reportId);
    document.getElementById('downloadReport').submit();
}
function downloadEXCEL()
{
    $('#downloadReport').attr('action', "/report/downloadEXCEL/"+trackerId+'/'+formId+'/'+reportId);
    document.getElementById('downloadReport').submit();
}