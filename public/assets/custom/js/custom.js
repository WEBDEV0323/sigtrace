/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


"use strict";

$(document).ready(function(){
    
    //===== Refresh-Button on Widgets =====//

    $('.widget .toolbar .widget-refresh').click(function() {
            var el = $(this).parents('.widget');
            App.blockUI(el);
            window.setTimeout(function () {
                    App.unblockUI(el);
                    noty({
                            text: '<strong>Widget updated.</strong>',
                            type: 'success',
                            timeout: 1000
                    });
            }, 1000);
    });
});