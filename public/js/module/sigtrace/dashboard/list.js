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
  }
  
  // End of POC code
  
  var gridOptions = {
    defaultColDef: {
        minWidth: 150,
        filter: true,
        resizable: true,

      
           // POC code
           // filter: 'agTextColumnFilter',
            filterParams: filterParams,
            // defaultColDef: {
            //     filter: true// set filtering on for all columns
                
            // },
            // End of POC
          
    },
    columnDefs: columnDefs,
    rowData: null,
    enableSorting: true,
    multiSortKey: 'ctrl',
    enableFilter: true,
    suppressRowClickSelection: true,
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
    components: {
        'myCellRenderer': MyCellRenderer,
        'anchorTag': AnchorTag,
        'applyFilter':ApplyFilter
    },
    
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
function ApplyFilter () {}

ApplyFilter.prototype.init = function(params) { 
    this.eGui = document.createElement('div'); 
    this.eGui.innerHTML = params.value;

    var lFilter = JSON.parse(params.colDef.filterParams); 
    for(var i in lFilter) { 
        var filterComponent = gridOptions.api.getFilterInstance(lFilter[i][0]);
        var model = filterComponent.getModel();   
     
        if (model === null) { 
            this.clearFilters();
            document.querySelector('#listFilter').innerHTML = '';
        } else { 
            filterComponent.setModel({
                type: lFilter[i][1].type,
                filter: lFilter[i][1].filter
            });
            gridOptions.api.onFilterChanged();
            document.querySelector('#listFilter').innerHTML = params.colDef.filterParams;
        }
    } 
};


ApplyFilter.prototype.getGui = function() {
    return this.eGui;
};

ApplyFilter.prototype.clearFilters = function() {
    gridOptions.api.setFilterModel(null);
    gridOptions.api.onFilterChanged();
};


function saveFilterModel(param, mode) { 
    var savedFilters = '[]';
    window.savedModel = gridOptions.api.getFilterModel();
    
    var listFilter = JSON.stringify(Object.entries(window.savedModel));
   
    document.querySelector('#listFilter').innerHTML = listFilter;
    if (mode == 'edit') {
        window.location.href='/wp/editrecord/'+trackerId+'/'+formId+'/'+dashboardId+'/'+asId+'/'+param+'?listfilter='+encodeURIComponent(window.btoa(listFilter))+'&filter='+filter+'&cond='+condition;
    } else {
        window.open('/wp/viewrecord/'+trackerId+'/'+formId+'/'+dashboardId+'/'+asId+'/'+param+'?listfilter='+encodeURIComponent(window.btoa(listFilter))+'&filter='+filter+'&cond='+condition);
    }
}

function AnchorTag () {}
AnchorTag.prototype.init = function(params) { 
    this.eGui = document.createElement('div');
    this.eGui.innerHTML = params.value;
};
AnchorTag.prototype.getGui = function() {
    return this.eGui;
};
// function to act as a class
function MyCellRenderer () {}
// gets called once before the renderer is used
MyCellRenderer.prototype.init = function(params) { 
    // create the cell
    this.eGui = document.createElement('div');
    
    this.eGui.innerHTML='<button type="button" onclick="saveFilterModel('+params.value+',\'view\')" onclick1="" class="btn btn-default mr-1 btn-sm saveFilterModel" aria-label="Left Align" title="View"><span class="lnr icon-eye" aria-hidden="true"></span></button>'; 
    
    this.eGui.innerHTML+='<button type="button"  onclick="saveFilterModel('+params.value+',\'edit\')" onclick1="" class="btn btn-default mr-1 btn-sm saveFilterModel" aria-label="Left Align" title="Edit" ><span class="lnr icon-pencil" aria-hidden="true"></span></button>';
};

// gets called once when grid ready to insert the element
MyCellRenderer.prototype.getGui = function() {
    return this.eGui;
};
// setup the grid after the page has finished loading
document.addEventListener('DOMContentLoaded', function() {
    var gridDiv = document.querySelector('#myGrid');
    new agGrid.Grid(gridDiv, gridOptions);
    agGrid.simpleHttpRequest({url: '/dashboard/fetchAllData/'+trackerId+'/'+formId+'/'+dashboardId+'/'+asId+'?filter='+filter+'&listFilter='+listFilter+'&cond='+condition})
          .then(function(data) {
              gridOptions.api.setRowData(data.data);
              gridOptions.api.setColumnDefs(data.labels);
          });
});

$('#deleteRecordModel').on('show.bs.modal', function (event) {
    $("#forReason").html("");
    $('#reason').val("");
	var button = $(event.relatedTarget); 
	var href = button.data('link');
    $("#href").val(href);
});

$('#reasonfordelete').on('click',function(e){
    var href = $("#href").val();
    $("#forReason").html("");
    e.preventDefault();
    var reasonForChange = $('#reason').serialize();
    if(reasonForChange==='addcomment='){
        $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
        return false;
    }
    $.post(href,reasonForChange,function(data){
            $('#alert_deleteRecord').html(data);
            $('#alert_deleteRecord').addClass('alert alert-success');
    }).fail(function(){
            $('#alert_deleteRecord').html("Something went wrong!!");
            $('#alert_deleteRecord').addClass('alert alert-danger');
    }).always(function(){
        $('#deleteRecordModel').modal('hide');
        window.setTimeout(function () {
            $(".alert").fadeTo(500, 0).slideUp(500, function () {
                $('#alert_csv').removeClass('alert');
                $(this).remove();
                location.reload();
            });
        }, 5000);
    });
});


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
    window.location.href = '/dashboard/list/'+trackerId+'/'+formId+'/'+dashboardId+'/'+asId+'?filter='+filter;
});