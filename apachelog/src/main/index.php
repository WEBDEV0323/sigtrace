<?php
ini_set('allow_url_fopen', true);
header("Content-Type:application/json");;
include('savelog.php');
$entityBody = file_get_contents('php://input');

$xml=simplexml_load_file("config.xml") or die("Error: Cannot create object");
$data=$xml->appender->param->attributes();
$filename = $data['value'];
$data=json_decode($entityBody,true);

$today = gmdate('Y-M-d H:i:s \U\T\C', time()); 
$logdata= '"'.$data['id'].'";"'.$data['username'].'";"'.$data['action'].'";"'.$data['originalValue'].'";"'.$data['newValue'].'";"'.$data['reasonForChange'].'";"'.$data['status'].'";"'.$data['customer'].'";"'.$today.'"';
$foo = new Foo();
$foo->go($logdata);
deliver_response(200,"changed",$entityBody);

function deliver_response($status,$status_message,$entityBody){
    header("HTTP/1.1 $status $status_message");
    $response['status']=$status;
    $response['status_message']=$status_message;
    $response['data']=$entityBody;
    $json_response=json_encode($response);
}
