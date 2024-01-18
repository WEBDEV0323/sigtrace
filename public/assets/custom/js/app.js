/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

var App = function() {
    "use strict";
    var handleWidgets = function() {
            $('body').on('click', '.widget .toolbar .widget-collapse', function() {
                    var widget         = $(this).parents(".widget");
                    var widget_content = widget.children(".widget-content");
                    var widget_chart   = widget.children(".widget-chart");
                    var divider        = widget.children(".divider");

                    if (widget.hasClass('widget-closed')) {
                            // Open Widget
                            $(this).children('i').removeClass('icon-chevron-up-circle').addClass('icon-chevron-down-circle');
                            widget_content.slideDown(200, function() {
                                    widget.removeClass('widget-closed');
                            });
                            widget_chart.slideDown(200);
                            divider.slideDown(200);
                    } else {
                            // Close Widget
                            $(this).children('i').removeClass('icon-chevron-down-circle').addClass('icon-chevron-up-circle');
                            widget_content.slideUp(200, function() {
                                    widget.addClass('widget-closed');
                            });
                            widget_chart.slideUp(200);
                            divider.slideUp(200);
                    }
            });
    };
    return {
        init: function() {
            handleWidgets(); // Handle collapse and expand from widgets
        },
        // Wrapper function to block elements (indicate loading)
        blockUI: function (el, centerY) {
                var el = $(el);
                el.block({
                        message: '<img src="./assets/img/ajax-loading.gif" alt="">',
                        centerY: centerY != undefined ? centerY : true,
                        css: {
                                top: '10%',
                                border: 'none',
                                padding: '2px',
                                backgroundColor: 'none'
                        },
                        overlayCSS: {
                                backgroundColor: '#000',
                                opacity: 0.05,
                                cursor: 'wait'
                        }
                });
        },

        // Wrapper function to unblock elements (finish loading)
        unblockUI: function (el) {
                $(el).unblock({
                        onUnblock: function () {
                                $(el).removeAttr("style");
                        }
                });
        }
    };
}();
