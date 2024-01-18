/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/* jshint sub:true*/
/* jshint shadow:true */
/* jshint -W061 */

var hot;
var hotdates;
$(document).ready(function () {
    var notEmpty = function (value, callback) {
        if (!value || String(value).length === 0) {
            callback(false);
        } else {
            callback(true);
        }
    };                                    
    $("#btnImport_old").click(function () {
        var fileData = document.getElementById("importCsvFile");
        var txt = "";
        var allowedFiles = [".csv", ".xls", ".xlsx", ".pdf", ".doc", ".docx"];
        var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + allowedFiles.join('|') + ")$");
        var formData = new FormData();
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
                $.ajax({
                    url:'/data/readImportFile/'+trackerId+'/'+formId, // point to server-side PHP script 
                    dataType: 'json',  // what to expect back from the PHP script, if anything
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: formData,                     
                    type: 'post',
                    success: function(data) {
                        $("#loading").hide();
                        $("#btnImport").prop("disabled", false);
                        if(data[0] == 0) {
                            if(data[2] == 0) {
                               $("#errorcsv").html(data[1]);
                            } else {
                               $("#alert").html(data[1]); 
                            }                            
                        } else if (data.result == 2) {
                            window.location.replace("/");
                        } else {
                            label=data;
                            $("#auditlogmsg").val(label[7]);
                            $('#part1').hide();
                            $('#part2').show();
                            $('#displaybuttons').show();
                            dataObj = eval(JSON.stringify(label[2]));
                            dateObj = eval(JSON.stringify(label[5]));
                            delets=false;
                            if(dataObj==undefined){
                                dataObj=[[null,null,null]] ;
                                delets=true;
                            }
                            if(dataObj==',,' && type==1){
                                $('#example1').hide();
                                $('#displaybuttons').hide();
                                $('#noRecord').show();
                            } else {
                                var container = document.getElementById('example1');
                                var datecontainer = document.getElementById('actsubdates');
                                hotdates = new Handsontable(datecontainer, {
                                    data : dateObj,
                                    rowHeaders: true,
                                    columns:label[6],
                                    stretchH: "all",
                                    autoWrapRow: false,
                                    colHeaders:label[4],
                                    filters: true, 
                                    dropdownMenu: true,
                                    licenseKey: 'non-commercial-and-evaluation',
                                });                        
                                hot = new Handsontable(container, {
                                    data : dataObj,
                                    rowHeaders: true,
                                    columns:label[3],
                                    stretchH: "all",
                                    autoWrapRow: false,
                                    colHeaders:label[1],
                                    filters: true, 
                                    dropdownMenu: true,
                                    licenseKey: 'non-commercial-and-evaluation',
                                });  
                                $("#alert").html(label[7]);
                                $('#example1').hide();
                            }                            
                        }
                    }
                });
            }
        }
        $("#errorcsv").html(txt);
    });

    $("#btneRMRImport").click(function () {
        $("#errorcsv").html('');
        var txt = '';
        var allowedFiles = [".csv", ".xls", ".xlsx", ".pdf", ".doc", ".docx"];
        var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + allowedFiles.join('|') + ")$");        
        var fileData = document.getElementById("import_ermr_file");
        var formData = new FormData();
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
                $.ajax({
                    url:'/data/readErmrImportFile/'+trackerId+'/'+formId,  
                    dataType: 'json',  
                    cache: false,
                    contentType: false,
                    processData: false,
                    data: formData,                     
                    type: 'post',
                    success: function(data) {
                        $("#loading").hide();
                        $("#btnImport").prop("disabled", false);
                        if(data.result == 1) {
                            location.reload();
                        } else if (data.result == 2) {
                            window.location.replace("/");
                        } else {
                            $("#loading").hide();
                            $("#errorcsv").html(data.message);
                        }
                    }                    
                });
            }            
        }
        $("#errorcsv").html(txt);
    });

    $('#import_ermr_file').on('change',function() {
        var fileName = $(this).val();
        var cleanFileName = fileName.replace('C:\\fakepath\\', " ");
        $(this).next('.custom-file-label').html(cleanFileName);
        $("#btneRMRImport").prop("disabled", false);
    });
    
    $('#importCsvFile').on('change',function() {
        var fileName = $(this).val();
        var cleanFileName = fileName.replace('C:\\fakepath\\', " ");
        $(this).next('.custom-file-label').html(cleanFileName);
        $("#btnImport").prop("disabled", false);
    });

    $('#deleteRecordModel').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget); 
        var href = button.data('link');
        $('#reasonfordelete').on('click',function(e){
            e.preventDefault();
            var reasonForChange = $('#reason').serialize();
            if(reasonForChange==='addcomment='){
                $("#forReason").html("Please enter reason for delete.");
                return false;
            }
            $.post(href,reasonForChange,function(data) {
                $('#alert_deleteRecord').html(data);
                $('#alert_deleteRecord').addClass('alert alert-success');
            }).fail(function() {
                $('#alert_deleteRecord').html("Something went wrong!!");
                $('#alert_deleteRecord').addClass('alert alert-danger');
            }).always(function() {
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
    });

    $("#btnClose").click(function () {
        $("#errorcsv").html('');
        $("#alert").html('');
        $('#importCsvFile').val('');
        $('.custom-file-label').html('Choose file');
    });

    $("#btnCancel").click(function () {
        location.reload();
    });

    // $("#btnSave").click(function () {
        $("#btnImport").click(function () {
        if($("tr").has("td.htInvalid").length==0) { //if some error is there while entering data
            var fileData = document.getElementById("importCsvFile");
            var formData = new FormData();
            $("#loading").show();
            formData.append('auditlogmsg',$("#auditlogmsg").val());
            formData.append('file',fileData.files.item(0));
           // formData.append('data',encodeURIComponent(JSON.stringify(hot.getData())));
            formData.append('trackerId',trackerId);
            formData.append('formId',formId);
            // formData.append('header',JSON.stringify(hot.getColHeader()));
            // formData.append('datesdata',encodeURIComponent(JSON.stringify(hotdates.getData())));
            // formData.append('datesheader',JSON.stringify(hotdates.getColHeader()));
            
            $.ajax({ 
                url: '/data/processImportFile/'+trackerId+'/'+formId,
                type:'post',
                contentType: false,
                processData: false,
                dataType:'json',
                data:formData,
                success:function(data) {
                    if(data.result == 1) {
                        location.reload();
                    } else if (data.result == 2) {
                        window.location.replace("/");
                    } else {
                        $("#loading").hide();
                        $("#errorcsv").html(data.message);
                    }
                }
            });
        } else {
            $("#errorcsv").html('Data is incorrect.');
            $("#loading").hide();
        }
    });
});
$("#btnliteratureImport").click(function () {
    var fileData = document.getElementById("importCsvFile");
    var txt = "";
    var allowedFiles = [".csv", ".xls", ".xlsx", ".pdf", ".doc", ".docx"];
    var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + allowedFiles.join('|') + ")$");
    var formData = new FormData();
    if ('files' in fileData) {
        if (fileData.files.length == 0) {
           txt = "Please select a file.";
        } else if(fileData.files.item(0).size >= allowedFileSize) { 
            txt = "Please upload file of max size "+allowedFileSize/(1024*1024) +" MB.";
        } else if(!regex.test(fileData.files.item(0).name.toLowerCase())) {
            txt = "Please upload files having extensions: " + allowedFiles.join(', ') + " only";
        } else {            
            $("#loading").show();
            $("#btnliteratureImport").prop("disabled", true);
           formData.append('file',fileData.files.item(0));
           formdata = formData.append('file','literature');
           var ext = fileData.files.item(0).name.substr(fileData.files.item(0).name.lastIndexOf('.') + 1);
           if (ext == "pdf" || ext == "doc" || ext == "docx") {
            var fcall = "importanduploadtos3";
            } 
            if (ext == "csv" || ext == "xls" || ext == "xlsx") {
                var fcall = "readlitImportFile";
                }
            $.ajax({
                url:'/data/'+fcall+'/'+trackerId+'/'+formId, // point to server-side PHP script
                dataType: 'json',  // what to expect back from the PHP script, if anything
                cache: false,
                contentType: false,
                processData: false,
                data: formData,                     
                type: 'post',
                success: function(data) {
                    //alert(data);
                    $("#loading").hide();
                    $("#btnliteratureImport").prop("disabled", false);
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
$("#btnaggrImport").click(function () {
    var fileData = document.getElementById("importCsvFile");
    var txt = "";
    var allowedFiles = [".csv", ".xls", ".xlsx", ".pdf", ".doc", ".docx"];
    var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + allowedFiles.join('|') + ")$");
    var formData = new FormData();
    if ('files' in fileData) {
        if (fileData.files.length == 0) {
           txt = "Please select a file.";
        } else if(fileData.files.item(0).size >= allowedFileSize) { 
            txt = "Please upload file of max size "+allowedFileSize/(1024*1024) +" MB.";
        } else if(!regex.test(fileData.files.item(0).name.toLowerCase())) {
            txt = "Please upload files having extensions: " + allowedFiles.join(', ') + " only";
        } else {            
            $("#loading").show();
            $("#btnaggrImport").prop("disabled", true);
           formData.append('file',fileData.files.item(0));
           formdata = formData.append('file','aggregate_reports');
           
           var ext = fileData.files.item(0).name.substr(fileData.files.item(0).name.lastIndexOf('.') + 1);
        //    if (ext == "pdf" || ext == "doc" || ext == "docx" || ext == "csv" || ext == "xls" || ext == "xlsx") {
        //        var fcall = "importanduploadtos3";
        //    } 
        if (ext == "pdf" || ext == "doc" || ext == "docx") {
            var fcall = "importanduploadtos3";
            } 
            if (ext == "csv" || ext == "xls" || ext == "xlsx") {
                var fcall = "readcommanImportFile";
                }
            $.ajax({
                url:'/data/'+fcall+'/'+trackerId+'/'+formId, // point to server-side PHP script
                dataType: 'json',  // what to expect back from the PHP script, if anything
                cache: false,
                contentType: false,
                processData: false,
                data: formData,                     
                type: 'post',
                success: function(data) {
                    //alert(data);
                    $("#loading").hide();
                    $("#btnaggrImport").prop("disabled", false);
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


$("#btrmpImport").click(function () {
    var fileData = document.getElementById("importCsvFile");
    var txt = "";
    var allowedFiles = [".csv", ".xls", ".xlsx", ".pdf", ".doc", ".docx"];
    var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + allowedFiles.join('|') + ")$");
    var formData = new FormData();
    if ('files' in fileData) {
        if (fileData.files.length == 0) {
           txt = "Please select a file.";
        } else if(fileData.files.item(0).size >= allowedFileSize) { 
            txt = "Please upload file of max size "+allowedFileSize/(1024*1024) +" MB.";
        } else if(!regex.test(fileData.files.item(0).name.toLowerCase())) {
            txt = "Please upload files having extensions: " + allowedFiles.join(', ') + " only";
        } else {            
            $("#loading").show();
            $("#btrmpImport").prop("disabled", true);
           formData.append('file',fileData.files.item(0));
           formdata = formData.append('file','risk_management_plans');
           
           var ext = fileData.files.item(0).name.substr(fileData.files.item(0).name.lastIndexOf('.') + 1);
        //    if (ext == "pdf" || ext == "doc" || ext == "docx" || ext == "csv" || ext == "xls" || ext == "xlsx") {
        //        var fcall = "importanduploadtos3";
        //    } 
        if (ext == "pdf" || ext == "doc" || ext == "docx") {
            var fcall = "importanduploadtos3";
            } 
            if (ext == "csv" || ext == "xls" || ext == "xlsx") {
                var fcall = "readcommanImportFile";
                }

            $.ajax({
                
                url:'/data/'+fcall+'/'+trackerId+'/'+formId, // point to server-side PHP script 
                dataType: 'json',  // what to expect back from the PHP script, if anything
                cache: false,
                contentType: false,
                processData: false,
                data: formData,                     
                type: 'post',
                success: function(data) {
                    //alert(data);
                    $("#loading").hide();
                    $("#btrmpImport").prop("disabled", false);
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

$("#btrrImport").click(function () {
    var fileData = document.getElementById("importCsvFile");
    var txt = "";
    var allowedFiles = [".csv", ".xls", ".xlsx", ".pdf", ".doc", ".docx"];
    var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + allowedFiles.join('|') + ")$");
    var formData = new FormData();
    if ('files' in fileData) {
        if (fileData.files.length == 0) {
           txt = "Please select a file.";
        } else if(fileData.files.item(0).size >= allowedFileSize) { 
            txt = "Please upload file of max size "+allowedFileSize/(1024*1024) +" MB.";
        } else if(!regex.test(fileData.files.item(0).name.toLowerCase())) {
            txt = "Please upload files having extensions: " + allowedFiles.join(', ') + " only";
        } else {            
            $("#loading").show();
            $("#btrrImport").prop("disabled", true);
           formData.append('file',fileData.files.item(0));
           formdata = formData.append('file','regulatory_recommendations');
           
           var ext = fileData.files.item(0).name.substr(fileData.files.item(0).name.lastIndexOf('.') + 1);
           if (ext == "pdf" || ext == "doc" || ext == "docx") {
            var fcall = "importanduploadtos3";
            } 
            if (ext == "csv" || ext == "xls" || ext == "xlsx") {
                var fcall = "readcommanImportFile";
                }
            $.ajax({
                
                url:'/data/'+fcall+'/'+trackerId+'/'+formId, // point to server-side PHP script 
                dataType: 'json',  // what to expect back from the PHP script, if anything
                cache: false,
                contentType: false,
                processData: false,
                data: formData,                     
                type: 'post',
                success: function(data) {
                    //alert(data);
                    $("#loading").hide();
                    $("#btrrImport").prop("disabled", false);
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

$("#btctImport").click(function () {
    var fileData = document.getElementById("importCsvFile");
    var txt = "";
    var allowedFiles = [".csv", ".xls", ".xlsx", ".pdf", ".doc", ".docx"];
    var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + allowedFiles.join('|') + ")$");
    var formData = new FormData();
    if ('files' in fileData) {
        if (fileData.files.length == 0) {
           txt = "Please select a file.";
        } else if(fileData.files.item(0).size >= allowedFileSize) { 
            txt = "Please upload file of max size "+allowedFileSize/(1024*1024) +" MB.";
        } else if(!regex.test(fileData.files.item(0).name.toLowerCase())) {
            txt = "Please upload files having extensions: " + allowedFiles.join(', ') + " only";
        } else {            
            $("#loading").show();
            $("#btctImport").prop("disabled", true);
           formData.append('file',fileData.files.item(0));
           formdata = formData.append('file','clinical_trial');
           
           var ext = fileData.files.item(0).name.substr(fileData.files.item(0).name.lastIndexOf('.') + 1);
           if (ext == "pdf" || ext == "doc" || ext == "docx") {
            var fcall = "importanduploadtos3";
            } 
            if (ext == "csv" || ext == "xls" || ext == "xlsx") {
                var fcall = "readcommanImportFile";
                }
            $.ajax({
                
                url:'/data/'+fcall+'/'+trackerId+'/'+formId, // point to server-side PHP script 
                dataType: 'json',  // what to expect back from the PHP script, if anything
                cache: false,
                contentType: false,
                processData: false,
                data: formData,                     
                type: 'post',
                success: function(data) {
                    //alert(data);
                    $("#loading").hide();
                    $("#btctImport").prop("disabled", false);
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

$("#btothersourcesImport").click(function () {
    var fileData = document.getElementById("importCsvFile");
    var txt = "";
    var allowedFiles = [".csv", ".xls", ".xlsx", ".pdf", ".doc", ".docx"];
    var regex = new RegExp("([a-zA-Z0-9\s_\\.\-:])+(" + allowedFiles.join('|') + ")$");
    var formData = new FormData();
    if ('files' in fileData) {
        if (fileData.files.length == 0) {
           txt = "Please select a file.";
        } else if(fileData.files.item(0).size >= allowedFileSize) { 
            txt = "Please upload file of max size "+allowedFileSize/(1024*1024) +" MB.";
        } else if(!regex.test(fileData.files.item(0).name.toLowerCase())) {
            txt = "Please upload files having extensions: " + allowedFiles.join(', ') + " only";
        } else {            
            $("#loading").show();
            $("#btothersourcesImport").prop("disabled", true);
           formData.append('file',fileData.files.item(0));
           formdata = formData.append('file','other_sources');
           
           var ext = fileData.files.item(0).name.substr(fileData.files.item(0).name.lastIndexOf('.') + 1);
           if (ext == "pdf" || ext == "doc" || ext == "docx") {
            var fcall = "importanduploadtos3";
            } 
            if (ext == "csv" || ext == "xls" || ext == "xlsx") {
                var fcall = "readcommanImportFile";
                }
            $.ajax({
                
                url:'/data/'+fcall+'/'+trackerId+'/'+formId, // point to server-side PHP script 
                dataType: 'json',  // what to expect back from the PHP script, if anything
                cache: false,
                contentType: false,
                processData: false,
                data: formData,                     
                type: 'post',
                success: function(data) {
                    //alert(data);
                    $("#loading").hide();
                    $("#btothersourcesImport").prop("disabled", false);
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