
function addRow(tableID){
    var table=document.getElementById(tableID);
    var rowCount=table.rows.length;
    var row=table.insertRow(rowCount);
    var colCount=table.rows[0].cells.length;
    //alert(rowCount+">>>"+row+">>>"+colCount);
    for(var i=0;i<colCount;i++){
        var newcell=row.insertCell(i);
        newcell.innerHTML=table.rows[1].cells[i].innerHTML;
        //alert(newcell.innerHTML);
        switch(newcell.childNodes[0].type){
            case"text":newcell.childNodes[0].value="";
                break;
            case"checkbox":newcell.childNodes[0].checked=false;
                break;
            case"select-one":newcell.childNodes[0].selectedIndex=0;
                break;
        }
    }
}


function deleteRow(tableID){
    try{
        var table=document.getElementById(tableID);
        var rowCount=table.rows.length;
        for(var i=0;i<rowCount;i++){
            var row=table.rows[i];
            var chkbox=row.cells[0].childNodes[0];
            if(null!=chkbox&&true==chkbox.checked){
                if(rowCount<=1){
                    alert("Cannot delete all the rows.");
                    break;
                }
    table.deleteRow(i);rowCount--;i--;
            }
        }
    }catch(e){
        alert(e);
    }
}
function savedata(tableID,tracker_id,form_id){
    $("#msg").hide();

    var elem = document.getElementsByClassName("check_allcocatio_rep");
    var check_allcocatio_rep = [];
    for (var i = 0; i < elem.length; ++i) {
        if (typeof elem[i].value !== "undefined") {
            check_allcocatio_rep.push(elem[i].checked);
        }
    }

    var elem = document.getElementsByClassName("arisgNo_allcocatio_rep");
    var arisgNo_allcocatio_rep = [];
    for (var i = 0; i < elem.length; ++i) {
        if (typeof elem[i].value !== "undefined") {
            arisgNo_allcocatio_rep.push(elem[i].value);
        }
    }

    var elem = document.getElementsByClassName("select_uName_allocation_rep");
    var select_uName_allocation_rep = [];
    for (var i = 0; i < elem.length; ++i) {
        if (typeof elem[i].value !== "undefined") {
            select_uName_allocation_rep.push(elem[i].value);
        }
    }
    var data = {
        arisgNo : arisgNo_allcocatio_rep,
        select_uName : select_uName_allocation_rep,
        check_allcocation : check_allcocatio_rep,
        tracker_id:tracker_id,
        form_id:form_id

    };
    $.ajax({
        url:'/report/saveUsersForForms',
        data:data,
        method:'post',
        success:function(data) {
            if(data!='')
            {
                $("#msg").show();
            }
        }

    });
    //console.log(table.rows);
}

function savedataforde(tableID,tracker_id,form_id,flag){
    $("#msg").hide();

    var elem = document.getElementsByClassName("check_allcocatio_rep");
    var check_allcocatio_rep = [];
    for (var i = 0; i < elem.length; ++i) {
        if (typeof elem[i].value !== "undefined") {
            check_allcocatio_rep.push(elem[i].checked);
        }
    }

    var elem = document.getElementsByClassName("arisgNo_allcocatio_rep");
    var arisgNo_allcocatio_rep = [];
    for (var i = 0; i < elem.length; ++i) {
        if (typeof elem[i].value !== "undefined") {
            arisgNo_allcocatio_rep.push(elem[i].value);
        }
    }

    var elem = document.getElementsByClassName("select_uName_allocation_rep");
    var select_uName_allocation_rep = [];
    for (var i = 0; i < elem.length; ++i) {
        if (typeof elem[i].value !== "undefined") {
            select_uName_allocation_rep.push(elem[i].value);
        }
    }
    var data = {
        aerno : arisgNo_allcocatio_rep,
        select_uName : select_uName_allocation_rep,
        check_allcocation : check_allcocatio_rep,
        tracker_id:tracker_id,
        form_id:form_id,
        flag:flag

    };
    $.ajax({
        url:'/report/saveUsersForDEAllocation',
        data:data,
        method:'post',
        success:function(data) {
            if(data!='')
            {
                $("#msg").show();
            }
        }

    });
    //console.log(table.rows);
}



function deleteRecord(id,trackerId,formId){
    if( confirm("Are you sure you want to delete this Form?"))
    {
        //            var id=$(this).closest('tr').attr("id");
        $.ajax({
            url: "/tracker/deleterecord",
            type:'post',
            dataType:'json',
            data:{id:id, tracker_id:trackerId,form_id:formId},
            success:function(data1) {
                if(data1=='deleted')
                {
                    window.location.assign('/tracker/form/'+trackerId+'/'+formId);
                }
            }
        });

        return false;
    }

}




