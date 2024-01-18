/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
var win;
$(document).ready(function() {
    //console.log(document.cookie);
var cookie= document.cookie;

if (cookie.indexOf('kibana-popUp') == -1) {
 win = window.open(dashboardUrl,"ModalPopUp",
            "toolbar=no," +
            "scrollbars=no," +
            "location=no," +
            "statusbar=no," +
            "menubar=no," +
            "resizable=0," +
            "width=900," +
            "height=600," +
            "left = 490," +
            "top=300");
    setTimeout(function () { 
        document.cookie='kibana-popUp=true';
        win.close();
        location.reload();
    }, popUpTime);
} else {
    $('#dashboard').attr('src', dashboardUrl);
}
});



function kibanapopUp(url) {
    win = window.open(url,"ModalPopUp",
            "toolbar=no," +
            "scrollbars=no," +
            "location=no," +
            "statusbar=no," +
            "menubar=no," +
            "resizable=0," +
            "width=900," +
            "height=600," +
            "left = 300," +
            "top=100");
    setTimeout(function () { 
        document.cookie='kibana-popUp=true';
        win.close();
        location.reload();
    }, popUpTime);
    $('#dashboard').attr('src', dashboardUrl);
}