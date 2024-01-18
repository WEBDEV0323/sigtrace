
function onBtImport(trackerId,formId,allowedFileSize)
{
    var fileData = document.getElementById("importCsvFile");
    var txt = "";
    var formData = new FormData();
    if ('files' in fileData) {
        if (fileData.files.length == 0) {
           txt = "Please select a file.";
        } else if(fileData.files.item(0).size >= allowedFileSize) {
            txt = "Please upload file of max size "+allowedFileSize/(1024*1024) +" MB.";
        } else if(fileData.files.item(0).name.toLowerCase().lastIndexOf(".csv") == -1) {
            txt = "Please upload only .csv file";
        } else {
            $("#btnImport").prop("disabled", true);
            formData.append('file',fileData.files.item(0));
            $.ajax({
                url:'/import/getFileFromClient/'+trackerId+'/'+formId, // point to server-side PHP script 
                dataType: 'json',  // what to expect back from the PHP script, if anything
                cache: false,
                contentType: false,
                processData: false,
                data: formData,                     
                type: 'post',
                success: function(respJson) {
                    var text='';
                    if(respJson.result!=0) {
                        text=respJson.message;
//                        $("#alert_csv").html(text);
//                        $('#alert_csv').addClass('alert alert-danger');
                        $("#ImportCsvFileMsg").html(text);
                        
                    } else {
                        text="Out of "+respJson.totalRecord + " Records, "+respJson.duplicateRecords+" are duplicate, "+respJson.uniqueRecords+" imported successfully.";
                        $("#alert_csv").html(text);
                        $('#alert_csv').addClass('alert alert-success');
                        window.setTimeout(function () {
                            $(".alert").fadeTo(500, 0).slideUp(500, function () {
                                $('#alert_csv').removeClass('alert');
                                $(this).remove();
                                location.reload();
                            });
                        }, 5000);
                        $('#alert_csv').fadeIn('slow');
                        $('#importCsvFile').val('');
                        $('#importCsvModal').modal('hide');
                    }
                }
            });
        }
    }
    $("#ImportCsvFileMsg").html(txt);
}

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

function clearErrorMessages() {
    $("#ImportCsvFileMsg").html('');
    $('#importCsvFile').val('');
    $('.custom-file-label').html('Choose file');
}