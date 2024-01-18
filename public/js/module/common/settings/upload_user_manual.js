$(".custom-file-input").on("change", function() {
  var fileName = $(this).val().split("\\").pop();
  $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
});
/**
* Created by saritatewari on 5/11/15.
*/

$("#uploadform").validate();

$(document).ready(function()
{   
    window.setInterval(function(){
      $('#alert').removeClass('alert-success').hide().html('');
    }, 3000);
    
    $(".uniform_on").uniform();

    $(".filename,.action").css('box-sizing','unset')
    $( '#image-file' ).change(function() {
        $("#status").html('');
        var ext = $('#image-file').val().split('.').pop().toLowerCase();
        if($.inArray(ext, ["pdf", "doc", "docx"]) == -1) {
            $("#status").html(messageJSON.MSG_FIELD_FORMAT.replace('#fieldName','User Manual').replace("#format", ".pdf, .doc, .docx"));
        }
    });
    $("#submitform").click(function()
    {
        var count = 0;
        $("#status").html('');
        var ext = $('#image-file').val().split('.').pop().toLowerCase();
        if($('#image-file').val()==''){
            $("#status").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','User Manual'));
            count++;
        } else if ($.inArray(ext, ["pdf", "doc", "docx"]) == -1) {
            $("#status").html(messageJSON.MSG_FIELD_FORMAT.replace('#fieldName','User Manual').replace("#format", ".pdf, .doc, .docx"));
            count++;
        }
        
        if ($('#reason').val()=='') {
            $("#forReason").html(messageJSON.MSG_FIELD_EMPTY.replace('#fieldName','Reason for change'));
            count++; 
        }
        
        if (count == 0) {
            $('#upload-form').submit();    
        }
        return false;
    });
});
$('#cancelButton').on('click',function() {
        window.location.href = cancelPath;
    });