<?php
namespace SigTRACE\SignalCalendar\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;

class SignalCalendarController extends AbstractActionController
{
    protected $_modelService;
    protected $_roleService;
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
    public function getService() 
    {
        if (!$this->_serviceMapper) {
            $sm = $this->getServiceLocator();
            $this->_serviceMapper = $sm->get('SignalCalendar\Service\SignalCalendarService');
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
    public function getModelService()
    {
        if (!$this->_modelService) {
            $sm = $this->getServiceLocator();
            $this->_modelService = $sm->get('SignalCalendar\Model\SignalCalendar');
        }
        return $this->_modelService;
    }
    public function isHavingTrackerPermission($trackerId)
    {
        $session = new SessionContainer();
        $trackerSession = $session->getSession("tracker");
        $trackerIds = $trackerSession->tracker_ids;
        if (!in_array($trackerId, $trackerIds)) {
            return $this->redirect()->toRoute('tracker'); 
        }
        return true;
    }
    public function listAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $trackerSession = $session->getSession("tracker");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            if ($this->isHavingTrackerPermission($trackerId)) {
                $filters = $this->getModelService()->getSignalCalendarFilters($trackerId, $formId);
                $trackerDetails = $trackerSession->tracker_user_groups;
                $grpId = $trackerDetails[$trackerId]['session_group_id'];
                $grpName = $trackerDetails[$trackerId]['session_group']; 
                
                if (isset($grpName) && $grpName != 'SuperAdmin' && $grpName != 'Administrator') {
                    $formIdArray = $this->getModelService()->formIdCheck($trackerId, $grpName, $grpId);
                    $bVal = false;
                    foreach ($formIdArray as $forms) {
                        if ($forms['form_id'] == $formId) {
                            $bVal = true;   
                        }
                    }
                    $formId = $bVal == true ? $formId : 0;
                }
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id' => $formId));
                return new ViewModel(
                    array(
                        'formId' => $formId,
                        'trackerId' => $trackerId,
                        'filters' => $filters
                    )
                ); 
            }
        }
    }
    public function loadSignalCalendarAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            $post = json_decode(file_get_contents('php://input'), true);
            $filter = isset($post['filter'])?$post['filter']:'all';
            
