<?php
namespace SigTRACE\Casedata\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;
use PhpOffice\PhpSpreadsheet\IOFactory;
class IndexController extends AbstractActionController
{
    protected $_casedataMapper;
    protected $_auditMapper;
    protected $_dbMapper;
    protected $_serviceMapper;
    protected $_adminMapper;
    protected $_roleService;
    protected $_mailService;
    public function getRoleService()
    {
        if (!$this->_roleService) {
            $sm = $this->getServiceLocator();
            $this->_roleService = $sm->get('Common\Role\Model\Role');
        }
        return $this->_roleService;
    }
    public function getMailService() 
    {
        if (!$this->_mailService) {
            $sm = $this->getServiceLocator();
            $this->_mailService = $sm->get('Common\Notification\Controller\Email');
        }
        return $this->_mailService;
    }
    public function getCasedataHelperService() 
    {
        if (!$this->_adminMapper) {
            $sm = $this->getServiceLocator();
            $this->_adminMapper = $sm->get('Casedata\Helper\CasedataHelper');
        }
        return $this->_adminMapper;
    }
    public function getService() 
    {
        if (!$this->_serviceMapper) {
            $sm = $this->getServiceLocator();
            $this->_serviceMapper = $sm->get('Casedata\Service\CasedataService');
        }
        return $this->_serviceMapper;
    }
    public function getAuditService() 
    {
        if (!$this->_auditMapper) {
            $sm = $this->getServiceLocator();
            $this->_auditMapper = $sm->get('Common\Audit\Service');
        }
        return $this->_auditMapper;
    }
    public function getCasedataService() 
    {
        if (!$this->_casedataMapper) {
            $sm = $this->getServiceLocator();
            $this->_casedataMapper = $sm->get('Casedata\Model\Casedata');
        }
        return $this->_casedataMapper;
    }
    public function getWorkflowService()
    {
        if (!$this->_WorkflowService) {
            $sm = $this->getServiceLocator();
            $this->_WorkflowService = $sm->get('Common\Workflow\Service');
        }
        return $this->_WorkflowService;
    }
    public function getDbService() 
    {
        if (!$this->_dbMapper) {
            $sm = $this->getServiceLocator();
            $this->_dbMapper = $sm->get('Casedata\Model\Db');
        }
        return $this->_dbMapper;
    }
    public function indexAction() 
    {
        set_time_limit(0);
        $view = new ViewModel();
        return $view;
    }
    public function importAction() 
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId != 0 && in_array($trackerId, $trackerIds)) {
                $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id' => $formId));
            } else {
                return $this->redirect()->toRoute('tracker');
            }
        }
        $view = new ViewModel();
        $view->setVariables(array('trackerId' => $trackerId, 'formId' => $formId));
        return $view;
    }
    public function parseCsvFile($csvfile)
    {
        $result = array();
        $csv = Array();
        $rowcount = 0;
        $header_colcount = 0;
        $rowLine = (int)$rowLine;
        if (($handle = fopen($csvfile, "r")) !== false) {
            $max_line_length = defined('MAX_LINE_LENGTH') ? MAX_LINE_LENGTH : 10000;
            $head = array();
            while (($row = fgetcsv($handle, $max_line_length)) !== false) {
                if ($rowcount == $rowLine) {
                    $header_colcount = count($row);
                    foreach ($row as $h) {
                        // $head[] = trim($h);
                        $h = preg_replace('/[^A-Za-z0-9 ()\-+\/]/', ' ', $h);
                        $head[] = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h));
                    }
                }
                if ($rowcount > $rowLine) {
                    $row_colcount = count($row);
                    if ($row_colcount == $header_colcount) {
                        array_walk_recursive(
                            $row, function (&$value) {
                                $value = trim(strtolower($value)) == 'non serious' ? 'Not Serious' : trim($value);
                            }
                        );
                        $entry = array_combine($head, $row);
                        $csv[] = $entry;
                    } else {
                        error_log("csvreader: Invalid number of columns at line " . ($rowcount + 2) . " (row " . ($rowcount + 1) . "). Expected=$header_colcount Got=$row_colcount");
                        return null;
                    }
                }
                $rowcount++;
            }
            fclose($handle);
        } else {
            error_log("csvreader: Could not read CSV \"$csvfile\"");
            return null;
        }
        if ($type == 'ermr') {
            $result['list'] = $csv;
            return $result;
        } else {
            return $csv;
        }
    }
    public function get2DArrayFromCsv($file, $delimiter)
    {
        $data2DArray = array();
        if (($handle = fopen($file, "r")) !== false) {
            $i = 0;
            while (($lineArray = fgetcsv($handle, 0, $delimiter)) !== false) {
                for ($j = 0;$j < count($lineArray);$j++) {
                    $data2DArray[$i][$j] = trim($lineArray[$j]);
                }
                $i++;
            }
            fclose($handle);
        }
        return $data2DArray;
        /*$data2DArray = array();
        foreach (file($file) as $line) {
            $data2DArray[] = str_getcsv($line);
        }
        return $data2DArray;*/
    }
    public function validateAction()
    {
        $response = $this->getResponse();
        set_time_limit(0);
        error_reporting(0);
        $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
        $fields = $this->getDbService()->fetch(array('t' => 'import_settings'), array('ims_form_id' => $post['formId']), array('ims_form_id', 'ims_file_type', 'ims_fields'));
        $fieldsArray = $DbFieldsArray = $msg = array();
        if (!empty($fields)) {
            $fieldsArray = unserialize($fields[0]['ims_fields']);
            foreach ($fieldsArray as $key => $value) {
                $DbFieldName = $this->getDbService()->fetch(array('t' => 'field'), array('field_id' => explode('-', $key) [1]), array('field_name', 'label'));
                $DbFieldsArray[] = trim(ucfirst($DbFieldName[0]['label']));
            }
            if ($fields[0]['ims_file_type'] == '.csv') {
                if (!empty(array_filter($_FILES['file']['name']))) {
                    if (count(array_filter($_FILES['file']['name'])) == count(array_unique(array_filter($_FILES['file']['name'])))) {
                        foreach ($_FILES['file']['name'] as $key => $value) {
                            if ($value != '') {
                                $file = pathinfo($value);
                                $changedFileName = $file['filename'] . '_' . time() . '.' . $file['extension'];
                                if ($file['extension'] == "csv") {
                                    $ssPath = files . 'import/temp';
                                    if (!file_exists($ssPath)) {
                                        mkdir($ssPath, 0777, true);
                                    }
                                    if ($_FILES["file"]["tmp_name"][$key] != '') {
                                        $productName = $this->getDbService()->fetch(array('s' => 'product'), array('product_id' => $post['product'][$key]), array('product_name'));
                                        $productNameVal = '';
                                        if (!empty($productName)) {
                                            $productNameVal = $productName[0]['product_name'];
                                        }
                                        move_uploaded_file($_FILES["file"]["tmp_name"][$key], $ssPath . "/" . $changedFileName);
                                        $resultArray = $this->parseCsvFile($ssPath . "/" . $changedFileName);
                                        $NVLcsv = array();
                                        foreach ($resultArray as $value) {
                                            $res = array();
                                            foreach ($value as $k => $v) {
                                                $res[] = trim($k);
                                                if (strtolower(trim($k)) == "nvl") {
                                                    $NVLcsv[] = trim($v);
                                                }
                                            }
                                        }
                                        if (count(array_unique($NVLcsv)) == 1 && in_array($productNameVal, $NVLcsv)) {
                                            if ($DbFieldsArray != $res) {
                                                $msg['fields'] = 'Fields mismatch!!!';
                                            }
                                        } else {
                                            $msg['products'] = 'Products mismatch!!!';
                                        }
                                        unlink($ssPath . "/" . $changedFileName);
                                        break;
                                    } else {
                                        $msg['info'] = 'Plese upload correct csv file';
                                        break;
                                    }
                                } else if ($file['extension'] == "xls") {
                                    $msg['info'] = 'Plese upload CSV type file';
                                    break;
                                } else {
                                    $msg['info'] = 'Plese upload valid type file';
                                    break;
                                }
                            }
                        }
                    } else {
                        $msg['info'] = 'Duplicate file names';
                    }
                }
            } else if ($fields[0]['ims_file_type'] == '.xls') {
                if (!empty(array_filter($_FILES['file']['name']))) {
                    if (count(array_filter($_FILES['file']['name'])) == count(array_unique(array_filter($_FILES['file']['name'])))) {
                        foreach ($_FILES['file']['name'] as $key => $value) {
                            if ($value != '') {
                                $file = pathinfo($value);
                                $changedFileName = $file['filename'] . '_' . time() . '.' . $file['extension'];
                                if ($file['extension'] == "xls" || $file['extension'] == "xlsx") {
                                    $ssPath = files . 'import/temp';
                                    if (!file_exists($ssPath)) {
                                        mkdir($ssPath, 0777, true);
                                    }
                                    if ($_FILES["file"]["tmp_name"][$key] != '') {
                                        move_uploaded_file($_FILES["file"]["tmp_name"][$key], $ssPath . "/" . $changedFileName);
                                        $data = new \Spreadsheet_Excel_Reader();
                                        $data->setOutputEncoding('CP1251');
                                        $data->read($ssPath . "/" . $changedFileName);
                                        $resList = $data->sheets[0]['cells'][1];
                                        unlink($ssPath . "/" . $changedFileName);
                                        if (!empty(array_diff($DbFieldsArray, $resList))) {
                                            $msg[$key] = 'Fields mismatch!!!';
                                            break;
                                        }
                                        unlink($ssPath . "/" . $changedFileName);
                                    } else {
                                        $msg['info'] = 'Plese upload correct Excel file';
                                        break;
                                    }
                                } else {
                                    $msg['info'] = 'Plese upload xls type file';
                                    break;
                                }
                            }
                        }
                    } else {
                        $msg['info'] = 'Duplicate file names';
                    }
                }
            } else {
                $msg['info'] = 'No fields set';
            }
            print_r(json_encode($msg));
            return $response;
        }
    }
    public function downloadAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $container = $session->getSession("user");
        $userDetails = $container->user_details;
        $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
        $fields = $this->getDbService()->fetch(array('t' => 'import_settings'), array('ims_form_id' => $formId), array('ims_form_id', 'ims_file_type', 'ims_fields'));
        if (count($fields) > 0) {
            if ($fields[0]['ims_file_type'] == '.csv') {
                $output = $head = $data = '';
                $fieldNames = unserialize($fields[0]['ims_fields']);
                $i = 0;
                if (count($fieldNames) > 0) {
                    foreach ($fieldNames as $key => $value) {
                        $DbFieldName = $this->getDbService()->fetch(array('t' => 'field'), array('field_id' => explode('-', $key) [1]), array('field_name', 'label'));
                        if ($head == '') {
                            $head.= ucfirst($DbFieldName[0]['label']);
                        } else {
                            $head.= ',' . ucfirst($DbFieldName[0]['label']);
                        }
                    }
                    $head.= "\r\n";
                    $output = $head;
                    //$this->getAuditService()->auditTrail($userDetails['u_name'],$userDetails['group_name'],'Download','Sample Import File',0,$trackerId,$_SERVER['REMOTE_ADDR'],'','','Success');
                    $filename = "form_" . date("d-m-Y_H-i", time());
                    header("Content-type: application/vnd.ms-excel");
                    header("Content-disposition: csv" . date("Y-m-d") . ".csv");
                    header("Content-disposition: filename=" . $filename . ".csv");
                    print $output;
                    exit;
                }
            } else if ($fields[0]['ims_file_type'] == '.xls') {
                echo "Please change type from xls to csv";
            }
        } else {
            echo "Please <a href='/settings/import/$trackerId'>click here</a> to set some default fields to import";
        }
        exit;
    }
    public function readCsvData($file, $delimiter)
    {
        $data2DArray = array();
        if (($handle = fopen($file, "r")) !== false) {
            $i = 0;
            while (($lineArray = fgetcsv($handle, 0, $delimiter)) !== false) {
                for ($j = 0;$j < count($lineArray);$j++) {
                    $data2DArray[$i][$j] = trim(str_replace(array("\n\r", "\r\n", "\r", "\n", "\\r", "\\n", "\\r\\n", "\\n\\r"), "", $lineArray[$j]));
                }
                $i++;
            }
            fclose($handle);
        }
        return $data2DArray;
    }
    public function readImportFileAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $request = $this->getRequest();
        $response = $this->getResponse();
        $resultArray = array();
        if (!isset($userContainer->u_id)) {
            $resultArray['result'] = 2;
            $response->setContent(json_encode($resultArray));
            return $response;
        }
        $userDetails = $userContainer->user_details;
        $configContainer = $session->getSession('config');
        $allowedFileSize = $configContainer->config['importFileSize'];
        $allowedFileSize = (int)$allowedFileSize;
        $dateFormat = $configContainer->config['dateFormat'];
        $trackerId = $this->params()->fromRoute('trackerId', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $formId = $this->params()->fromRoute('formId', 0);
        $message = '';
        $flag = 0;
        if ($request->isPost()) {
            $post = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());
            $tableName = "form_" . $trackerId . "_" . $formId;
            $fileName = $post['file']['name'];
            $fileSize = $post['file']['size'];
            $fileSize = (int)$fileSize;
            $extension = substr($fileName, strrpos($fileName, '.') + 1);
            $tempFileName = isset($post['file']['tmp_name']) ? $post['file']['tmp_name'] : '';
            $changedFileName = $fileName . '_' . time() . '.' . $extension;
            $columns = array();
            $datecolumns = array();
            $headers = array();
            $dateHeaders = array();
            $dataObject = array();
            $dateObject = array();
            $result = array();
            $ignoredIngredients = array();
            $uniqueKey = '';
            $isAlert = 0;
            $auditLog = '';
            $configValue = $this->getCasedataService()->getQuantitativeSettingsConfigs($formId);
            $asValue = 'active_ingredient';
            $nvlValue = 'nvl';
            $importType = 'ai';
            $suspectedProducts = 'Suspect products';
            $primaryIngredients = 'Primary Active Ingredient';
            $headerRow = 0;
            $changedProductIngredients = array();
            if (count($configValue) > 0) {
                $asValue = isset($configValue[0]['active_ingredient']) ? $configValue[0]['active_ingredient'] : '';
                $nvlValue = isset($configValue[0]['nvl']) ? $configValue[0]['nvl'] : '';
                $importType = isset($configValue[0]['importby']) ? $configValue[0]['importby'] : 'ai';
                $suspectedProducts = isset($configValue[0]['product']) ? $configValue[0]['product'] : 'Suspect products';
                $primaryIngredients = isset($configValue[0]['primaryingredient']) ? $configValue[0]['primaryingredient'] : 'Primary Active Ingredient';
            }
            try {
                if ($tempFileName != '' && $fileSize <= $allowedFileSize) {
                    $importFieldMapping = $this->getCasedataHelperService()->getImportFieldMappingData($tableName);
                    foreach ($importFieldMapping as $mapping) {
                        $tempArray = array();
                        $tempArray['data'] = $mapping['source_field_name'];
                        if ($mapping['isDate'] == 'yes') {
                            $tempArray['type'] = 'date';
                            $tempArray['dateFormat'] = $dateFormat;
                            $tempArray['correctFormat'] = true;
                        } else {
                            $tempArray['type'] = 'text';
                        }
                        if ($mapping['isEditable'] == 'no') {
                            $tempArray['readOnly'] = true;
                        }
                        if ($mapping['isDisplay'] == 'yes') {
                            $datecolumns[] = $tempArray;
                            $dateHeaders[] = $mapping['source_field_name'];
                        }
                        $columns[] = $tempArray;
                        $headers[] = $mapping['source_field_name'];
                        if ($mapping['isUnique'] == 'yes') {
                            $uniqueKey = $mapping['source_field_name'];
                        }
                    }
                    if ($extension == "csv" || $extension == "xls" || $extension == "xlsx") {
                        if ($extension == "csv") {
                            if (count($configValue) > 0) {
                                $headerRow = isset($configValue[0]['csvheaderline']) ? $configValue[0]['csvheaderline'] : 0;
                            }
                            $resList = $this->parseCsvFile($tempFileName, $headerRow);
                        } else if ($extension == "xls" || $extension == "xlsx") {
                            if (count($configValue) > 0) {
                                $headerRow = isset($configValue[0]['xlsheaderline']) ? $configValue[0]['xlsheaderline'] : 0;
                            }
                            $resList = $this->parseXlsFile($tempFileName, $headerRow);
                            
                        }
                        if (count($resList) > 0) {
                            foreach ($resList as $list) {
                                $tmpArray = array();
                                foreach ($headers as $header) {
                                    if (array_key_exists($header, $list)) {
                                        $tmpArray[$header] = $list[$header];
                                    } else {
                                        if (!in_array($header, $dateHeaders) || $header == $uniqueKey) {
                                            $flag = 1;
                                            $message = "Required " . $header . " is missing from the uploaded file.";
                                            $response->setContent(\Zend\Json\Json::encode(array(0, $message)));
                                            return $response;
                                        } else if (in_array($header, $dateHeaders) && $header != $uniqueKey) {
                                            $tmpArray[$header] = '';
                                        }
                                    }
                                }
                                if ($importType == 'p') {
                                    $arrActiveSubstance = $this->getCasedataService()->getActiveIngradientFromProduct($list[$uniqueKey], $trackerId);
                                    if (count($arrActiveSubstance) > 0 && $arrActiveSubstance[0]['as_name'] != $tmpArray[$primaryIngredients]) {
                                        $changedProductIngredients[] = $tmpArray[$suspectedProducts] . ' Active Substances changed from ' . $tmpArray[$primaryIngredients] . ' to ' . $arrActiveSubstance[0]['as_name'];
                                        $tmpArray[$primaryIngredients] = $arrActiveSubstance[0]['as_name'];
                                        $list[$primaryIngredients] = $arrActiveSubstance[0]['as_name'];
                                    }
                                } else {
                                    $arrActiveSubstance = $this->getCasedataService()->getActiveIngradientByName($list[$uniqueKey]);
                                }
                                if (count($arrActiveSubstance) > 0) {
                                    // $dataObject[] = $tmpArray;
                                    $tmpArray = array();
                                    if (!in_array($list[$uniqueKey], array_column($dateObject, $uniqueKey))) {
                                        foreach ($dateHeaders as $header) {
                                            if (array_key_exists($header, $list)) {
                                                $tmpArray[$header] = $list[$header];
                                            } else {
                                                $tmpArray[$header] = '';
                                            }
                                        }
                                        $dateObject[] = $tmpArray;
                                    }
                                } else {
                                    $ignoredIngredients[] = $list[$uniqueKey];
                                }
                            }
                            if (count($ignoredIngredients) > 0 && $importType == 'p') {
                                $ignoredIngredients = array_unique($ignoredIngredients);
                                $message = "Following Suspected Products are not present in the system and are ignored: " . implode(", ", $ignoredIngredients);
                                $isAlert = 1;
                            } else if (count($ignoredIngredients) > 0 && $importType == 'ai') {
                                $ignoredIngredients = array_unique($ignoredIngredients);
                                $message = "Following Active Substances are not present in the system and are ignored: " . implode(", ", $ignoredIngredients);
                                $isAlert = 1;
                            }
                            if (count($changedProductIngredients) > 0) {
                                $changedProductIngredients = array_unique($changedProductIngredients);
                                $message.= ($message != '') ? ' ## ' . implode(" ## ", $changedProductIngredients) : implode(" ## ", $changedProductIngredients);
                            }
                            if (count($dateObject) > 0) {
                                $response->setContent(\Zend\Json\Json::encode(array(1, $headers, $dataObject, $columns, $dateHeaders, $dateObject, $datecolumns, $message, $isAlert)));
                                return $response;
                            } else {
                                $flag = 1;
                                $message = ($message != "") ? $message : "empty file uploaded.";
                                $response->setContent(\Zend\Json\Json::encode(array(0, $message, $isAlert)));
                                return $response;
                            }
                        } else {
                            $message = "Empty file uploaded";
                            $resultArray['result'] = 0;
                            $resultArray['message'] = $message;
                            $resultArray['isalert'] = $isAlert;
                            $response->setContent(\Zend\Json\Json::encode(array(0, $message, $isAlert)));
                            return $response;
                        }
                    } else {
                        $message = "Invalid file type uploaded";
                        $resultArray['result'] = 0;
                        $resultArray['message'] = $message;
                        $resultArray['isalert'] = $isAlert;
                        $response->setContent(\Zend\Json\Json::encode(array(0, $message, $isAlert)));
                        return $response;
                    }
                } else {
                    $message = "max upload size is $allowedFileSize Byte";
                    $resultArray['result'] = 0;
                    $resultArray['message'] = $message;
                    $resultArray['isalert'] = $isAlert;
                    $response->setContent(\Zend\Json\Json::encode(array(0, $message, $isAlert)));
                    return $response;
                }
            }
            catch(Exception $ex) {
                $message = $e->getMessage();
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $response->setContent($message);
                return $response;
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $response->setContent('Method not allowed');
            return $response;
        }
    }
    public function processImportFileAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $request = $this->getRequest();
        $response = $this->getResponse();
        $resultArray = array();
        if (!isset($userContainer->u_id)) {
            $resultArray['result'] = 2;
            $response->setContent(json_encode($resultArray));
            return $response;
        }
        $duplicateRecords = 0;
        $userDetails = $userContainer->user_details;
        $configContainer = $session->getSession('config');
        $trackerId = $this->params()->fromRoute('trackerId', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $formId = $this->params()->fromRoute('formId', 0);
        $config = $this->getServiceLocator()->get('config');
        $groupId = $config['group']['group_id'];
        $message = '';
        $flag = 0;
        $InserCsvData = '';
        $activeSubstances = array();
        $resultArray = array();
        $datesArray = array();
        $uniqueKey = '';
        $keyValue = 0;
        $i = 0;
        $dataArray = array();
        $ptNames = array();
        $configValue = $this->getCasedataService()->getQuantitativeSettingsConfigs($formId);
        $ptValue = '';
        $socValue = '';
        $ssValue = '';
        $headerRow = 0;
        $importType = 'ai';
        $data = array();
        $asValue = 'active_ingredient';
        $nvlValue = 'nvl';
        $suspectedProducts = 'Suspect products';
        $primaryIngredients = 'Primary Active Ingredient';
        $sourceCustomer = 'import_source';
        if (count($configValue) > 0) {
            $nvlValue = isset($configValue[0]['nvl']) ? $configValue[0]['nvl'] : '';
            $importType = isset($configValue[0]['importby']) ? $configValue[0]['importby'] : 'ai';
            $suspectedProducts = isset($configValue[0]['product']) ? $configValue[0]['product'] : 'Suspect products';
            $primaryIngredients = isset($configValue[0]['primaryingredient']) ? $configValue[0]['primaryingredient'] : 'Primary Active Ingredient';
            $socValue = isset($configValue[0]['socname']) ? $configValue[0]['socname'] : '';
            $ptValue = isset($configValue[0]['ptname']) ? $configValue[0]['ptname'] : '';
            $ssValue = isset($configValue[0]['seriousnessevent']) ? $configValue[0]['seriousnessevent'] : '';
            $asValue = isset($configValue[0]['active_ingredient']) ? $configValue[0]['active_ingredient'] : '';
            $sourceCustomer = isset($configValue[0]['sourcecustomer']) ? $configValue[0]['sourcecustomer'] : 'import_source';
        }
        if ($request->isPost()) {
            $post = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());
            $fileName = $post['file']['name'];
            $fileSize = $post['file']['size'];
            $fileSize = (int)$fileSize;
            $extension = substr($fileName, strrpos($fileName, '.') + 1);
            $tempFileName = isset($post['file']['tmp_name']) ? $post['file']['tmp_name'] : '';
            if (isset($post['file']['tmp_name']) && !isset($post['datesdata'])) {
                // $data=isset($post['data'])?json_decode(urldecode($post['data'])):"";
                $data = array();
                $datesdata = isset($post['datesdata']) ? json_decode(urldecode($post['datesdata'])) : "";
                $headers = isset($post['header']) ? json_decode(urldecode($post['header'])) : "";
                $datesheaders = isset($post['datesheader']) ? json_decode(urldecode($post['datesheader'])) : "";
                $tableName = "form_" . $trackerId . "_" . $formId;
                /*if (count($headers) != count($data[0])) {
                    $response->setContent(\Zend\Json\Json::encode(false));
                    return $response;
                } */
                try {
                    $importFieldMapping = $this->getCasedataHelperService()->getImportFieldMappingData($tableName);
                    $importFieldArray = array_column($importFieldMapping, 'db_field_name', 'source_field_name');
                    $requiredFieldArray = array_column($importFieldMapping, 'isRequired', 'db_field_name');
                    $uniqueFieldArray = array_column($importFieldMapping, 'isUnique', 'source_field_name');
                    $sourceFieldArray = array_column($importFieldMapping, 'source_field_name', 'db_field_name');
                    $requiredFieldArray = array_filter(
                        $requiredFieldArray, function ($v, $k) {
                            return $v == 'yes';
                        }, ARRAY_FILTER_USE_BOTH
                    );
                    $uniqueFieldArray = array_filter(
                        $uniqueFieldArray, function ($v, $k) {
                            return $v == 'yes';
                        }, ARRAY_FILTER_USE_BOTH
                    );
                    foreach ($uniqueFieldArray as $key => $value) {
                        $uniqueKey = $key;
                    }
                }
                catch(Exception $e) {
                    $message = $e->getMessage();
                }
                $keyValue = array_search($uniqueKey, $datesheaders);
                if ($extension == "csv" || $extension == "xls" || $extension == "xlsx") {
                    if ($extension == "csv") {
                        if (count($configValue) > 0) {
                            $headerRow = isset($configValue[0]['csvheaderline']) ? $configValue[0]['csvheaderline'] : 0;
                            // echo "<pre>"; print_r($headerRow); die;

                        }
                        $resList = $this->parseCsvFile($tempFileName, $headerRow);
                    } else if ($extension == "xls" || $extension == "xlsx") {
                        if (count($configValue) > 0) {
                            $headerRow = isset($configValue[0]['xlsheaderline']) ? $configValue[0]['xlsheaderline'] : 0;
                        }
                        $resList = $this->parseXlsFile($tempFileName, $headerRow);
                    }
                }
                if (count($resList) > 0) {
                    foreach ($resList as $list) {
                        $tmpArray = array();
                        foreach ($headers as $header) {
                            if (array_key_exists($header, $list)) {
                                $tmpArray[$header] = $list[$header];
                            } else {
                                if (!in_array($header, $datesheaders) || $header == $uniqueKey) {
                                    $flag = 1;
                                    $message = "Required " . $header . " is missing from the uploaded file.";
                                    $response->setContent(\Zend\Json\Json::encode(array(0, $message)));
                                    return $response;
                                } else if (in_array($header, $datesheaders) && $header != $uniqueKey) {
                                    $tmpArray[$header] = '';
                                }
                            }
                        }
                        if ($importType == 'p') {
                            $arrActiveSubstance = $this->getCasedataService()->getActiveIngradientFromProduct($list[$uniqueKey], $trackerId);
                            if (count($arrActiveSubstance) > 0 && $arrActiveSubstance[0]['as_name'] != $tmpArray[$primaryIngredients]) {
                                $tmpArray[$primaryIngredients] = $arrActiveSubstance[0]['as_name'];
                                $list[$primaryIngredients] = $arrActiveSubstance[0]['as_name'];
                            }
                        } else {
                            $arrActiveSubstance = $this->getCasedataService()->getActiveIngradientByName($list[$uniqueKey]);
                        }
                        if (count($arrActiveSubstance) > 0) {
                            // $dataObject[] = $tmpArray;
                            $data[] = $tmpArray;
                        }
                    }
                }
                foreach ($datesdata as $key => $datarray) {
                    $uniqueKeyVal = '';
                    $tempArray = array();
                    foreach ($datarray as $datakey => $datavalue) {
                        $datavalue = trim($datavalue);
                        if ($datakey != $keyValue) {
                            $tmpValue = $datavalue == null ? '' : $datavalue;
                            if (($datavalue != '') && (Date('j-M-Y', strtotime($datavalue)) == $datavalue || Date('d-M-Y', strtotime($datavalue)) == $datavalue)) {
                                $tempArray[$datesheaders[$datakey]] = Date('Y-m-d', strtotime($datavalue));
                            } else {
                                $tempArray[$datesheaders[$datakey]] = trim(htmlspecialchars($datavalue, ENT_QUOTES));
                            }
                        } else {
                            $uniqueKeyVal = trim(htmlspecialchars($datavalue, ENT_QUOTES));
                        }
                    }
                    $datesArray[$uniqueKeyVal] = $tempArray;
                }
                $keyValue = array_search($uniqueKey, $headers);
                foreach ($data as $key => $datarray) {
                    $tempArray = array();
                    $tempArray = isset($datesArray[trim($datarray[$uniqueKey]) ]) ? $datesArray[trim($datarray[$uniqueKey]) ] : array();
                    if (count($tempArray) > 0) {
                        foreach ($datarray as $datakey => $datavalue) {
                            $datavalue = trim($datavalue);
                            // $datavalue = ($datavalue == null || $datavalue == '') ? (array_key_exists($headers[$datakey], $tempArray) ? trim($tempArray[$headers[$datakey]]) : '') : $datavalue;
                            $datavalue = ($datavalue == null || $datavalue == '') ? (array_key_exists($datakey, $tempArray) ? trim($tempArray[$datakey]) : '') : $datavalue;
                            if (($datavalue != '') && (Date('j-M-Y', strtotime($datavalue)) == $datavalue || Date('j-M-y', strtotime($datavalue)) == $datavalue || Date('d-M-Y', strtotime($datavalue)) == $datavalue || Date('d-M-y', strtotime($datavalue)) == $datavalue || Date('d-m-Y', strtotime($datavalue)) == $datavalue || Date('d-m-Y', strtotime($datavalue)) == $datavalue)) {
                                // $dataArray[$i][$headers[$datakey]]=Date('Y-m-d', strtotime($datavalue));
                                $datarray[$datakey] = Date('Y-m-d', strtotime($datavalue));
                            } else {
                                // $dataArray[$i][$headers[$datakey]]= trim(htmlspecialchars($datavalue, ENT_QUOTES));
                                $datarray[$datakey] = trim(htmlspecialchars($datavalue, ENT_QUOTES));
                            }
                        }
                        $dataArray[] = $datarray;
                    }
                    $i++;
                }
                $socValue = isset($configValue[0]['socname']) ? $configValue[0]['socname'] : '';
                $ptValue = isset($configValue[0]['ptname']) ? $configValue[0]['ptname'] : '';
                $ssValue = isset($configValue[0]['seriousnessevent']) ? $configValue[0]['seriousnessevent'] : '';
                $asValue = isset($configValue[0]['active_ingredient']) ? $configValue[0]['active_ingredient'] : '';
                if ($flag == 0 && !empty($dataArray)) {
                    $dataArray = $this->getService()->swapCsvColumnToDb($dataArray, $importFieldArray);
                    $dataArray = array_filter($dataArray);
                    foreach ($dataArray as $data) {
                        $ptNames[] = array($asValue => $data[$asValue], $ptValue => $data[$ptValue]);
                        foreach ($requiredFieldArray as $key => $value) {
                            $tempValue = isset($data[$key]) ? trim($data[$key]) : null;
                            if ($tempValue == null || $tempValue == '') {
                                $flag = 1;
                                $message = "value cannot be empty for " . $sourceFieldArray[$key];
                                break;
                            }
                        }
                        if ($flag == 1) {
                            break;
                        }
                    }
                    if ($flag == 0) {
                        $columnForDuplicateCheck = $this->getService()->columnForDuplicateCheck($importFieldMapping);
                        if (!empty($columnForDuplicateCheck)) {
                            $dupArraydata = $this->getService()->computeDuplicateCheckArrayData($columnForDuplicateCheck, $dataArray);
                            $dupArraydata = array_map("unserialize", array_unique(array_map("serialize", $dupArraydata)));
                            $dataArray = $this->getService()->deDuplicateDataArray($dataArray, $dupArraydata);
                            if (!empty($dupArraydata)) {
                                $dupDataFromDb = $this->getCasedataHelperService()->getDupDataFromDb($dupArraydata, $tableName, array_keys($columnForDuplicateCheck));
                                $duplicateRecords = count($dupDataFromDb);
                                if (!empty($dupDataFromDb)) {
                                    $dataArray = $this->getService()->findDuplicateDataArray($dataArray, $dupDataFromDb);
                                }
                            }
                        }
                        if (!empty($dataArray)) {
                            foreach ($dataArray as $key => $csm) {
                                $dataArray[$key][$sourceCustomer] = 'safetydb';

                                $ptname = $dataArray[$key]["ptname"];
                                $rankResult=$this->getWorkflowService()->getPtName($ptname); 

                                switch ($rankResult) {
                                case "DME":
                                    $rank = 1;
                                    break;
                                case "IME":
                                    if (strtolower($dataArray[$key]["death"]) == "yes") {
                                        $rank = 2;
                                    } else {
                                        $rank = 3;
                                    }
                                    break;
                                default:
                                    $rank = 4;
                                }

                                if (in_array("rank", $configValue[0])) {
                                    $dataArray[$key]["rank"] = $rank;
                                }
                            }
                            $uniqueRecords = count($dataArray);
                            $InserCsvData = $this->getCasedataHelperService()->insertCsvData($dataArray, $tableName);
                            $activeSubstances = array_column($dataArray, $asValue);
                            $activeSubstances = array_unique($activeSubstances);
                            $keySubstance = array();
                            if (count($activeSubstances) > 0) {
                                foreach ($activeSubstances as $activeSubstance) {
                                    $arrActiveSubstance = $this->getCasedataService()->getActiveIngradientByName($activeSubstance);
                                    if (count($arrActiveSubstance) > 0) {
                                        $tempName = trim(strtolower($activeSubstance));
                                        $keySubstance[$tempName] = $arrActiveSubstance[0]['as_id'];
                                    }
                                }
                            }
                            if (count($ptNames) > 0) {
                                $input = array();
                                foreach ($ptNames as $key => $ptName) {
                                    $tempName = trim(strtolower($ptName[$asValue]));
                                    if (array_key_exists($tempName, $keySubstance)) {
                                        $ptExists = $this->getCasedataService()->getPTNameByNameAndActiveSubstance($keySubstance[$tempName], $ptName[$ptValue]);
                                        if (count($ptExists) == 0) {
                                            $input[] = "('" . $ptName[$ptValue] . "', now(), $keySubstance[$tempName])";
                                            $this->getCasedataService()->insertNewPreferredTerms($input);
                                            $input = array();
                                        }
                                    }
                                }
                            }
                            $fileName = $post['file']['name'];
                            $hostName = gethostname();
                            $fileInfo = pathinfo($fileName);
                            $newFileName = $fileInfo['filename'] . "_" . $hostName . "_" . Date('YmdHis') . "." . $fileInfo['extension'];
                            $keyname = "csvImport/" . $newFileName;
                            if (count($activeSubstances) > 0) {
                                foreach ($activeSubstances as $activeSubstance) {
                                    $tempName = trim(strtolower($activeSubstance));
                                    if (array_key_exists($tempName, $keySubstance)) {
                                        $this->getCasedataService()->updateQuantitativeAnalysis($trackerId, $formId, $activeSubstance, $keySubstance[$tempName], $configValue, 'safetydb');
                                        $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "FileImport", "", $fileName, "imported data for active ingredient: $activeSubstance", "Success", $trackerData['client_id']);
                                    }
                                }
                            }
                            if (file_exists($post['file']['tmp_name'])) {
                                $awsResult = $this->forward()->dispatch('Common\FileHandling\Controller\FileHandling', array('action' => 'uploadFilesToAws', 'keyname' => $keyname, 'filepath' => $post['file']['tmp_name'], 'del' => '1'));
                            }
                            $htmlPart = "<html>" . "<body>" . "Dear " . $userDetails['u_name'] . ", </br>
                                        <p>
                                            " . $uniqueRecords . " records imported successfully! </br>
                                        
                                            <h5>Confidentiality Statement:</h5>
                                                This is a system generated correspondence. Please do not reply to this email </br>
                                                <h5>Please contact " . $groupId . " in case you have any questions.</h5>
                                        </p>
                                        
                                        <div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                                            <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                                        </div>
                                    </body>
                                 </html>";
                            $res = $this->getMailService()->sendSesEmail('Import Status', $htmlPart, array($userDetails['email']), array($groupId));
                            if (isset($post['auditlogmsg']) && $post['auditlogmsg'] != '') {
                                $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "FileImport", "", $fileName, $post['auditlogmsg'], "Success", $trackerData['client_id']);
                                $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "FileImport", "", $fileName, $uniqueRecords . ' records imported successfully!', "Success", $trackerData['client_id']);
                            }
                            if (isset($awsResult) && is_object($awsResult)) {
                                $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "FileImport", "", $fileName, "File moved to s3", "Success", $trackerData['client_id']);
                            } else {
                                $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "FileImport", "", $fileName, $message, "Failed", $trackerData['client_id']);
                            }
                            $resultArray['result'] = 1;
                            $resultArray['message'] = $uniqueRecords . ' records imported successfully!';
                            $resultArray['totalRecord'] = $uniqueRecords;
                            $this->flashMessenger()->addMessage(array('success' => $resultArray['message']));
                            $response->setContent(json_encode($resultArray));
                            return $response;
                        } else {
                            $resultArray['result'] = 0;
                            $resultArray['message'] = ($message != '') ? $message : 'All records are duplicate';
                            $response->setContent(\Zend\Json\Json::encode($resultArray));
                            return $response;
                        }
                    } else {
                        $resultArray['result'] = 0;
                        $resultArray['message'] = ($message != '') ? $message : 'No valid data to upload';
                        $response->setContent(\Zend\Json\Json::encode($resultArray));
                        return $response;
                    }
                } else {
                    $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "CsvFileImport", "", "", "empty data sent for import", "Fail", $trackerData['client_id']);
                    $resultArray['result'] = 0;
                    $resultArray['message'] = $message;
                    $response->setContent(\Zend\Json\Json::encode($resultArray));
                    return $response;
                }
            } else {
                $resultArray['result'] = 0;
                $resultArray['message'] = ($message != '') ? $message : 'No valid data to upload';
                $response->setContent(\Zend\Json\Json::encode($resultArray));
                return $response;
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $message = 'Method not allowed';
        }
    }
    public function ermrAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId != 0 && in_array($trackerId, $trackerIds)) {
                $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id' => $formId));
            } else {
                return $this->redirect()->toRoute('tracker');
            }
        }
        $view = new ViewModel();
        $view->setVariables(array('trackerId' => $trackerId, 'formId' => $formId));
        return $view;
    }
    public function readErmrImportFileAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $request = $this->getRequest();
        $response = $this->getResponse();
        $resultArray = array();
        if (!isset($userContainer->u_id)) {
            $resultArray['result'] = 2;
            $response->setContent(json_encode($resultArray));
            return $response;
        }
        $userDetails = $userContainer->user_details;
        $configContainer = $session->getSession('config');
        $allowedFileSize = $configContainer->config['importFileSizeErmr'];
        $allowedFileSize = (int)$allowedFileSize;
        $dateFormat = $configContainer->config['dateFormat'];
        $trackerId = $this->params()->fromRoute('trackerId', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $formId = $this->params()->fromRoute('formId', 0);
        $message = '';
        $config = $this->getServiceLocator()->get('config');
        $groupId = $config['group']['group_id'];
        $flag = 0;
        if ($request->isPost()) {
            $post = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());
            $tableName = "form_" . $trackerId . "_" . $formId;
            $fileName = isset($post['file']['name']) ? $post['file']['name'] : '';
            $fileSize = isset($post['file']['size']) ? $post['file']['size'] : '';
            $fileSize = (int)$fileSize;
            $extension = ($fileName != '') ? substr($fileName, strrpos($fileName, '.') + 1) : '';
            $tempFileName = isset($post['file']['tmp_name']) ? $post['file']['tmp_name'] : '';
            $columns = array();
            $headers = array();
            $dataObject = array();
            $reqFields = array();
            $arrErmrList = array();
            $arrErmrFinal = array();
            $keySubstance = array();
            $inputPT = array();
            $inputQuantitative = array();
            $auditLogs = array();
            $activeSubstances = array();
            $mappingArray = array();
            try {
                if ($tempFileName != '' && $fileSize <= $allowedFileSize) {
                    if ($extension == "csv" || $extension == "xls" || $extension == "xlsx") {
                        $configValue = $this->getCasedataService()->getQuantitativeSettingsConfigs($formId);
                        $importFieldMapping = $this->getCasedataService()->getErmrSettingsConfigs($tableName);
                        foreach ($importFieldMapping as $mapping) {
                            $label = trim($mapping['label']);
                            $dbField = trim($mapping['db_field']);
                            $mappingArray[$label] = $dbField;
                            if ($mapping['required'] == 'true') {
                                $reqFields[] = trim($mapping['label']);
                            }
                            $headers[] = $mapping['label'];
                        }
                        if ($extension == "csv") {
                            $tempArray = $this->parseCsvFile($tempFileName, $configContainer->config['ermr_header_line'], 'ermr');
                        } else if ($extension == "xls" || $extension == "xlsx") {
                            $tempArray = $this->parseXlsFile($tempFileName, $configContainer->config['ermr_header_line'], 'ermr');
                        }
                        $arrErmrList = $tempArray['list'];
                        $dateRange = isset($tempArray['date']) ? $tempArray['date'] : '';
                        $columns = array_keys($arrErmrList[0]);
                        foreach ($reqFields as $reqField) {
                            if (!in_array($reqField, $columns)) {
                                $resultArray['result'] = 0;
                                $resultArray['message'] = $reqField . " is requried in the file";
                                $response->setContent(\Zend\Json\Json::encode($resultArray));
                                return $response;
                            }
                        }
                        $reqFields = array_diff($headers, $columns);
                        foreach ($arrErmrList as $ermrList) {
                            $activeSubstance = trim(htmlspecialchars($ermrList['Active Substance'], ENT_QUOTES));
                            $activeSubstance = ucwords(strtolower($activeSubstance));
                            $ermrList['Active Substance'] = $activeSubstance;
                            $ptName = trim(htmlspecialchars($ermrList['PT'], ENT_QUOTES));
                            $ermrList['PT'] = $ptName;
                            $iSubstanceId = '';
                            if (array_key_exists($activeSubstance, $keySubstance)) {
                                $iSubstanceId = $keySubstance[$activeSubstance];
                            } else {
                                $arrActiveSubstance = $this->getCasedataService()->getActiveIngradientByName($activeSubstance);
                                if (count($arrActiveSubstance) > 0) {
                                    $keySubstance[$activeSubstance] = $arrActiveSubstance[0]['as_id'];
                                    $iSubstanceId = $arrActiveSubstance[0]['as_id'];
                                }
                            }
                            if ($iSubstanceId > 0) {
                                foreach ($reqFields as $reqField) {
                                    $ermrList[$reqField] = '';
                                }
                                if ((mb_substr($ermrList['IME/DME'], 0, 3)) != 'IME' && (($ermrList['Tot Paed'] > 1 && $ermrList['Relative ROR (-) Paed vs Others'] > 1) OR ($ermrList['Tot Geriatr'] > 2 && $ermrList['Relative ROR (-) Geriatr vs Others'] > 1) OR ($ermrList['Tot Spont Europe'] > 2 && $ermrList['ROR (-) Europe'] > 1) OR ($ermrList['Tot Spont N America'] > 2 && $ermrList['ROR (-) N America'] > 1) OR ($ermrList['Tot Spont Japan'] > 2 && $ermrList['ROR (-) Japan'] > 1) OR ($ermrList['Tot Spont Asia'] > 2 && $ermrList['ROR (-) Asia'] > 1) OR ($ermrList['Tot Spont Rest'] > 2 && $ermrList['ROR (-) Rest'] > 1))) {
                                    $ermrList['Non-IME SDR'] = 'true';
                                } else {
                                    $ermrList['Non-IME SDR'] = 'false';
                                }
                                $ptCode = $this->getCasedataService()->getPTCodes($activeSubstance, $ptName);
                                if ($ptCode[0]['risk_count'] == '0' && $ermrList['New EVPM'] > 0) {
                                    $ermrList['Category'] = 3;
                                } else {
                                    $ermrList['Category'] = $ptCode[0]['risk_count'];
                                }
                                $pos = strpos($ermrList['IME/DME'], 'DME');
                                $dme = $pos === false ? false : true;
                                if ($ermrList['Category'] == 3 && $dme && $ermrList['New Fatal'] > 0) {
                                    $ermrList['Priority'] = '0';
                                    $ermrList['Selected for Qualitative analysis'] = 'yes';
                                } else if ($ermrList['Category'] == 3 && $dme) {
                                    $ermrList['Priority'] = '1';
                                    $ermrList['Selected for Qualitative analysis'] = 'yes';
                                } else if ($ermrList['Category'] == 3 && $ermrList['IME/DME'] == 'IME' && (strtolower($ermrList['SDR All']) == 'yes' || strtolower($ermrList['SDR Paed']) == 'yes' || strtolower($ermrList['SDR Geriatr']) == 'yes')) {
                                    $ermrList['Priority'] = '2';
                                    $ermrList['Selected for Qualitative analysis'] = 'yes';
                                } else if ($ermrList['Category'] == 3 && $ermrList['IME/DME'] == 'IME' && $ermrList['New Fatal'] > 0 && ((strtolower($ermrList['SDR All']) == 'no' || strtolower($ermrList['SDR All']) == '') && (strtolower($ermrList['SDR Paed']) == 'no' || strtolower($ermrList['SDR Paed']) == '') && (strtolower($ermrList['SDR Geriatr']) == 'no' || strtolower($ermrList['SDR Geriatr']) == ''))) {
                                    $ermrList['Priority'] = '3';
                                    $ermrList['Selected for Qualitative analysis'] = 'yes';
                                } else if ($ermrList['Category'] == 3 && $ermrList['Non-IME SDR'] == 'true') {
                                    $ermrList['Priority'] = '4';
                                    $ermrList['Selected for Qualitative analysis'] = 'yes';
                                } else {
                                    $ermrList['Priority'] = $ermrList['Category'] == 3 ? 'NA' : null;
                                    $ermrList['Selected for Qualitative analysis'] = 'no';
                                }
                                if ($ermrList['Category'] == 3 && $ermrList['Selected for Qualitative analysis'] == 'yes') {
                                    // if ($ermrList['Category'] == 3) {
                                    if ($ptCode[0]['risk_count'] == '0') {
                                        $ptExists = $this->getCasedataService()->getPTNameByNameAndActiveSubstance($iSubstanceId, $ptName);
                                        if (count($ptExists) == 0) {
                                            $inputPT[] = "('" . $ptName . "', now(), $iSubstanceId)";
                                            $auditLogs[] = array("1", isset($userDetails['email']) ? $userDetails['email'] : '', "Add New Preferred Term", "", json_encode(array('PT' => $ptName, 'Active Substance' => $activeSubstance)), "PT added from eRMR data", "Success", $trackerData['client_id']);
                                        }
                                    }
                                    $qtExists = $this->getCasedataService()->getQuantitativeDataByPTNameAndActiveSubstance($iSubstanceId, $ptName, $trackerId);
                                    if (count($qtExists) == 0) {
                                        $activeSubstances[] = $activeSubstance;
                                        $soc = trim(htmlspecialchars($ermrList['SOC'], ENT_QUOTES));
                                        $rationale = date("Y-m-d H:i:s") . " : " . "Added from eRMR data.";
                                        $inputQuantitative[] = "('" . $soc . "', '" . $ptName . "', $iSubstanceId, '" . $activeSubstance . "', '" . $ermrList['Category'] . "', '" . $ermrList['Priority'] . "', '" . $ermrList['ROR (-) All'] . "', '" . $rationale . "', '" . $ermrList['Non-IME SDR'] . "', $trackerId, 'evdas')";
                                        $auditLogs[] = array("1", isset($userDetails['email']) ? $userDetails['email'] : '', "Add New Quantitative Analysis", "", json_encode(array('Active Substance' => $activeSubstance, 'PT' => $ptName, 'Category' => $ermrList['Category'], 'Priority' => $ermrList['Priority'], 'ROR_All' => $ermrList['ROR (-) All'], 'Non-IME SDR' => $ermrList['Non-IME SDR'])), "PT data added from eRMR data", "Success", $trackerData['client_id']);
                                        // } else if ($qtExists[0]['medical_evaluation'] != 3) {
                                        
                                    } else {
                                        $activeSubstances[] = $activeSubstance;
                                        $soc = trim(htmlspecialchars($ermrList['SOC'], ENT_QUOTES));
                                        $arrRationale = json_decode($qtExists[0]['rationale']);
                                        $extra = '';
                                        $extra = ($qtExists[0]['medical_evaluation'] != $ermrList['Category']) ? ' Category value changed from ' . $qtExists[0]['medical_evaluation'] . ' to ' . $ermrList['Category'] : '';
                                        $rationale = date("Y-m-d H:i:s") . " : " . "Updated from eRMR data." . $extra . " #" . $qtExists[0]['rationale'];
                                        $inputQuantitative[] = "('" . $soc . "', '" . $ptName . "', $iSubstanceId, '" . $activeSubstance . "', '" . $ermrList['Category'] . "', '" . $ermrList['Priority'] . "', '" . $ermrList['ROR (-) All'] . "', '" . $rationale . "', '" . $ermrList['Non-IME SDR'] . "', $trackerId, 'evdas')";
                                        $auditLogs[] = array("1", isset($userDetails['email']) ? $userDetails['email'] : '', "Update Quantitative Analysis", json_encode(array('Active Substance' => $activeSubstance, 'PT' => $ptName, 'Category' => $qtExists[0]['medical_evaluation'], 'Priority' => $qtExists[0]['Priority'], 'ROR_All' => $qtExists[0]['ror_all'], 'Non-IME SDR' => $ermrList['Non-IME SDR'])), json_encode(array('Active Substance' => $activeSubstance, 'PT' => $ptName, 'Category' => $ermrList['Category'], 'Priority' => $ermrList['Priority'], 'ROR_All' => $ermrList['ROR (-) All'], 'Non-IME SDR' => $ermrList['Non-IME SDR'])), "PT data updated from eRMR data", "Success", $trackerData['client_id']);
                                    }
                                }
                            }
                            $arrErmrFinal[] = $ermrList;
                        }
                        if (count($inputPT) > 0) {
                            $this->getCasedataService()->insertNewPreferredTerms($inputPT);
                        }
                        if (count($inputQuantitative) > 0) {
                            $inc = 0;
                            $counter = 0;
                            $limit = 500;
                            $tempQuantArray = array();
                            foreach ($inputQuantitative as $arrInput) {
                                $tempQuantArray[] = $arrInput;
                                $counter = $counter + 1;
                                $inc = $inc + 1;
                                if ($counter == $limit) {
                                    $this->getCasedataService()->insertQuantitativeAnalysis($tempQuantArray);
                                    $tempQuantArray = array();
                                    $counter = 0;
                                }
                                if ($inc == count($inputQuantitative) && $counter > 0) {
                                    $this->getCasedataService()->insertQuantitativeAnalysis($tempQuantArray);
                                    $tempQuantArray = array();
                                }
                            }
                            $this->getCasedataService()->updateMedicalConcept($trackerId);
                        }
                        if (count($arrErmrFinal) > 0) {
                            $ermrTableName = 'ermr_' . $trackerId;
                            $arrErmrFinal = $this->getService()->swapCsvColumnToDb($arrErmrFinal, $mappingArray);
                            $arrErmrFinal = array_filter($arrErmrFinal);
                            $InserCsvData = $this->getCasedataHelperService()->insertCsvData($arrErmrFinal, $ermrTableName);
                        }
                        $activeSubstances = array_unique($activeSubstances);
                        foreach ($auditLogs as $auditLog) {
                            // foreach ($activeSubstances as $sActiveSubstance) {
                            $this->getAuditService()->saveToLog($auditLog[0], $auditLog[1], $auditLog[2], "", $auditLog[4], $auditLog[5], $auditLog[6], $auditLog[7]);
                            // $this->getAuditService()->saveToLog("1", isset($userDetails['email'])?$userDetails['email']:'', "eRMRCsvFileImport", "", $fileName, "imported eRMR data for active ingredient: $sActiveSubstance", "Success", $trackerData['client_id']);
                            
                        }
                        if ($tempFileName != '') {
                            $fileName = $post['file']['name'];
                            $hostName = gethostname();
                            $fileInfo = pathinfo($fileName);
                            $newFileName = 'eRMR_' . $fileInfo['filename'] . "_" . $hostName . "_" . Date('YmdHis') . "." . $fileInfo['extension'];
                            $keyname = "csvImport/" . $newFileName;
                            if (file_exists($post['file']['tmp_name'])) {
                                $awsResult = $this->forward()->dispatch('Common\FileHandling\Controller\FileHandling', array('action' => 'uploadFilesToAws', 'keyname' => $keyname, 'filepath' => $post['file']['tmp_name'], 'del' => '1'));
                            }
                            $htmlPart = "<html>" . "<body>" . "Dear " . $userDetails['u_name'] . ", </br>
                                        <p>
                                            " . count($inputQuantitative) . " eRMR records imported successfully! </br>
                                        
                                            <h5>Confidentiality Statement:</h5>
                                                This is a system generated correspondence. Please do not reply to this email </br>
                                                <h5>Please contact " . $groupId . " in case you have any questions.</h5>
                                        </p>
                                        
                                        <div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                                            <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                                        </div>
                                    </body>
                                 </html>";
                            $res = $this->getMailService()->sendSesEmail('eRMR Import Status', $htmlPart, array($userDetails['email']), array($groupId));
                            $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "eRMRFileImport", "", $fileName, count($inputQuantitative) . ' eRMR records imported successfully!', "Success", $trackerData['client_id']);
                            if (isset($awsResult) && is_object($awsResult)) {
                                $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "eRMRFileImport", "", $fileName, "File moved to s3", "Success", $trackerData['client_id']);
                            } else {
                                $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "eRMRFileImport", "", $fileName, "File unable to move to s3", "Failed", $trackerData['client_id']);
                            }
                        }
                        $resultArray['result'] = 1;
                        $resultArray['message'] = count($inputQuantitative) . ' eRMR records imported successfully!';
                        $resultArray['totalRecord'] = count($inputQuantitative);
                        $this->flashMessenger()->addMessage(array('success' => $resultArray['message']));
                        $response->setContent(json_encode($resultArray));
                        return $response;
                    } else {
                        $message = "Invalid file type uploaded";
                        $resultArray['result'] = 0;
                        $resultArray['message'] = $message;
                        $response->setContent(json_encode($resultArray));
                        return $response;
                    }
                } else {
                    $message = "max upload size is $allowedFileSize Byte";
                    $resultArray['result'] = 0;
                    $resultArray['message'] = $message;
                    $response->setContent(json_encode($resultArray));
                    return $response;
                }
            }
            catch(Exception $ex) {
                $message = $e->getMessage();
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $response->setContent($message);
                return $response;
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $response->setContent('Method not allowed');
            return $response;
        }
    }
    public function parseXlsFile($csvfile, $rowLine = 0, $type = '')
    {
        $result = array();
        $csv = Array();
        if ($type == 'ermr') {
            $rowcount = - 1;
        } else {
            $rowcount = 0;
        }
        $header_colcount = 0;
        $rowLine = (int)$rowLine;
        $spreadsheet = IOFactory::load($csvfile);
        if ($spreadsheet !== null) {
            unset($sheetData);
            $sheetData = array();
            $sheetData = $spreadsheet->getActiveSheet()->toArray();
            foreach ($sheetData as $row) {
                if ($rowcount == $rowLine) {
                    $header_colcount = count($row);
                    foreach ($row as $h) {
                        // $head[] = trim($h);
                        $h = preg_replace('/[^A-Za-z0-9 ()\-+\/]/', ' ', $h);
                        $head[] = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h));
                    }
                }
                if ($rowcount > $rowLine) {
                    $row_colcount = count($row);
                    if ($row_colcount == $header_colcount) {
                        array_walk_recursive(
                            $row, function (&$value) {
                                $value = trim(strtolower($value)) == 'non serious' ? 'Not Serious' : trim($value);
                            }
                        );
                        if ($type == 'lit') {
                            foreach ($head as $key => $value) {
                                $key = strtolower(str_replace(' ', '_', $key));
                                $head[$key] = strtolower(str_replace(' ', '_', $value));
                            }
                            foreach ($head as $key => $value) {
                                $key = strtolower(str_replace('(', '', $key));
                                $head[$key] = strtolower(str_replace('(', '', $value));
                            }
                            foreach ($head as $key => $value) {
                                $key = strtolower(str_replace(')', '', $key));
                                $head[$key] = strtolower(str_replace(')', '', $value));
                            }
                            foreach ($head as $key => $value) {
                                $key = strtolower(str_replace('/', '', $key));
                                $head[$key] = strtolower(str_replace('/', '_', $value));
                            }
                            foreach ($row as $key => $value) {
                                $key = strtolower(str_replace('"', '', $key));
                                $row[$key] = strtolower(str_replace('"', '_', $value));
                            }
                        }    
                        $entry = array_combine($head, $row);
                        $csv[] = $entry;
                        // print_r ($csv); die;
                       
                    } else {
                        error_log("xlsreader: Invalid number of columns at line " . ($rowcount + 2) . " (row " . ($rowcount + 1) . "). Expected=$header_colcount Got=$row_colcount");
                        return null;
                    }
                }
                $rowcount++;
            }
        } else {
            error_log("xlsreader: Could not read XLS file \"$csvfile\"");
            return null;
        }
        if ($type == 'ermr') {
            $result['list'] = $csv;
            return $result;
        } else {
            return $csv;
        }
    }
    // POC code
    public function literatureAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId != 0 && in_array($trackerId, $trackerIds)) {
                $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id' => $formId));
            } else {
                return $this->redirect()->toRoute('tracker');
            }
        }
        $view = new ViewModel();
        $view->setVariables(array('trackerId' => $trackerId, 'formId' => $formId));
        return $view;
    }
    public function aggregatereportsAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId != 0 && in_array($trackerId, $trackerIds)) {
                $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id' => $formId));
            } else {
                return $this->redirect()->toRoute('tracker');
            }
        }
        $view = new ViewModel();
        $view->setVariables(array('trackerId' => $trackerId, 'formId' => $formId));
        return $view;
    }
    public function riskmanagementplanAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId != 0 && in_array($trackerId, $trackerIds)) {
                $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id' => $formId));
            } else {
                return $this->redirect()->toRoute('tracker');
            }
        }
        $view = new ViewModel();
        $view->setVariables(array('trackerId' => $trackerId, 'formId' => $formId));
        return $view;
    }
    public function rrimportAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId != 0 && in_array($trackerId, $trackerIds)) {
                $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id' => $formId));
            } else {
                return $this->redirect()->toRoute('tracker');
            }
        }
        $view = new ViewModel();
        $view->setVariables(array('trackerId' => $trackerId, 'formId' => $formId));
        return $view;
    }
    public function ctimportAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId != 0 && in_array($trackerId, $trackerIds)) {
                $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id' => $formId));
            } else {
                return $this->redirect()->toRoute('tracker');
            }
        }
        $view = new ViewModel();
        $view->setVariables(array('trackerId' => $trackerId, 'formId' => $formId));
        return $view;
    }
    public function othersourcesAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId != 0 && in_array($trackerId, $trackerIds)) {
                $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id' => $formId));
            } else {
                return $this->redirect()->toRoute('tracker');
            }
        }
        $view = new ViewModel();
        $view->setVariables(array('trackerId' => $trackerId, 'formId' => $formId));
        return $view;
    }
    public function importanduploadtos3Action()
    {
        // set_time_limit(0);
        // $session = new SessionContainer();
        // $userContainer = $session->getSession('user');
        // $request = $this->getRequest();
        // $response = $this->getResponse();
        // $resultArray = array();
        // if (!isset($userContainer->u_id)) {
        //     $resultArray['result'] = 2;
        //     $response->setContent(json_encode($resultArray));
        //     return $response;
        // }
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $request = $this->getRequest();
        $response = $this->getResponse();
        $resultArray = array();
        if (!isset($userContainer->u_id)) {
            $resultArray['result'] = 2;
            $response->setContent(json_encode($resultArray));
            return $response;
        }
        $userDetails = $userContainer->user_details;
        $configContainer = $session->getSession('config');
        $allowedFileSize = $configContainer->config['importFileSizeErmr'];
        $allowedFileSize = (int)$allowedFileSize;
        $dateFormat = $configContainer->config['dateFormat'];
        $trackerId = $this->params()->fromRoute('trackerId', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $formId = $this->params()->fromRoute('formId', 0);
        $message = '';
        $config = $this->getServiceLocator()->get('config');
        $groupId = $config['group']['group_id'];
        $flag = 0;
        
        if ($request->isPost()) {
            $post = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());
            $uploadedfiletype = $post['file'][0];
            $fileName = $post['file']['name'];
            $fileSize = $post['file']['size'];
            $fileSize = (int)$fileSize;
            $extension = substr($fileName, strrpos($fileName, '.') + 1);
            $tempFileName = isset($post['file']['tmp_name']) ? $post['file']['tmp_name'] : '';
            $changedFileName = $fileName . '_' . time() . '.' . $extension;
            if ($extension == 'pdf') {
                $parent_folder = "pdfimport";
            }
            if ($extension == 'csv' || $extension == 'xls' || $extension == 'xlsx') {
                $parent_folder = "csvImport";
            }
            if ($extension == 'doc' || ($extension == 'docx')) {
                $parent_folder = "wordfileimport";
            }
            $child_folder = $uploadedfiletype;
            if ($tempFileName != '') {
                $fileName = $post['file']['name'];
                $hostName = gethostname();
                $fileInfo = pathinfo($fileName);
                $newFileName = $fileInfo['filename'] . "_" . $hostName . "_" . Date('YmdHis') . "." . $fileInfo['extension'];
                $keyname = $parent_folder . "/" . $child_folder . "/" . $newFileName;
                if (file_exists($post['file']['tmp_name'])) {
                    $awsResult = $this->forward()->dispatch('Common\FileHandling\Controller\FileHandling', array('action' => 'uploadFilesToAws', 'keyname' => $keyname, 'filepath' => $post['file']['tmp_name'], 'del' => '1'));
                }
            }
            $uploadedfiletype = strtolower(str_replace('_', ' ', $uploadedfiletype));
            $uploadedfiletype = ucfirst($uploadedfiletype);
            $htmlPart = "<html>" . "<body>" . "Dear " . $userDetails['u_name'] . ", </br>
               <p>
                   " . $uploadedfiletype . " File imported successfully! </br>
               
                   <h5>Confidentiality Statement:</h5>
                       This is a system generated correspondence. Please do not reply to this email </br>
                       <h5>Please contact " . $groupId . " in case you have any questions.</h5>
               </p>
               
               <div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                   <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
               </div>
           </body>
        </html>";
            $res = $this->getMailService()->sendSesEmail('File Import Status', $htmlPart, array($userDetails['email']), array($groupId));
            $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "FileImport", "", $fileName, $totalrecords . 'File mported successfully!', "Success", $trackerData['client_id']);
            if (isset($awsResult) && is_object($awsResult)) {
                $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "FileImport", "", $fileName, "File moved to s3", "Success", $trackerData['client_id']);
            } else {
                $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "FileImport", "", $fileName, "File unable to move to s3", "Failed", $trackerData['client_id']);
            }
            $resultArray['result'] = 1;
            $resultArray['message'] = $totalrecords . ' File imported imported successfully!';
            $resultArray['totalRecord'] = $totalrecords;
            $this->flashMessenger()->addMessage(array('success' => $resultArray['message']));
            $response->setContent(json_encode($resultArray));
            return $response;
        } else {
            $message = "Invalid file type uploaded";
            $resultArray['result'] = 0;
            $resultArray['message'] = $message;
            $response->setContent(json_encode($resultArray));
            return $response;
        }
    }
    public function readlitImportFileAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $request = $this->getRequest();
        $response = $this->getResponse();
        $resultArray = array();
        if (!isset($userContainer->u_id)) {
            $resultArray['result'] = 2;
            $response->setContent(json_encode($resultArray));
            return $response;
        }
        $userDetails = $userContainer->user_details;
        $configContainer = $session->getSession('config');
        $allowedFileSize = $configContainer->config['importFileSizeErmr'];
        $allowedFileSize = (int)$allowedFileSize;
        $dateFormat = $configContainer->config['dateFormat'];
        $trackerId = $this->params()->fromRoute('trackerId', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $formId = $this->params()->fromRoute('formId', 0);
        $message = '';
        $config = $this->getServiceLocator()->get('config');
        $groupId = $config['group']['group_id'];
        $flag = 0;
        if ($request->isPost()) {
            $post = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());
            try {
                $tableName ='form_literature_109_199';
                $importFieldMapping = $this->getCasedataHelperService()->getImportFieldMappingData($tableName);
                foreach ($importFieldMapping as $mapping) {
                    $tempArray = array();
                    $tempArray['data'] = $mapping['source_field_name'];
                    if ($mapping['isDate'] == 'yes') {
                        $tempArray['type'] = 'date';
                        $tempArray['dateFormat'] = $dateFormat;
                        $tempArray['correctFormat'] = true;
                    } else {
                        $tempArray['type'] = 'text';
                    }
                    if ($mapping['isEditable'] == 'no') {
                        $tempArray['readOnly'] = true;
                    }
                    if ($mapping['isDisplay'] == 'yes') {
                        $datecolumns[] = $tempArray;
                        $dateHeaders[] = $mapping['source_field_name'];
                    }
                    $columns[] = $tempArray;
                    $headers[] = $mapping['source_field_name'];
                    if ($mapping['isUnique'] == 'yes') {
                        $uniqueKey = $mapping['source_field_name'];
                    }
                    if ($mapping['isRequired'] == 'yes') {
                        $uniqueKeys[] = strtolower($mapping['source_field_name']);
                    }
                }

                $fileName = isset($post['file']['name']) ? $post['file']['name'] : '';
                $fileSize = isset($post['file']['size']) ? $post['file']['size'] : '';
                $fileSize = (int)$fileSize;
                $extension = ($fileName != '') ? substr($fileName, strrpos($fileName, '.') + 1) : '';
                $tempFileName = isset($post['file']['tmp_name']) ? $post['file']['tmp_name'] : '';
                if ($extension == "csv") {
                    $tempArray = $this->parselitCsvFile($tempFileName);
                }
                if ($extension == "xls" || $extension == "xlsx") {
                    if (count($configValue) > 0) {
                        $headerRow = isset($configValue[0]['xlsheaderline']) ? $configValue[0]['xlsheaderline'] : 0;
                    }
                    $tempArray = $this->parseXlsFile($tempFileName, $headerRow, $type = 'lit');
                    
                }
                foreach ($tempArray as $arrInput) {
                    $headerskeys = array_keys($arrInput);
                    $headers = str_replace('_', ' ', $headerskeys);
                }
                if (count($tempArray) > 0) {
                    foreach ($uniqueKeys as $uniqueKey) {
                        if (!in_array($uniqueKey, $headers)) { 
                            $flag = 1;
                            $message = "Required " . $uniqueKey . " is missing from the uploaded file.";
                            $response->setContent(\Zend\Json\Json::encode(array(0, $message)));
                            return $response;
                        } 
                    }
                }
                $totalrecords = count($tempArray);
                if ($fileSize <= $allowedFileSize) {
                    if (count($tempArray) > 0) {
                        $userdetails = $userContainer->u_id;
                        foreach ($tempArray as $arrInput) {
                            $tem = array_change_key_case($arrInput, CASE_LOWER);
                            $InserCsvData = $this->getCasedataService()->insertlitimportdata($tem, $userdetails, $trackerId, $formId);
                        }
                    }
                    if ($tempFileName != '') {
                        $fileName = $post['file']['name'];
                        $hostName = gethostname();
                        $fileInfo = pathinfo($fileName);
                        $newFileName = 'literature_' . $fileInfo['filename'] . "_" . $hostName . "_" . Date('YmdHis') . "." . $fileInfo['extension'];
                        $keyname = "csvImport/" . $newFileName;
                        if (file_exists($post['file']['tmp_name'])) {
                            $awsResult = $this->forward()->dispatch('Common\FileHandling\Controller\FileHandling', array('action' => 'uploadFilesToAws', 'keyname' => $keyname, 'filepath' => $post['file']['tmp_name'], 'del' => '1'));
                        }
                        $htmlPart = "<html>" . "<body>" . "Dear " . $userDetails['u_name'] . ", </br>
                                            <p>
                                                " . $totalrecords . " Literature records imported successfully! </br>
                                            
                                                <h5>Confidentiality Statement:</h5>
                                                    This is a system generated correspondence. Please do not reply to this email </br>
                                                    <h5>Please contact " . $groupId . " in case you have any questions.</h5>
                                            </p>
                                            
                                            <div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                                                <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                                            </div>
                                        </body>
                                     </html>";
                        $res = $this->getMailService()->sendSesEmail('Literature Import Status', $htmlPart, array($userDetails['email']), array($groupId));
                        $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "LiteratureFileImport", "", $fileName, $totalrecords . ' Literature records imported successfully!', "Success", $trackerData['client_id']);
                        if (isset($awsResult) && is_object($awsResult)) {
                            $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "LiteratureFileImport", "", $fileName, "File moved to s3", "Success", $trackerData['client_id']);
                        } else {
                            $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "LiteratureFileImport", "", $fileName, "File unable to move to s3", "Failed", $trackerData['client_id']);
                        }
                        $resultArray['result'] = 1;
                        $resultArray['message'] = $totalrecords . ' Literature records imported successfully!';
                        $resultArray['totalRecord'] = $totalrecords;
                        $this->flashMessenger()->addMessage(array('success' => $resultArray['message']));
                        $response->setContent(json_encode($resultArray));
                        return $response;
                    } else {
                        $message = "Invalid file type uploaded";
                        $resultArray['result'] = 0;
                        $resultArray['message'] = $message;
                        $response->setContent(json_encode($resultArray));
                        return $response;
                    }
                } else {
                    $message = "max upload size is $allowedFileSize Byte";
                    $resultArray['result'] = 0;
                    $resultArray['message'] = $message;
                    $response->setContent(json_encode($resultArray));
                    return $response;
                }
            }
            catch(Exception $ex) {
                $message = $e->getMessage();
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $response->setContent($message);
                return $response;
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $response->setContent('Method not allowed');
            return $response;
        }
    }
    public function parselitCsvFile($csvfile)
    {
        $result = array();
        $csv = Array();
        $rowcount = 0;
        $header_colcount = 0;
        $rowLine = (int)$rowLine;
        if (($handle = fopen($csvfile, "r")) !== false) {
            $max_line_length = defined('MAX_LINE_LENGTH') ? MAX_LINE_LENGTH : 10000;
            $head = array();
            while (($row = fgetcsv($handle, $max_line_length)) !== false) {
                if ($rowcount == $rowLine) {
                    $header_colcount = count($row);
                    foreach ($row as $h) {
                        // $head[] = trim($h);
                        $h = preg_replace('/[^A-Za-z0-9 ()\-+\/]/', ' ', $h);
                        $head[] = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h));
                    }
                }
                if ($rowcount > $rowLine) {
                    $row_colcount = count($row);
                    if ($row_colcount == $header_colcount) {
                        array_walk_recursive(
                            $row, function (&$value) {
                                $value = trim(strtolower($value)) == 'non serious' ? 'Not Serious' : trim($value);
                            }
                        );
                        foreach ($head as $key => $value) {
                            $key = strtolower(str_replace(' ', '_', $key));
                            $head[$key] = strtolower(str_replace(' ', '_', $value));
                        }
                        foreach ($head as $key => $value) {
                            $key = strtolower(str_replace('(', '', $key));
                            $head[$key] = strtolower(str_replace('(', '', $value));
                        }
                        foreach ($head as $key => $value) {
                            $key = strtolower(str_replace(')', '', $key));
                            $head[$key] = strtolower(str_replace(')', '', $value));
                        }
                        foreach ($head as $key => $value) {
                            $key = strtolower(str_replace('/', '', $key));
                            $head[$key] = strtolower(str_replace('/', '_', $value));
                        }
                        foreach ($row as $key => $value) {
                            $key = strtolower(str_replace('"', '', $key));
                            $row[$key] = strtolower(str_replace('"', '_', $value));
                        }
                        $entry = array_combine($head, $row);
                        $csv[] = $entry;
                    } else {
                        error_log("csvreader: Invalid number of columns at line " . ($rowcount + 2) . " (row " . ($rowcount + 1) . "). Expected=$header_colcount Got=$row_colcount");
                        return null;
                    }
                }
                $rowcount++;
            }
            fclose($handle);
        } else {
            error_log("csvreader: Could not read CSV \"$csvfile\"");
            return null;
        }
        if ($type == 'ermr') {
            $result['list'] = $csv;
            return $result;
        } else {
            return $csv;
        }
    }
    public function readcommanImportFileAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $request = $this->getRequest();
        $response = $this->getResponse();
        $resultArray = array();
        if (!isset($userContainer->u_id)) {
            $resultArray['result'] = 2;
            $response->setContent(json_encode($resultArray));
            return $response;
        }
        $userDetails = $userContainer->user_details;
        $configContainer = $session->getSession('config');
        $allowedFileSize = $configContainer->config['importFileSizeErmr'];
        $allowedFileSize = (int)$allowedFileSize;
        $dateFormat = $configContainer->config['dateFormat'];
        $trackerId = $this->params()->fromRoute('trackerId', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $formId = $this->params()->fromRoute('formId', 0);
        $message = '';
        $config = $this->getServiceLocator()->get('config');
        $groupId = $config['group']['group_id'];
        $flag = 0;
        if ($request->isPost()) {
            $post = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());
            $tableName = "form_aggregate_" . $trackerId;
            $fileName = $post['file']['name'];
            $fileSize = $post['file']['size'];
            $uploadedfiletype = $post['file'][0];

            $fileSize = (int)$fileSize;
            $extension = substr($fileName, strrpos($fileName, '.') + 1);
            $tempFileName = isset($post['file']['tmp_name']) ? $post['file']['tmp_name'] : '';
            $changedFileName = $fileName . '_' . time() . '.' . $extension;
            $columns = array();
            $datecolumns = array();
            $headers = array();
            $dateHeaders = array();
            $dataObject = array();
            $dateObject = array();
            $result = array();
            $ignoredIngredients = array();
            $uniqueKey = '';
            $isAlert = 0;
            $auditLog = '';
            $configwherecheck = $uploadedfiletype . '_'. 'settings';
            // $configValue = $this->getCasedataService()->getcommonimportSettingsConfigs($formId, $configwherecheck);
            $asValue = '';
            $headerRow = 0;
            $changedProductIngredients = array();
            try {
                $importFieldMapping = $this->getCasedataHelperService()->getImportFieldMappingData($tableName);
                foreach ($importFieldMapping as $mapping) {
                    $tempArray = array();
                    $tempArray['data'] = $mapping['source_field_name'];
                    if ($mapping['isDate'] == 'yes') {
                        $tempArray['type'] = 'date';
                        $tempArray['dateFormat'] = $dateFormat;
                        $tempArray['correctFormat'] = true;
                    } else {
                        $tempArray['type'] = 'text';
                    }
                    if ($mapping['isEditable'] == 'no') {
                        $tempArray['readOnly'] = true;
                    }
                    if ($mapping['isDisplay'] == 'yes') {
                        $datecolumns[] = $tempArray;
                        $dateHeaders[] = $mapping['source_field_name'];
                    }
                    $columns[] = $tempArray;
                    $headers[] = $mapping['source_field_name'];
                    if ($mapping['isUnique'] == 'yes') {
                        $uniqueKey = $mapping['source_field_name'];
                    }
                }
                if ($extension == "csv") {
                    $tempArray = $this->parsecommonCsvFile($tempFileName);
                }
                if ($extension == "xls" || $extension == "xlsx") {
                    if (count($configValue) > 0) {
                        $headerRow = isset($configValue[0]['xlsheaderline']) ? $configValue[0]['xlsheaderline'] : 0;
                    }
                    $tempArray = $this->parseXlsFile($tempFileName, $headerRow, $type = '');
                    
                }
                if (count($tempArray) > 0) {
                    foreach ($tempArray as $list) {
                        $tmpArray = array();
                        $temarr = array();
                        foreach ($headers as $header) {
                            if (array_key_exists($header, $list)) {
                                $tmpArray[$header] = $list[$header];
                            } else {
                                if (!in_array($header, $dateHeaders) || $header == $uniqueKey) {
                                    $flag = 1;
                                    $message = "Required " . $header . " is missing from the uploaded file.";
                                    $response->setContent(\Zend\Json\Json::encode(array(0, $message)));
                                    return $response;
                                } else if (in_array($header, $dateHeaders) && $header != $uniqueKey) {
                                    $tmpArray[$header] = '';
                                }
                            }
                        }
                    }
                }
                
                $totalrecords = count($tempArray);
                if ($fileSize <= $allowedFileSize) {
                    if (count($tmpArray) > 0) {
                        $userdetails = $userContainer->u_id;
                        foreach ($tempArray as $arrInput) {
                            $tem = array_change_key_case($arrInput, CASE_LOWER);
                            //  print_r ("tracker id1: ".$trackerId); die;
                            $InserCsvData = $this->getCasedataService()->insertcommonimportdata($tem, $userdetails, $uploadedfiletype, $trackerId);
                        }
                    }
                    if ($extension == 'pdf') {
                        $parent_folder = "pdfimport";
                    }
                    if ($extension == 'csv' || $extension == 'xls' || $extension == 'xlsx') {
                        $parent_folder = "csvImport";
                    }
                    if ($extension == 'doc' || ($extension == 'docx')) {
                        $parent_folder = "wordfileimport";
                    }
                    $child_folder = $uploadedfiletype;
                    if ($tempFileName != '') {
                            $fileName = $post['file']['name'];
                            $hostName = gethostname();
                            $fileInfo = pathinfo($fileName);
                            $newFileName = $fileInfo['filename'] . "_" . $hostName . "_" . Date('YmdHis') . "." . $fileInfo['extension'];
                            $keyname = $parent_folder . "/" . $child_folder . "/" . $newFileName;
                        if (file_exists($post['file']['tmp_name'])) {
                            $awsResult = $this->forward()->dispatch('Common\FileHandling\Controller\FileHandling', array('action' => 'uploadFilesToAws', 'keyname' => $keyname, 'filepath' => $post['file']['tmp_name'], 'del' => '1'));
                        }
                        $child_folder = str_replace('_', ' ', $child_folder);
                        $child_folder = ucfirst($child_folder);
                        $htmlPart = "<html>" . "<body>" . "Dear " . $userDetails['u_name'] . ", </br>
                                            <p>
                                                " . $totalrecords . " ".$child_folder." records imported successfully! </br>
                                            
                                                <h5>Confidentiality Statement:</h5>
                                                    This is a system generated correspondence. Please do not reply to this email </br>
                                                    <h5>Please contact " . $groupId . " in case you have any questions.</h5>
                                            </p>
                                            
                                            <div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                                                <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                                            </div>
                                        </body>
                                     </html>";
                        $res = $this->getMailService()->sendSesEmail(''.$child_folder.' Import Status', $htmlPart, array($userDetails['email']), array($groupId));
                        $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "'.$child_folder.'FileImport", "", $fileName, $totalrecords . ' '.$child_folder.' records imported successfully!', "Success", $trackerData['client_id']);
                        if (isset($awsResult) && is_object($awsResult)) {
                            $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "'.$child_folder.'FileImport", "", $fileName, "File moved to s3", "Success", $trackerData['client_id']);
                        } else {
                            $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "'.$child_folder.'FileImport", "", $fileName, "File unable to move to s3", "Failed", $trackerData['client_id']);
                        }
                        $resultArray['result'] = 1;
                        $resultArray['message'] = $totalrecords . ' '.$child_folder.' records imported successfully!';
                        $resultArray['totalRecord'] = $totalrecords;
                        $this->flashMessenger()->addMessage(array('success' => $resultArray['message']));
                        $response->setContent(json_encode($resultArray));
                        return $response;
                    } else {
                        $message = "Invalid file type uploaded";
                        $resultArray['result'] = 0;
                        $resultArray['message'] = $message;
                        $response->setContent(json_encode($resultArray));
                        return $response;
                    }
                } else {
                    $message = "max upload size is $allowedFileSize Byte";
                    $resultArray['result'] = 0;
                    $resultArray['message'] = $message;
                    $response->setContent(json_encode($resultArray));
                    return $response;
                }
            }
            catch(Exception $ex) {
                $message = $e->getMessage();
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $response->setContent($message);
                return $response;
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $response->setContent('Method not allowed');
            return $response;
        }
    }
    public function parsecommonXlsFile($csvfile, $rowLine = 0, $type = '')
    {
        $result = array();
        $csv = Array();
        if ($type == 'ermr') {
            $rowcount = - 1;
        } else {
            $rowcount = 0;
        }
        $header_colcount = 0;
        $rowLine = (int)$rowLine;
        $spreadsheet = IOFactory::load($csvfile);
        if ($spreadsheet !== null) {
            unset($sheetData);
            $sheetData = array();
            $sheetData = $spreadsheet->getActiveSheet()->toArray();
            foreach ($sheetData as $row) {
                if ($rowcount == $rowLine) {
                    $header_colcount = count($row);
                    foreach ($row as $h) {
                        // $head[] = trim($h);
                        $h = preg_replace('/[^A-Za-z0-9 ()\-+\/]/', ' ', $h);
                        $head[] = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h));
                    }
                }
                if ($rowcount > $rowLine) {
                    $row_colcount = count($row);
                    if ($row_colcount == $header_colcount) {
                        array_walk_recursive(
                            $row, function (&$value) {
                                $value = trim(strtolower($value)) == 'non serious' ? 'Not Serious' : trim($value);
                            }
                        );
                        foreach ($head as $key => $value) {
                            $key = strtolower(str_replace(' ', '_', $key));
                            $head[$key] = strtolower(str_replace(' ', '_', $value));
                        }
                        foreach ($head as $key => $value) {
                            $key = strtolower(str_replace('(', '', $key));
                            $head[$key] = strtolower(str_replace('(', '', $value));
                        }
                        foreach ($head as $key => $value) {
                            $key = strtolower(str_replace(')', '', $key));
                            $head[$key] = strtolower(str_replace(')', '', $value));
                        }
                        foreach ($head as $key => $value) {
                            $key = strtolower(str_replace('/', '', $key));
                            $head[$key] = strtolower(str_replace('/', '_', $value));
                        }
                        foreach ($row as $key => $value) {
                            $key = strtolower(str_replace('"', '', $key));
                            $row[$key] = strtolower(str_replace('"', '_', $value));
                        }
                                                      
                        $entry = array_combine($head, $row);
                        $csv[] = $entry;
                    } else {
                        error_log("xlsreader: Invalid number of columns at line " . ($rowcount + 2) . " (row " . ($rowcount + 1) . "). Expected=$header_colcount Got=$row_colcount");
                        return null;
                    }
                }
                $rowcount++;
            }
        } else {
            error_log("xlsreader: Could not read XLS file \"$csvfile\"");
            return null;
        }
        if ($type == 'ermr') {
            $result['list'] = $csv;
            return $result;
        } else {
            return $csv;
        }
    }
    public function parsecommonCsvFile($csvfile)
    {
        $result = array();
        $csv = Array();
        $rowcount = 0;
        $header_colcount = 0;
        $rowLine = (int)$rowLine;
        if (($handle = fopen($csvfile, "r")) !== false) {
            $max_line_length = defined('MAX_LINE_LENGTH') ? MAX_LINE_LENGTH : 10000;
            $head = array();
            while (($row = fgetcsv($handle, $max_line_length)) !== false) {
                if ($rowcount == $rowLine) {
                    $header_colcount = count($row);
                    foreach ($row as $h) {
                        // $head[] = trim($h);
                        $h = preg_replace('/[^A-Za-z0-9 ()\-+\/]/', ' ', $h);
                        $head[] = trim(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $h));
                    }
                }
                if ($rowcount > $rowLine) {
                    $row_colcount = count($row);
                    if ($row_colcount == $header_colcount) {
                        array_walk_recursive(
                            $row, function (&$value) {
                                $value = trim(strtolower($value)) == 'non serious' ? 'Not Serious' : trim($value);
                            }
                        );
                        $entry = array_combine($head, $row);
                        $csv[] = $entry;
                    } else {
                        error_log("csvreader: Invalid number of columns at line " . ($rowcount + 2) . " (row " . ($rowcount + 1) . "). Expected=$header_colcount Got=$row_colcount");
                        return null;
                    }
                }
                $rowcount++;
            }
            fclose($handle);
        } else {
            error_log("csvreader: Could not read CSV \"$csvfile\"");
            return null;
        }
        if ($type == 'ermr') {
            $result['list'] = $csv;
            return $result;
        } else {
            return $csv;
        }
    }
    // End of POC
    
}
