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
        minWidth: 150,
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
    if(canRead != 'No') {
        this.eGui.innerHTML='<button type="button" onclick="window.location.href=\'/tracker/viewrecord/'+trackerId+'/'+formId+'/'+params.value+'/'+type+'/'+filter+'\'" class="btn btn-default mr-1 btn-sm" aria-label="Left Align" title="View"><span class="lnr icon-eye" aria-hidden="true"></span></button>'; 
    }
    if(canEdit != 'No') {
        this.eGui.innerHTML+='<button type="button" onclick="window.location.href=\'/tracker/editrecord/'+trackerId+'/'+formId+'/'+params.value+'/'+type+'/'+filter+'\'" class="btn btn-default mr-1 btn-sm" aria-label="Left Align" title="Edit"><span class="lnr icon-pencil" aria-hidden="true"></span></button>';
    }
    if(canDelete != 'No') {  
        this.eGui.innerHTML+='<button type="button" data-toggle="modal" data-target="#deleteRecordModel" data-link="/tracker/deleteRecordfromform/'+trackerId+'/'+formId+'/'+params.value + '" href="#/tracker/deleterecordfromform/'+trackerId+'/'+formId+'/'+params.value + '" class="btn btn-default mr-1 btn-sm" title="Delete" aria-label="Left Align"><span class="lnr icon-trash2" aria-hidden="true"></span></button>';
    }
    if(canEdit != 'No') {
        var wLink=jQuery.parseJSON(workflowLink);
        for(var k in wLink) {
            if(wLink[k].can_update == 'Yes')
                this.eGui.innerHTML+='<button type="button" onclick="window.location.href=\'/tracker/editrecord/'+trackerId+'/'+formId+'/'+params.value+'/'+type+'/'+filter+'/'+wLink[k].workflow_id+'\'" class="btn btn-default mr-1 btn-sm" data-toggle="tooltip" title="'+wLink[k].workflow_name+'" aria-label="Left Align"><span aria-hidden="true">'+wLink[k].workflow_name.charAt(0).toUpperCase()+'</span></button>';
         }
    }
};

// gets called once when grid ready to insert the element
MyCellRenderer.prototype.getGui = function() {
    return this.eGui;
};

// setup the grid after the page has finished loading
document.addEventListener('DOMContentLoaded', function() {
    var gridDiv = document.querySelector('#myGrid');
    new agGrid.Grid(gridDiv, gridOptions);
    agGrid.simpleHttpRequest({url: '/aDashboard/fetchAllData/'+trackerId+'/'+formId+'/'+type+'/'+filter})
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