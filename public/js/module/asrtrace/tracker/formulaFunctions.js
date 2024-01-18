/* jshint shadow:true */
/*jshint sub:true*/
function comboselected(element,value){
    $("#"+element).prop("disabled",false);
    return value;
 }
 
 function combonotselected(element){
    $("#"+element).prop("disabled","disabled");
    return "";
 }
 
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

 function addBusDays(date1,days)
 { 
   return moment(date1).businessAdd(days).format(dateFormat);
 }

 function addCalDays(date1,days)
{ 
   var expectedDate = moment(date1).add(days, "days").format(dateFormat);
   
   var dt = moment(expectedDate, dateFormat);
   var days = dt.format('dddd');
   if (days == 'Sunday') {
      expectedDate = substractCalDays(expectedDate,2);
   } else if(days == 'Saturday') {
      expectedDate = substractCalDays(expectedDate,1);
   }
   // console.log(holidayList);
   // for(var k in holidayList) {
   //    console.log(holidayList[k]);
   // }
   if (holidayList.includes(expectedDate)) {
      do {
         expectedDate = substractCalDays(expectedDate,1);
      } while (holidayList.includes(expectedDate));
   }
   //console.log('date1 :'+date1+'dateFormat :'+dateFormat+ ' convert format :'+ moment(date1, 'YYYY-MM-DD').add(days, "day").format(dateFormat));
   return expectedDate;
}

function substractCalDays(date1,days)
{
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