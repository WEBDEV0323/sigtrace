/**
 * Created by saritatewari on 5/11/15.
 */

    $(document).ready(function()
    {
        $("#logincheck").submit(function()
        {
            var u_name = $("#u_name").val();
            var password = $("#upw").val();
            $("#status").html('');
            $("#statuspwd").html('');
            if(u_name.length ===0)
            {
                $("#status").html('<font color="#cc0000">Username Can not be blank</font>');
            }
            else if(password.length===0)
            {
                $("#statuspwd").html('<font color="#cc0000">Password can not be blank.</font>');
            }
            else
            {
                $("#statuspwd").html('&nbsp;Processing in.');
                $.post("logincheck",
                    {   u_name : u_name,
                        upw : password,
                        act : 'weblogin'
                    },
                   function(respJson){
		       var flag=0;
                       var resp =JSON.parse(respJson);
                       //console.log(resp);
                       if(resp.u_name !=undefined)
                       {
                           $("#status").html('<font color="#cc0000">Username Can not be blank</font>');
                            flag=1;
                       }
                       if(resp.upw !=undefined)
                       {
                           $("#statuspwd").html('<font color="#cc0000">Password can not be blank.</font>');
                           flag=1;
                       }
                       if(flag==0) {
                           var responseCode = resp.statusCode;
                           var errMessage = resp.result;
                           if (responseCode == 200) {
                               window.location.replace("/dashboard");
                           }
                           else {
                               $("#statuserror").html('<font color="#cc0000">Enter Correct Username & Password</font>');
                           }
                       }
                });
            }
            return false;
        });
    });
