<?php
namespace Import\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;
use Tracker\Controller\AwsController;

class ImportController extends AbstractActionController
{
    protected $_serviceMapper;
    protected $_adminMapper;
    protected $_roleMapper;

    public function getImportService()
    {
        if (!$this->_adminMapper) {
            $sm = $this->getServiceLocator();
            $this->_adminMapper = $sm->get('Import\Model\Import');
        }
        return $this->_adminMapper;
    }

    public function getService()
    {
        if (!$this->_serviceMapper) {
            $sm = $this->getServiceLocator();
            $this->_serviceMapper = $sm->get('Import\Service\ImportService');
        }
        return $this->_serviceMapper;
    }

    public function getRoleService()
    {
        if (!$this->_roleMapper) {
            $sm = $this->getServiceLocator();
            $this->_roleMapper = $sm->get('Role\Model\Role');
        }
        return $this->_roleMapper;
    }

    /*
     * function to get the file from client
     */
    public function getFileFromClientAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $configContainer = $session->getSession('config');
        $allowedFileSize=$configContainer->config['importFileSize'];

        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        
        $formId = $this->params()->fromRoute('id', 0);
        $request = $this->getRequest();
        $response = $this->getResponse();
        $applicationController = new \Application\Controller\IndexController;
        $message='';
        $flag=0;
        $totalRecords=0;
        $duplicateRecords=0;
        $uniqueRecords=0;
        $InserCsvData='';
        $fieldDataTypeMapper = Array();

        if ($request->isPost()) { 
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
            $tableName="form_".$trackerId."_".$formId;
            $fileName=$post['file']['name'];
            $fileSize=$post['file']['size'];
          
            try{
                $importFieldMapping=$this->getImportService()->getImportFieldMappingData($tableName);                
                if (!empty($importFieldMapping)) {
                    foreach ($importFieldMapping as $mapper) {
                        if ($mapper['isDate'] == 'yes') {
                            $fieldDataTypeMapper[] = $mapper['db_field_name'];
                        }
                    }
                    
                    $importFieldArray = array_column($importFieldMapping, 'db_field_name', 'source_field_name');
                } else {
                    $flag = 1;
                    $message=$importFieldMapping;
                }
            } catch(Exception $e) {
                $message= $e->getMessage();
            }
           
            try{
                if ((int)$fileSize <= ((int)$allowedFileSize)) {
                    $dataArray=$this->getService()->get2DArrayFromCsv($post['file']['tmp_name'], ',', $fieldDataTypeMapper);
                } else {
                    $flag = 1;
                    $message="max upload size is $allowedFileSize Byte";
                }
                
                if (!empty($dataArray)) {
                    $dataArray=$this->getService()->swapCsvColumnToDb($dataArray, $importFieldArray);                    
                    $dataArray = array_filter($dataArray);  
                    $i=0;
                    foreach ($dataArray as $row) {
                        foreach ($fieldDataTypeMapper as $cols) {
                            if (isset($row[$cols])) {
                                $dateTemp = trim($row[$cols]);
                                $dateTemp = $dateTemp == null ? '' : $dateTemp;
                                if (($dateTemp!= '') && !(Date('j-M-Y', strtotime($dateTemp)) == $dateTemp || Date('d-M-Y', strtotime($dateTemp)) == $dateTemp)) {
                                    $message="Accepted Date Format is DD-MMM-YYYY";
                                    $flag=1;
                                    break;                                
                                } else {           
                                    $dataArray[$i][$cols]=Date('Y-m-d', strtotime($dateTemp));                   
                                }
                            }
                        }
                        $i++;
                    }
                    
                } else {
                    $message="Please upload valid CSV file.";
                    $flag=1;
                }
                if (!empty($dataArray) && $flag == 0) {
                    $totalRecords=count($dataArray);
                    $columnForDuplicateCheck=$this->getService()->columnForDuplicateCheck($importFieldMapping);
                    
                    if (!empty($columnForDuplicateCheck)) {
                        $dupArraydata = $this->getService()->computeDuplicateCheckArrayData($columnForDuplicateCheck, $dataArray);
                        
                        $dupArraydata = array_map("unserialize", array_unique(array_map("serialize", $dupArraydata)));
                        $dataArray=$this->getService()->deDuplicateDataArray($dataArray, $dupArraydata);
                        
                        if (!empty($dupArraydata)) { 
                            $dupDataFromDb=$this->getImportService()->getDupDataFromDb($dupArraydata, $tableName, array_keys($columnForDuplicateCheck));
                            
                            $duplicateRecords=count($dupDataFromDb);
                            if (!empty($dupDataFromDb)) {
                                $dataArray=$this->getService()->findDuplicateDataArray($dataArray, $dupDataFromDb);
                            }
                        } else {
                            $flag = 1;
                            $message="There is issue with duplicate Check.";

                        }
                    }
                    
                    
                    
                    if (!empty($dataArray)) { 
                        $uniqueRecords=count($dataArray);
                        $InserCsvData=$this->getImportService()->insertCsvData($dataArray, $tableName);
                    } else {
                        $flag = 1;
                        $message="All records are duplicate";
                    }
                } else {
                    $flag=1;
                }

            } catch(Exception $e) {
                $message= $e->getMessage();
            }
            
            try{
                
                $hostName=gethostname();
                $fileInfo = pathinfo($fileName);
                $newFileName=$fileInfo['filename']."_".$hostName."_".Date('YmdHis').".".$fileInfo['extension'];
                $keyname =  "csvImport/".$newFileName;
                   
                if (!is_object($InserCsvData) || $InserCsvData=='') {
                    $flag = 1;
                    if ($InserCsvData != '') {
                        $message= $InserCsvData;
                    }                    
                } else {
                    $flag = 0;
                    if (file_exists($post['file']['tmp_name'])) {
                            $awsResult=$this->forward()->dispatch(
                                'Tracker\Controller\Aws',
                                array(
                                    'action' => 'uploadFilesToAws',
                                    'keyname' => $keyname,
                                    'filepath' => $post['file']['tmp_name'],
                                    'del' => '1'
                                )
                            );
                    }
                }          
            }catch(Exception $e) {
                echo $e->getMessage();
                $message= $e->getMessage();

            }

            if ($flag == 0) {
                $applicationController->saveToLogFile("1", isset($userDetails['email'])?$userDetails['email']:'', "CsvFileImport", "", $fileName, $message, "Success", $trackerData['client_id']);
                if (isset($awsResult) && is_object($awsResult)) {
                     $applicationController->saveToLogFile("1", isset($userDetails['email'])?$userDetails['email']:'', "CsvFileImport", "", $fileName, "File moved to s3", "Success", $trackerData['client_id']);

                } else {
                    $applicationController->saveToLogFile("1", isset($userDetails['email'])?$userDetails['email']:'', "CsvFileImport", "", $fileName, $message, "Failed", $trackerData['client_id']);
                }
            }
            
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $message= 'Method not allowed';
        }
        $resultArray=array();
        $resultArray['result']=$flag;
        $resultArray['message']=$message;
        $resultArray['totalRecord']=$totalRecords;
        $resultArray['duplicateRecords']=($totalRecords-$uniqueRecords);
        $resultArray['uniqueRecords']=$uniqueRecords;
        $response->setContent(\Zend\Json\Json::encode($resultArray));
        return $response;
    }   
}
