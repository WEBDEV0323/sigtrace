/* jshint sub:true*/
/* jshint shadow:true */
/* jshint -W061 */
/* jshint -W083 */
$(function () {
  openTab();
  $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) { 
    console.log(e);
    var activatedTab = $(e.target).attr("aria-controls"); // activated tab
    var tabId = $(e.target).attr("dashId");
    var tabData = $(e.target).data('tab'); 
    var previousActivatedTab  = $(e.relatedTarget ).attr("aria-controls"); // previous active tab

    if (activatedTab == 'signalDetection') activatedTab = 'qualitativeAnalysis';
    if (previousActivatedTab == 'signalDetection') previousActivatedTab = 'qualitativeAnalysis';

    $('#'+previousActivatedTab+'Grid').empty();
    $('#'+previousActivatedTab+'Filter').empty();
    
    if(activatedTab!='quantitativeAnalysis') {
      ajaxCallAfterPageLoad(trackerId,formId,activatedTab,tabId,'all');
    }
  });
});
function openTab() {
    // get hash from url
  var hash = location.hash;
  // check if tab matching hash exists
  if (hash && $(hash).length) {
    // now trigger click on appropriate tab
    var pTab = $('a[data-toggle="tab"][aria-controls="' + hash.slice(1) + '"]').parent().parent().parent().parent().attr('id');
    if (pTab != 'undefined'){
        $('a[href="#'+pTab+'"]').click();
    }
    //$('a[href=#' + $('a[data-toggle="tab"][aria-controls="' + hash.slice(1) + '"]').parent().attr('id') + ']').click();
    $('a[data-toggle="tab"][aria-controls="' + hash.slice(1) + '"]').click();        
  }
}
function ajaxCallAfterPageLoad(trackerId,formId,activatedTab,tabId,filter) 
{
    $.ajax({
      url:'/dashboard/getDashboardData/'+trackerId+'/'+formId, 
      dataType: 'json',  // what to expect back from the PHP script, if anything
      cache: false,
      //contentType: false,
      processData: true,
      async: true,                    
      data: {
          'filter':filter,
          'activeTab':activatedTab,
          'tabId':tabId,
        },
      type: 'post',
      beforeSend: function(){ 
          var html = '<div class="loading mt-3 pt-3" id ="load"><img src="/assets/dashboard_spinner.gif" width="10%" class="mx-auto d-block pt-5" alt="loading..." /></div>';
          $("#"+activatedTab).append(html); 
          $('#'+activatedTab+'Grid').empty();
          $('#'+activatedTab+'Filter').empty();
      }, 
      complete: function() { 
          $('#load').remove(); 
          dateRange();
          gridClick();
          $('.selectpicker').selectpicker('render');
      },
      success: renderCallbackGrids,
      
    });
}

