/* jshint shadow:true */
/*jshint sub:true*/

 function getPresentDateTime(div_id){
    var d = new Date();
    $("#"+div_id+" option:not(:selected)");
    return moment(d).format(dateTimeFormat);
 }
 
 function CheckTAT(StartDate, EndDate) {
   StartDate = toformatdatetime(new Date(StartDate),"date");
   EndDate = toformatdatetime(new Date(EndDate),"date");
   StartDate = Date.parse(StartDate)/1000;    //change date time to unix time stamp
   EndDate = Date.parse(EndDate)/1000;
   if (StartDate >= EndDate) {
      return "PASS";
   } else {
      return "FAIL";
   }
 }

function addBusinessDays(theDate, days) 
{
    days = parseInt(days);
    var wks = Math.floor(days/5);
    var dys = days.mod(5);
    var dy = theDate.getDay();

    if (dy === 6 && dys > -1) {
        if (dys === 0) {
            dys-=2;
            dy+=2;
        }
        dys++;
        dy -= 6;
    }
    if (dy === 0 && dys < 1) {
        if (dys === 0) {
            dys+=2;
            dy-=2;
        }
        dys--;
        dy += 6;
    }
    if (dy + dys > 5) dys += 2;
    if (dy + dys < 1) dys -= 2;
    return new Date(theDate.getTime()+(wks*7+dys)*24*60*60*1000);
}

function addBusDays(date1,days)
 { 
   var date1 = formatDateToYYYYMMDD(date1);	
   var expectedDate =  moment(date1).businessAdd(days).format(dateFormat);	
   if (holidayList.includes(expectedDate)) {	
         do {
            expectedDate = substractCalDays(expectedDate,1);	
         } while (holidayList.includes(expectedDate));	
      }	
   return expectedDate;
 }

function addDays(date1, days) 
{
    var date1 = formatDateToYYYYMMDD(date1);	
    var expectedDate =  moment(date1).businessAdd(days).format(dateFormat);	

    // return new Date(theDate.getTime() + days*24*60*60*1000);
    return expectedDate;
}

function addCalDays(date1,days)
{ 
   var date1 = formatDateToYYYYMMDD(date1);
   var expectedDate = moment(date1).add(days, "days").format(dateFormat);
   
   var dt = moment(expectedDate, dateFormat);
   var days = dt.format('dddd');
   if (days == 'Sunday') {
      expectedDate = substractCalDays(expectedDate,2);
   } else if(days == 'Saturday') {
      expectedDate = substractCalDays(expectedDate,1);
   }
   if (holidayList.includes(expectedDate)) {
      do {
         expectedDate = substractCalDays(expectedDate,1);
      } while (holidayList.includes(expectedDate));
   }
   
  var dt = moment(expectedDate, dateFormat);	
  var days = dt.format('dddd');	
  if (days == 'Sunday') {	
     expectedDate = substractCalDays(expectedDate,2);	
  } else if(days == 'Saturday') {	
     expectedDate = substractCalDays(expectedDate,1);	
  }
   return expectedDate;
}

function substractCalDays(date1,days)
{
    var date1 = formatDateToYYYYMMDD(date1);
    return moment(date1).subtract(days, "days").format(dateFormat);	
}

function getPresentDate()
{
   var d = new Date();
   return moment(d).format(dateFormat);
}
function populatefields(val,value) {
   var item = val[0]['Item'][0];
   var arrkey = [];
   for (var ob in item) {
      arrkey.push(ob);
      $("#"+item[ob]).val('');
   }
   var form = val[0]['Form'];
   var id=val[0]['onchangeof'];
   var data = {
      form : form,
      id:id,
      val:value,
      originfields : arrkey,
   };
   var url = '/tracker/getvaluefromotherform';
   $.post(url, data,function(respJson){
      if(respJson!='') {
         var resp = JSON.parse(respJson);
         var ob;
         for (ob in resp[0]) {
               $("#" + item[ob]).val(resp[0][ob]);
         }
      }
   });
   return false;
}

