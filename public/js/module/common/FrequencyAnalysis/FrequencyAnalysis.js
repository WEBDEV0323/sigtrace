// specify the columns agNumberColumnFilter
var columnDefs = [
    {headerName: "Risk/Listed/ Special situation", field: "ptname", width: 240},
    {headerName: "Type(Unlisted, IIR, IPR, SS, CME, Listed)", field: "type", width: 280},
    {headerName: "Period 1 (Previous)", marryChildren: true,
        children : [ 
            { headerName: '<div class="form-group row mt-3"><label class="col-sm-4 col-form-label">Exposure</label><div class="col-sm-6"><input type="number" id="pE" class="form-control" placeholder="Exposure" name="pE" value="1" /></div></div>', field:"pE",
                children: [
                    {headerName: "Serious", field: "preS", width: 120},
                    {headerName: "Non Serious", field: "preNs", width: 150},
                    {headerName: "Total", field: "preTot", width: 120}
                ]
            }
        ]
    },
    {headerName: "Period 2 (Current)", marryChildren: true,
        children : [ 
            { headerName: '<div class="form-group row mt-3"><label class="col-sm-4 col-form-label">Exposure</label><div class="col-sm-6"><input type="number" id="cE" class="form-control" placeholder="Exposure" name="cE" value="1" /></div></div>', field:"cE", 
                children: [
                    {headerName: "Serious", field: "curS", width: 120},
                    {headerName: "Non Serious", field: "curNs", width: 150},
                    {headerName: "Total", field: "curTot", width: 120}
                ]
            }
        ]
    },
    {headerName: "Increase?", marryChildren: true,
                children: [
                    {headerName: "Serious", field: "incS", width: 120},
                    {headerName: "Non Serious", field: "incNs", width: 150},
                    {headerName: "Total", field: "incTot", width: 120},
                    {headerName: "Any", field: "incAny", width: 120}
                ]
    },
    {headerName: "C Value", marryChildren: true,
                children: [
                    {headerName: "Serious", field: "cS", width: 120},
                    {headerName: "Non Serious", field: "cNs", width: 150},
                    {headerName: "Total", field: "cTot", width: 120}
                ]
    }
    
];
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
        minWidth: 150,
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
    components: {
        'myCellRenderer': MyCellRenderer
    }  
};
function onPageSizeChanged(newPageSize) {
    var value = document.getElementById('page-size').value;
    gridOptions.api.setDomLayout('normal');
    document.querySelector('#myGrid').style.height = '495px';
    gridOptions.api.paginationSetPageSize(Number(value));
}

function getBooleanValue(cssSelector) {
    return document.querySelector(cssSelector).checked === true;
}

function onBtExport() {
    var params = {};
    gridOptions.api.exportDataAsCsv(params);
}
// function to act as a class
function MyCellRenderer () {}
// gets called once before the renderer is used
MyCellRenderer.prototype.init = function(params) {
    // create the cell
    this.eGui = document.createElement('div');
};

// gets called once when grid ready to insert the element
MyCellRenderer.prototype.getGui = function() {
    return this.eGui;
};

// setup the grid after the page has finished loading
document.addEventListener('DOMContentLoaded', function() {
    var gridDiv = document.querySelector('#myGrid');
    new agGrid.Grid(gridDiv, gridOptions);
    agGrid.simpleHttpRequest({url: '/frequency/fetchAllData/'+trackerId+'/'+formId+'/'+$('#pE').val()+'/'+$('#cE').val()})
          .then(function(data) {
              gridOptions.api.setRowData(data.data);
    });
});

$(document).on("change", "#pE, #cE", function () {
    if ($('#pE').val() > 0 ) {
    $('#loading').show();
    agGrid.simpleHttpRequest({url: '/frequency/fetchAllData/'+trackerId+'/'+formId+'/'+$('#pE').val()+'/'+$('#cE').val()})
          .then(function(data) {
            gridOptions.api.refreshCells();
            gridOptions.api.setRowData(data.data);
            $('#loading').hide();
    });
    } else {
         $("#alertError").html('Previous Exposure value should be greater that 0');
         $('#alertError').addClass('alert alert-danger');
         $('#alertError').fadeIn('slow');
         $('html, body').animate({ scrollTop: 0 }, 0);
         window.setTimeout(function () {
            $(".alert").fadeTo(500, 1).slideUp(500, function () {
               $(this).empty();
            });
         }, 5000);
    }
});