function renderCallbackGrids(response) { 
    for(var i in response.data) {  
        var productGrid = '<div class="card btn-primary gridTab" formId="'+response.data[i]['formId']+'" tabid="'+response.data[i]['tabId']+'" asid="'+response.data[i]['as_id']+'" atab="'+response.data[i]['activeTab']+'">';
        productGrid+= '<a href="#/dashboard/list/'+trackerId+'/'+response.data[i]['formId']+'/'+response.data[i]['tabId']+'/'+response.data[i]['as_id']+'" >';
        productGrid+= '<div class="card-body">';
        productGrid+= '<h6 class="card-title text-white">'+response.data[i]['active_substance']+'</h6>';
        productGrid+= '<span class="float-left text-white"> Total : '+response.data[i]['total_cnt']+'</span>';
        productGrid+= '<span class="float-right text-white">Pending : '+response.data[i]['pending_cnt']+'</span>';
        productGrid+= '</div>';
        productGrid+='</a>';
        productGrid+= '</div>';
        $('#'+response.data[i]['activeTab']+'Grid').append(productGrid);
    }
    for(var j in response.filter) {
      switch(response.filter[j].type) { 
        case 'date':
            var html ="";
            html += '<div class="form-group mx-sm-3 mb-2 ">';
            html += '<label for="'+response.filter[j].field+'1">'+response.filter[j].label+'</label>';
            html += '<input type="text" class="form-control daterange" id="'+response.filter[j].field+'" name="date:'+response.filter[j].field+'" value ="'+response.dateRange+'" title ="'+response.dateRange+'" placeholder="'+response.filter[j].label+'" readOnly>';
            html += '</div>';
            $('#'+response.activeTab+'Filter').append(html);
        break;
        case 'select':
          var html ="";
          html += '<div class="form-group mb-2 ">';
          html += '<label for="'+response.filter[j].field+'">'+response.filter[j].label+'</label>';
          html += '<select class="form-control selectpicker" data-live-search="true" id="'+response.filter[j].field+'" name="select:'+response.filter[j].field+'" title ="'+response.filter[j].label+'" multiple data="'+response.filter[j].data+'">';
          // for(var k in response.options) {
          //   html +=  '<option value="'+response.options[k].as_name+'">'+response.options[k].as_name+'</option>';
          // }
          $.each(response.options, function(j, item) {
            html +=  '<option value="'+item.as_name+'">'+item.as_name+'</option>';
          });
          html += '</div>';
          $('#'+response.activeTab+'Filter').append(html);
          $('.selectpicker').selectpicker('val',response.selectedValues);
        break;
        case Default:
            html = '<h6>No filter available</h6>';
            $('#'+response.activeTab+'Filter').append(html);
      }
    }
    var html ="";
    html += '<div class="form-group mx-sm-3 mb-2">';
    html += '<label for="filterButtonTab" > &nbsp; </label>';
    html += '<button type="submit" class="btn btn-primary mb-2 form-control" id="filterButtonTab">Filter</button>';
    html += '</div>';
    html += '<input type ="hidden" id="tabname" name ="tabname" value="'+response.activeTab+'">';
    html += '<input type ="hidden" id="tabid" name ="tabid" value="'+response.tabId+'">';
    $('#'+response.activeTab+'Filter').append(html);
}

$(function() {
  dateRange();
  gridClick();
  $('#filterButton').on('click',function(e){
    var fieldData = JSON.stringify($('#filterForm').serializeArray());
    //console.log(fieldData);
    var filter = encodeURIComponent(window.btoa(fieldData));
    window.location.href = '/dashboard/index/'+trackerId+'/'+formId+'?filter='+filter;
  });
});

function gridClick() {
  $('.gridTab').on('click',function(e){
    e.preventDefault();
    var tabId=$(this).attr('tabid');
    var atab = $(this).attr('atab');
    var asId = $(this).attr('asid');
    var formId=$(this).attr('formId');
    var source=$(this).attr('source');
    if (atab=='quantitativeAnalysis') { 
      var fieldData = JSON.stringify($('#filterForm').serializeArray());
      var filter = encodeURIComponent(window.btoa(fieldData));
      window.location.href = '/quantitative/view/'+trackerId+'/'+formId+'/'+tabId+'/'+asId+'?filter='+filter;
    } else if (atab == 'moleculesourceList') {
      var fieldData = JSON.stringify($('#filterForm').serializeArray());
      var filter = encodeURIComponent(window.btoa(fieldData));
      window.location.href = '/dashboard/source/'+trackerId+'/'+formId+'?tabId='+tabId+'&asId='+asId+'&filter='+filter;
    } else if (source == 'ICSR' || source == 'eRMR') {
      var filter=$(this).attr('filter');
      window.location.href = '/quantitative/view/'+trackerId+'/'+formId+'/'+tabId+'/'+asId+'?filter='+filter;
    } else {
      var fieldData = JSON.stringify($('#'+atab+'Filter').serializeArray());
      
      var filter = encodeURIComponent(window.btoa(fieldData));
      window.location.href = '/dashboard/list/'+trackerId+'/'+formId+'/'+tabId+'/'+asId+'?filter='+filter;
    }
  });
}

function dateRange(){
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
}

$("form").submit(function( event ) {
  var queryData = $('.selectpicker').attr('data');
  var fieldData = $(this).serializeArray();
  event.preventDefault();
  var filterData=[];
  var tabId;
  var activatedTab;
  for(var k in fieldData) {
    if (fieldData[k].name == 'tabid') {
      tabId = fieldData[k].value;
    } else if (fieldData[k].name == 'tabname') {
      activatedTab = fieldData[k].value;
    } else {
      filterData.push(fieldData[k]);
    }
  } 
  filterData.push(queryData);
  
  if(activatedTab!='quantitativeAnalysis') {
    ajaxCallAfterPageLoad(trackerId,formId,activatedTab,tabId,filterData);
  }
});




