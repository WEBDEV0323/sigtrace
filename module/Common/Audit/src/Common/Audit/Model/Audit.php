<?php
namespace Common\Audit\Model;

use Zend\Mvc\Controller\AbstractActionController;

class Audit extends AbstractActionController
{
    public function saveToLog($id = "", $username = "", $action = "", $originalValue = "", $newValue = "", $reasonForChange = "", $status = "",$customer = 0)
    { 
        if (count(func_get_args()) > 0) {
            $dataArray = array();
            $dataArray['id'] = $id;
            $dataArray['username'] = $username;
            $dataArray['action'] = $action;
            $dataArray['originalValue'] = $originalValue;
            $dataArray['newValue'] = $newValue;
            $dataArray['reasonForChange'] = $reasonForChange;
            $dataArray['status'] = $status;
            $dataArray['customer'] = $customer;
            $data_json = json_encode($dataArray);

            $rest = curl_init();
            $headers = array(
              "Content-Type: application/json",
            );
            $url='';

            if (filter_input(INPUT_SERVER, 'SERVER_NAME') == 'localhost') {
                $url="http://localhost:8060";
            } else {
                $url="http://".filter_input(INPUT_SERVER, 'SERVER_ADDR').":8060";
            }
            curl_setopt($rest, CURLOPT_URL, $url);
            curl_setopt($rest, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($rest, CURLOPT_POST, 1);
            curl_setopt($rest, CURLOPT_POSTFIELDS, $data_json);

            curl_setopt($rest, CURLOPT_SSL_VERIFYPEER, false);

            curl_setopt($rest, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($rest, CURLOPT_RETURNTRANSFER, true);
            curl_exec($rest);
            curl_close($rest);
        } else {
            return "no parameters";
        }
    }
}