function formatDateToYYYYMMDD(date) {
   var d = new Date(date),
       month = '' + (d.getMonth() + 1),
       day = '' + d.getDate(),
       year = d.getFullYear();

   if (month.length < 2) month = '0' + month;
   if (day.length < 2) day = '0' + day;

   return [Math.abs(year), month, day].join('-');
}

function formatDateDDMMMYYYY(d) {
   var date = new Date(d);
   if ( isNaN( date .getTime() ) ) {
      return d;
   } else {
      var month = [];
      month[0] = "Jan";
      month[1] = "Feb";
      month[2] = "Mar";
      month[3] = "Apr";
      month[4] = "May";
      month[5] = "Jun";
      month[6] = "Jul";
      month[7] = "Aug";
      month[8] = "Sept";
      month[9] = "Oct";
      month[10] = "Nov";
      month[11] = "Dec";
      day = date.getDate();
      if(day < 10) {
         day = "0"+day;
      }
      return    day  + "-" +month[date.getMonth()] + "-" + date.getFullYear();
   }
}

/*
* format date in yyyy-mm-dd hh:ii:ss
*/
function toformatdatetime(date,formattype) {
   var formattedDate = new Date(date);
   var d = formattedDate.getDate(date);
   d = d < 10 ? '0' + d : d;
   var m =  formattedDate.getMonth();
   m += 1;  // JavaScript months are 0-11
   m = m < 10 ? '0' + m : m;
   var y = formattedDate.getFullYear();
   var h = formattedDate.getHours();
   h = h < 10 ? '0' + h : h;
   var i = formattedDate.getMinutes();
   i = i < 10 ? '0' + i : i;
   var s = formattedDate.getSeconds();
   s = s < 10 ? '0' + s : s;
   var format;
   if(formattype=='datetime'){
      format = y+"-"+m+"-"+d+" "+h+":"+i+":"+s;
   } else {
      format = y+"-"+m+"-"+d;
   }
   return format;
}
function toDateTime(date) {
   var str = '';
   var year, month, day, hour, min;
   year = date.getUTCFullYear();
   month = date.getUTCMonth() + 1;
   month = month < 10 ? '0' + month : month;
   day = date.getUTCDate();
   day = day < 10 ? '0' + day : day;
   hour = date.getUTCHours();
   hour = hour < 10 ? '0' + hour : hour;
   min = date.getUTCMinutes();
   min = min < 10 ? '0' + min : min;

   str += year + '-' + month + '-' + day;
   str += ' ' + hour + ':' + min;
   return str;
}
function toDate(date) {
      var str = '';
      var year, month, day, hour, min;
      year = date.getUTCFullYear();
      month = date.getUTCMonth() + 1;
      month = month < 10 ? '0' + month : month;
      day = date.getUTCDate();
      day = day < 10 ? '0' + day : day;
      str += day + '-' + month + '-' + year;
      return str;
}
function getDuration(date1, date2)
{
    return (date2-date1)/(1000*60*60*24);
}
function comboselected(element,value){
    $("#"+element).prop("disabled",false);
    return value;
}
function combonotselected(element){
    $("#"+element).prop("disabled","disabled");
    return "";
}
function applyDisplayRule(element,value){ 

   $("input[name="+element+"]").each(function(){
      if($(this).attr('type')=='checkbox') {
         $(this).prop('checked', false);
      }
      //console.log($(this).is(":checked"));
   });
   
   $("#id_"+element).css("display",value);
   return "";
}
function changeDropdownSelectedValue(id, value) 
{ 
    if ($("#"+id).is("input")) {
        $("#"+id).val(value);
    } else {
       if(value !== "") {
            $("#"+id+" option[value="+value+"]").attr('selected', 'selected');  
       } else {
            $("#"+id).prop("selectedIndex", 0);
        }
    }
    
    return value;
}