            $resArray = $this->createSignalCalendarCount($trackerId, $formId, $filter);
            $response->setContent(\Zend\Json\Json::encode($resArray)); 
        }
        return $response;
    }
    public function createSignalCalendarCount($trackerId, $formId, $filter)
    {
        $footerTypes = array();
        try {
            $configs = $this->getModelService()->getConfigs(); 
            if (!empty($configs) && $filter == 'all') {
                if (isset($configs['number_count_reports']) && strtolower($configs['number_count_reports']) == 'on') {
                    $countReportQueries = $this->getModelService()->getSignalCalendarCountReports($trackerId, $formId);
                    $footerTypes = $this->getModelService()->getSignalCalendarReports(array(), $trackerId, $formId, $countReportQueries, $filter);
                } 
            }
            $archiveQueries = $this->getModelService()->getSignalCalendarQueries($trackerId, $formId);
            $reportTypes = $this->getModelService()->getSignalCalendarReports($archiveQueries, $trackerId, $formId, array(), $filter);
        } catch(\Exception $e) {
            $reportTypes = "";
        } catch(\PDOException $e) {
            $reportTypes = "";
        } 
        return array("data"=>$reportTypes, "footer"=>$footerTypes, "filter"=>$filter);
    }
    public function loadFiltersAction()
    {
        $response = $this->getResponse();
        $resArray = array();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            $resArray = $this->getModelService()->getSignalCalendarFilters($trackerId, $formId);
        }
        $response->setContent(\Zend\Json\Json::encode($resArray)); 
        return $response;
    }
    public function indexAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $trackerSession = $session->getSession("tracker");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0); 
            $DashboardData=$this->getModelService()->fetchDashboardData($formId, $trackerId);
            $dashboard = $DashboardData[6]['dashboard_id'];
            $adashboardId = $dashboard;//(int)$this->getEvent()->getRouteMatch()->getParam('dashboard_id', 0);
            $asId = (int)$this->getEvent()->getRouteMatch()->getParam('subaction_id', 0);
            $queryArray = array();
            if ($this->isHavingTrackerPermission($trackerId)) {
                $type = $this->params()->fromQuery('type', 'all');
                $filter = $this->params()->fromQuery('filter', 'all'); 
                $filterData = $this->getModelService()->getSignalCalendarQueries($trackerId, $formId, $type);
                $filerLabel = (!empty($filterData))?array_column($filterData, 'label')[0]:'All';
                $checkTableArray = $this->getModelService()->trackerCheckForms($trackerId, $formId);
                $formDetails = $checkTableArray['form_details'];
                $responseCode = $checkTableArray['responseCode'];
                if ($responseCode == 0) {
                    return $this->redirect()->toRoute('tracker');
                }
                $userDetails = $userSession->user_details;
                $roleId = $userDetails['group_id'];
                $roleName = $userDetails['group_name']; 
                if (isset($trackerSession->tracker_user_groups) && $roleId != 1) {
                    $trackerUserGroups = $trackerSession->tracker_user_groups;
                    $roleName = $trackerUserGroups[$trackerId]['session_group'];
                    $roleId = $trackerUserGroups[$trackerId]['session_group_id'];
                }
                $canUpdateArray = $this->getModelService()->getCanReadAndCanUpdateAccessAllWorkflow($formId, $roleId, $roleName);  
                $canDelete='No';
                $canEdit='No';
                $canRead='No';
                foreach ($canUpdateArray as $canUpdateData) {
                    if (isset($canUpdateData) && $canUpdateData['can_delete']=='Yes') {
                        $canDelete='Yes';
                    } 
                    if (isset($canUpdateData) && $canUpdateData['can_update']=='Yes') {
                        $canEdit='Yes';
                    } 
                    if (isset($canUpdateData) && $canUpdateData['can_read']=='Yes') {
                        $canRead='Yes';
                    } 
                } 
                $showWorkflowLinkArray = $this->getModelService()->showWorkflowLink($formId, $roleId, $roleName); 
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id' => $formId));
                return new ViewModel(
                    array(
                        'formId' => $formId,
                        'trackerId' => $trackerId,
                        'formDetails' => $formDetails,
                        'type' =>$type,
                        'filter' =>$filter,
                        'label' => $filerLabel,
                        'canDelete' => $canDelete,
                        'canEdit' => $canEdit,
                        'canRead' => $canRead,
                        'showWorkflowLink' => $showWorkflowLinkArray,
                        'dashboardId' =>  $adashboardId, 
                        'asId' => $asId, 
                        
                    )
                );
            } 
        }
    }
    public function fetchAllDataAction()
    {
        $response = $this->getResponse();
        $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
        $subaction_id = (int)$this->getEvent()->getRouteMatch()->getParam('subaction_id', 0);
        $type ='signalCalendar';
        $filter = $this->getEvent()->getRouteMatch()->getParam('filter', 'all');
        $allData = $this->getModelService()->fetchAllData($trackerId, $formId, $type, $filter);
        foreach ($allData['data'] as $key => $value) {
            //$allData['data'][$key]['action'] = $value['id'];
        }
        $labels = array_map('trim', explode(",", $allData['labels']));
        $names = array_map('trim', $allData['names']);
        $columnDefs=array();
        if ($subaction_id == 0) {
            if (!empty($labels)) {
                $columnDefs[0]['headerName']="Action";
                $columnDefs[0]['field']="action";
                $columnDefs[0]['lockPosition']=true; 
                $columnDefs[0]['suppressSorting']=true;
                $columnDefs[0]['suppressMenu']=true;
                $columnDefs[0]['filter']="false";
                $columnDefs[0]['width']=210;
                //$columnDefs[0]['suppressSizeToFit']=true;
                $columnDefs[0]['cellRenderer'] = "myCellRenderer";
                $columnDefs[0]['suppressColumnVirtualisation'] = false;
                foreach ($labels as $key => $arrvalue) {
                    $columnDefs[$key+1]['headerName'] = $columnDefs[$key+1]['headerTooltip'] = $arrvalue;
                    (!empty($names))?$columnDefs[$key+1]['field'] = $names[$key+1]:"";
                }
            }
        } else {
            foreach ($labels as $key => $arrvalue) {
                $columnDefs[$key]['headerTooltip'] = $columnDefs[$key]['headerName'] = $arrvalue;
                (!empty($names))?$columnDefs[$key+1]['field'] = $names[$key+1]:"";
            }
        }
        $allData['labels'] = $columnDefs;
        unset($allData['names']);
        $response->setContent(\Zend\Json\Json::encode($allData)); 
        return $response;
    }
    public function signalcalendarImportAction()
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
        $trackerId = $this->params()->fromRoute('trackerId', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $message = '';
        $config = $this->getServiceLocator()->get('config');
        $groupId = $config['group']['group_id'];
        $flag = 0;
        if ($request->isPost()) {
            $post = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());
            $fileName = $post['file']['name'];
            $fileSize = $post['file']['size'];
            $uploadedfiletype = $post['file'][0];
            $fileSize = (int)$fileSize;
            $extension = substr($fileName, strrpos($fileName, '.') + 1);
            $tempFileName = isset($post['file']['tmp_name']) ? $post['file']['tmp_name'] : '';
            $changedFileName = $fileName . '_' . time() . '.' . $extension;
            
            try {
                $tableName = 'signal_calendar_file_'.$trackerId;
                $hostName = gethostname();
                $fileInfo = pathinfo($fileName);
                $newFileName1 = $fileInfo['filename'] . "_uploadsignalcal_" . $hostName . "_" . Date('YmdHis') . "." . $fileInfo['extension'];
                $signalcal = [
                    'created_by' => $userDetails['u_name'],
                    'file_name' => $newFileName1,
                    'file_path' => $changedFileName
                ];
                if ($fileSize <= $allowedFileSize) {
                    if (count($signalcal) > 0) {
                        $InserCsvData = $this->getModelService()->insertsignalcalendardata($signalcal, $tableName, $changedFileName);
                    }
                    if ($extension == 'xls' || $extension == 'xlsx') {
                        $parent_folder = "Signal calendar";
                    }
                    if ($tempFileName != '') {
                            $fileName = $post['file']['name'];
                            $hostName = gethostname();
                            $fileInfo = pathinfo($fileName);
                            $newFileName = $newFileName1;
                            $keyname = $parent_folder . "/" . $newFileName;
                        if (file_exists($post['file']['tmp_name'])) {
                            $awsResult = $this->forward()->dispatch('Common\FileHandling\Controller\FileHandling', array('action' => 'uploadFilesToAws', 'keyname' => $keyname, 'filepath' => $post['file']['tmp_name'], 'del' => '1'));
                        }
                        $child_folder = str_replace('_', ' ', $child_folder);
                        $child_folder = ucfirst($child_folder);
                        $htmlPart = "<html>" . "<body>" . "Dear " . $userDetails['u_name'] . ", </br>
                                            <p>
                                                " .$fileName." imported successfully! </br>
                                            
                                                <h5>Confidentiality Statement:</h5>
                                                    This is a system generated correspondence. Please do not reply to this email </br>
                                                    <h5>Please contact " . $groupId . " in case you have any questions.</h5>
                                            </p>
                                            
                                            <div style='border-top:3px solid #eee;color:#999;font-size:11px;line-height:1.2'>
                                                <br>Powered by <a href='www.bioclinica.com' target='_blank' style='color: #005399;text-decoration: none;'>Bioclinica</a>. All rights reserved.<br>
                                            </div>
                                        </body>
                                     </html>";
                        $res = $this->getMailService()->sendSesEmail(''.$fileName.' Import Status', $htmlPart, array($userDetails['email']), array($groupId));
                        $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', " Signal Calendar FileImport", "", $fileName. ' imported successfully!', "Success", $trackerData['client_id']);
                        if (isset($awsResult) && is_object($awsResult)) {
                            $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "'.$fileName.'FileImport", "", $fileName, "File moved to s3", "Success", $trackerData['client_id']);
                        } else {
                            $this->getAuditService()->saveToLog("1", isset($userDetails['email']) ? $userDetails['email'] : '', "'.$fileName.'FileImport", "", $fileName, "File unable to move to s3", "Failed", $trackerData['client_id']);
                        }
                        $resultArray['result'] = 1;
                        $resultArray['message'] = $fileName.' File imported successfully!';
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
    public function downloadsignalcalendarfileAction()
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
        $filename1 = $this->params()->fromRoute('filename', 0);
        $filename = base64_decode($filename1);
        if ($filename != '') {
            $keyname = "Signal calendar/" . $filename;
            $keyname = base64_encode($keyname);
            if ($filename != '') {
                 $awsResult = $this->forward()->dispatch('Common\FileHandling\Controller\FileHandling', array('action' => 'downloadFilesFromAws', 'keyname' => $keyname, 'filename' => base64_encode($filename)));
            }
            exit;
        }
    }
    
}