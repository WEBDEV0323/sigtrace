/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
$(document).ready(function()
{
    $('[data-toggle="popover"]').popover();
    $('[data-toggle="tooltip"]').tooltip();
}); 

function getCalendarData(requYM) {
    var tid= $('#tid').val();
    var fid= $('#fid').val();
    $.ajax({
        url: "/calendar/get_month_data",
        type:'post',
        data:{'tracker_id':tid,'formId':fid,'month':requYM},
        success:function(respJson) {
            var resp = JSON.parse(respJson);
            var calendar = resp.calendar;
            if (calendar !== '') {
                $('#monthTable').replaceWith('<div class="card-body m-0 p-0" id="monthTable">'+calendar+'</div>');
                reportsCount = resp.reportCount;
                usersCount = resp.usersCount;
                setGridContainersHeight();
                resetCalendarDetailRowHeight();
            }
        }
    });
}