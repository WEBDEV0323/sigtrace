/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var paramsobj;
var keyname = btoa('Signal calendar');

var columnDefs = [
    {headerName: "Import Date And Time", field: "created_date_time", tooltipField: "created_date_time", autoHeight:true, width: 250},
    {
        headerName: "File Name", 
        field: "file_name", 
        autoHeight:true, 
        width: 250, 
        cellRenderer: function(params) {
              return '<a href="/aws/downloadsignalcalendarfile/'+keyname+'/'+btoa(params.value)+'"  title="'+params.value.split('_uploadsignalcal')[0]+'">'+params.value.split('_uploadsignalcal')[0]+'.xlsx</a>';                    
            }         
    },
    {headerName: "Uploaded By", field: "created_by", tooltipField: "created_by", autoHeight:true, width: 150}
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
    // onCellClicked: changeMedicalValue, 
    components: {
        customTooltip: CustomTooltip,
    }
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
    // var trackerId = '109';
    // var formId = '199';
    var dashboardId = '0';
    // var filter = 'all';
    // var asId = '0';
    // var type = 'all';
    new agGrid.Grid(gridDiv, gridOptions);
    var data = {
            trackerId : trackerId,
            formId : formId,
            // dashboardId : '0',
            // filter : filter,
            // asId : asId,
            // type :type,
    };    
    // alert (trackerId);
    $.ajax({
        url:'/signalcalendar/fetchAllData/'+trackerId+'/'+formId+'/'+dashboardId+'/'+asId+'/'+type+'/'+filter, // point to server-side PHP script 
        dataType: 'json',  // what to expect back from the PHP script, if anything
        cache: false,
        data:data,
        type: 'post',
        success: function(data) {
            gridOptions.api.setRowData(data.data);
        }
    });
});

function CustomTooltip () {}
CustomTooltip.prototype.init = function(params) {
    var eGui = this.eGui = document.createElement('div');
    var color = '#ececec';
    var data = params.api.getDisplayedRowAtIndex(params.rowIndex).data;


    eGui.classList.add('custom-tooltip');
    eGui.style['background-color'] = color;
    // if (params.column.colId == 'rationale') {
    //     var temp = [];
    //     if(params.value != '') {
    //         temp = params.value.split("#");
    //     }
    //     if(temp.length > 0) {
    //         for (i = 0; i < temp.length; i++) {
    //             eGui.innerHTML += '<p><span>' + temp[i] + '</span></p>';                    
    //         }
    //     } else {
    //         eGui.innerHTML = '' ;
    //     }
    // } else {
        eGui.innerHTML = '<p><span>' + params.value + '</span></p>';        
    // }
};

CustomTooltip.prototype.getGui = function() {
    return this.eGui;
};
$(document).ready(function () {
    var notEmpty = function (value, callback) {
        if (!value || String(value).length === 0) {
            callback(false);
        } else {
            callback(true);
        }
    };                                    
    $("#btnsigcalImport").click(function () {
        var fileData = document.getElementById("importCsvFile");
        var txt = "";
        var allowedFiles = [".xls", ".xlsx"];
        var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + allowedFiles.join('|') + ")$");
        var formData = new FormData();
        var allowedFileSize = 20971520;
        if ('files' in fileData) {
            if (fileData.files.length == 0) {
               txt = "Please select a file.";
            } else if(fileData.files.item(0).size >= allowedFileSize) { 
                txt = "Please upload file of max size "+allowedFileSize/(1024*1024) +" MB.";
            } else if(!regex.test(fileData.files.item(0).name.toLowerCase())) {
                txt = "Please upload files having extensions: " + allowedFiles.join(', ') + " only";
            } else {            
                $("#loading").show();
                $("#btnImport").prop("disabled", true);
                formData.append('file',fileData.files.item(0));
                formdata = formData.append('file','Signal calendar');
                var ext = fileData.files.item(0).name.substr(fileData.files.item(0).name.lastIndexOf('.') + 1);

                $.ajax({
                    url:'/signalcalendar/signalcalendarImport/'+trackerId+'/'+formId, // point to server-side PHP script 
                    dataType: 'json',  // what to expect back from the PHP script, if anything
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: formData,                     
                    type: 'post',
                    success: function(data) {
                        //alert(data);
                        $("#loading").hide();
                        $("#btnsigcalImport").prop("disabled", false);
                        if(data[0] == 0) {
                            if(data[2] == 0) {
                               $("#errorcsv").html(data[1]);
                            } else {
                               $("#alert").html(data[1]); 
                            }                            
                        } else if (data.result == 2) {
                            window.location.replace("#");
                        } else {
                            label=data;
                            $("#auditlogmsg").val(label[7]);
                            $('#part1').show();
                            // $('#part2').show();
                            // $('#displaybuttons').show();
                            location.reload();                            
                        }
                    }
                });
            }
        }
        $("#errorcsv").html(txt);
    });
    $('#importCsvFile').on('change',function() {
        var fileName = $(this).val();
        var cleanFileName = fileName.replace('C:\\fakepath\\', " ");
        $(this).next('.custom-file-label').html(cleanFileName);
        $("#btnsigcalImport").prop("disabled", false);
    });
});