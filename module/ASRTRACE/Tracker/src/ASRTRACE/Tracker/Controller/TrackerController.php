<?php
namespace ASRTRACE\Tracker\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Tracker\Form\TrackerForm;
use Report\Form\ReportForm;
use Notification\Controller\Email;
use Aws;
use Zend\Validator;
use Zend\Validator\Regex;
use Zend\I18n;
use Zend\I18n\Validator\Alnum;
use Zend\Validator\InArray;
use Session\Container\SessionContainer;
use Application\Controller\IndexController;

class TrackerController extends AbstractActionController
{
    protected $_appModelService;
    protected $_adminMapper;
    protected $_reportMapper;
    protected $_userMapper;
    protected $_modelService;
    protected $_WorkflowMgmService;
    protected $_roleMapper;
    
    public function getApplicationModelService()
    {
        if (!$this->_appModelService) {
            $sm = $this->getServiceLocator();
            $this->_appModelService = $sm->get('Application\Model\AdminMapper');
        }
        return $this->_appModelService;
    }
    public function getWorkflowMgmService()
    {
        if (!$this->_WorkflowMgmService) {
            $sm = $this->getServiceLocator();
            $this->_WorkflowMgmService = $sm->get('Tracker\Model\WorkflowMgmModule');
        }
        return $this->_WorkflowMgmService;
    }
    public function getModelService()
    {
        if (!$this->_modelService) {
            $sm = $this->getServiceLocator();
            $this->_modelService = $sm->get('Tracker\Model\Tracker');
        }
        return $this->_modelService;
    }
    public function getUserService()
    {
        if (!$this->_userMapper) {
            $sm = $this->getServiceLocator();
            $this->_userMapper = $sm->get('Tracker\Model\UserModule');
        }
        return $this->_userMapper;
    }
    public function getTrackerService()
    {
        if (!$this->_adminMapper) {
            $sm = $this->getServiceLocator();
            $this->_adminMapper = $sm->get('Tracker\Model\TrackerModule');
        }
        return $this->_adminMapper;
    }
    public function getReportService()
    {
        if (!$this->_reportMapper) {
            $sm = $this->getServiceLocator();
            $this->_reportMapper = $sm->get('Report\Model\ReportTable');
        }
        return $this->_reportMapper;
    }
    public function getRoleService()
    {
        if (!$this->_roleMapper) {
            $sm = $this->getServiceLocator();
            $this->_roleMapper = $sm->get('Role\Model\Role');
        }
        return $this->_roleMapper;
    }
    
    public function isAdmin($trackerId) 
    {
        $session = new SessionContainer();
        $userContainer= $session->getSession("user");
        $trackerContainer = $session->getSession("tracker");
        $userDetails = $userContainer->user_details;
        $roleId = isset($userDetails['group_id'])?$userDetails['group_id']:0; 
        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        $sessionGroup = $trackerUserGroups[$trackerId]['session_group'];
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            return $this->redirect()->toRoute('tracker');
        } 
        return true;
    }
    public function isSuperAdmin() 
    {
        $session = new SessionContainer();
        $userContainer= $session->getSession("user");
        $userDetails = $userContainer->user_details;
        $roleId = isset($userDetails['group_id'])?$userDetails['group_id']:0; 
        if ($roleId != 1) {
            return $this->redirect()->toRoute('tracker');
        } 
        return true;
    }
    
    public function indexAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $dashboardResults = $this->getModelService()->dashboardResults();
            $dashboardUrl = $this->getApplicationModelService()->getDashboardUrl();
            return new ViewModel(
                array('dashboardRsults' => $dashboardResults, 'dashboardUrl'=>$dashboardUrl)
            );
        }
    }

    /*
     * function to add tracker for client
     */

    public function trackerManagementAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {  
            if ($this->isSuperAdmin()) {
                $trackersList = $this->getModelService()->getAllClientsWithTracker();
            }
            return new ViewModel(array('allclients' => $trackersList));
        }
    }

    /*
     * function to add tracker for client
     */

    public function addAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $form = new TrackerForm();
            if ($this->isSuperAdmin()) {
                $form->setName('trackerForm');
                $form->get('c_hidden')->setAttribute('value', 0);
                $optionRecords = $this->getModelService()->getAllClients();
                $options = array();
                foreach ($optionRecords as $option) {
                    $options[] = array('value' => $option['client_id'], 'label' => stripslashes($option['client_name']));
                }
                $form->get('c_select_client')->setAttribute('options', $options);
            }
        }
        
        return new ViewModel(array('form' => $form));
    }

     /*
     * function to add tracker for client
     */

    public function editAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $form = new TrackerForm();
            if ($this->isSuperAdmin()) { 
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
                $clientName = "";
                $form->setName('trackerForm');
                $form->get('c_hidden')->setAttribute('value', $trackerId);
                if ($trackerId > 0) {
                    $trackerInfo = $this->getModelService()->getTrackerInformation($trackerId); 
                    $form->get('c_tracker')->setAttribute('value', isset($trackerInfo['name'])?$trackerInfo['name']:"");
                    $clientInfo = $this->getModelService()->getClientInfo(isset($trackerInfo['client_id'])?$trackerInfo['client_id']:0);
                    $clientName = $clientInfo['client_name'];
                    $clientId = $clientInfo['client_id'];
                    $this->layout()->setVariables(array('tracker_id' => $trackerId));
                }
            }
        }
        return new ViewModel(
            array('form' => $form,
            'trackerId' => $trackerId,
            'clientName' => $clientName,
            'clientId'  => $clientId
            )
        );
    }
    
    public function viewAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $form = new TrackerForm();
            if ($this->isSuperAdmin()) {
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
                if ($trackerId == 0) {
                    return $this->redirect()->toRoute('tracker');
                } else {
                    $this->layout()->setVariables(array('tracker_id' => $trackerId));
                    $trackerInfo = $this->getModelService()->getTrackerInformation($trackerId);
                    $clientInfo = $this->getModelService()->getClientInfo(isset($trackerInfo['client_id'])?$trackerInfo['client_id']:0);
                }
            }
        }
        return new ViewModel(array('form' => $form,'trackerData' => $trackerInfo, 'clientData' => $clientInfo));
    }
    
    public function saveUpdateTrackerAction() 
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        
        $dataArr = $this->getRequest()->getPost()->toArray();
        
        if (!empty($dataArr)) {
            $clientId = $dataArr['clientId'];
            $trackerName = $dataArr['trackerName'];
            $trackerId = $dataArr['trackerId'];
            $reason = $dataArr['reason'];
            $trackerOldValues = $this->getModelService()->getTrackerInformation($trackerId);
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
                return $response; 
            } else { 
                if ($this->isSuperAdmin()) {
                    $applicationController = new IndexController();
                    $userDetails = $userSession->user_details;
                    $results = $this->getModelService()->saveUpdateTracker($dataArr);
                    switch ($results['responseCode']) {
                    case 1:
                        if ($results['tracker_id'] > 0) {
                            $trackerOldValues = $this->getModelService()->getTrackerInformation($results['tracker_id']);
                            $applicationController->saveToLogFile($results['tracker_id'], $userDetails['email'], 'Add Tracker', '', "{'tracker name':'".$trackerName."', 'client_id':'".$clientId."'}", $reason, 'Success', $trackerOldValues['client_id']);
                        }
                        $this->flashMessenger()->addMessage(array('success' => 'Tracker created successfully!'));
                        break;
                    case 2:
                        if (!empty($trackerOldValues)) {
                            $applicationController->saveToLogFile($trackerId, $userDetails['email'], 'Edit Tracker', "{'tracker name':'".$trackerOldValues['name']."', 'client_id':'".$trackerOldValues['client_id']."'}", "'tracker name':'".$trackerName."', 'client_id':'".$clientId."'", $reason, 'Success', $trackerOldValues['client_id']);
                        }
                        $this->flashMessenger()->addMessage(array('success' => 'Tracker updated successfully!'));
                        break;
                    default:
                        break;
                    }
                    $response->setContent(json_encode($results));
                    return $response;
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $response->setContent('Method Not Allowed');
            return $response;
        } 
    }
    
    /*
    * Function to delete tracker :to make tracker archive
    */

    public function deleteAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession('user');
        $post = $this->getRequest()->getPost()->toArray();
        $trackerId = isset($post['trackerId'])?$post['trackerId']:0;
        $reason = isset($post['reason'])?$post['reason']:'';
        if (!empty($post)) {
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
                return $response;
            } else {
                if ($this->isSuperAdmin()) {
                    $applicationController = new IndexController();
                    $userDetails = $userSession->user_details;
                    $trackerOldValues = $this->getModelService()->getTrackerInformation($trackerId);
                    $results = $this->getModelService()->deleteTracker($trackerId);
                    switch ($results['responseCode']) {
                    case 1:
                        if (!empty($trackerOldValues)) {
                            $applicationController->saveToLogFile($trackerId, $userDetails['email'], 'Delete Tracker', "{'tracker name':'".$trackerOldValues['name']."', 'client_id':'".$trackerOldValues['client_id']."'}", "", $reason, 'Success', $trackerOldValues['client_id']);
                        }
                        $this->flashMessenger()->addMessage(array('success' => 'Tracker deleted successfully!'));
                        $response->setContent(\Zend\Json\Json::encode('deleted'));
                        break;
                    default:
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                        $response->setContent(\Zend\Json\Json::encode('error'));
                        break;
                    }
                    return $response;
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $response->setContent('Method Not Allowed');
            return $response;
        }
    }
    
    public function changeRoleAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession('user');
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            
            $trackerId = $post['tracker_id'];
            $groupId = $post['group_id'];
            $groupName = $post['group_name'];
          
            $session->updateSession('tracker', array("tracker_user_groups"=>array($trackerId =>array('session_group_id'=>$groupId, 'session_group'=>$groupName))));
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_200);
            $response->setContent('success');
            return $response;
        }
    }  
    
    public function newformAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession("tracker");
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = $trackerContainer->tracker_user_groups;
            $sessionGroup = $trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    $this->layout()->setVariables(array('tracker_id' => $trackerId));
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'tracker_id' => $trackerId,
                        )
                    );
                } else {
                    echo "Access Denied";
                }
            }
            exit;
        }
    }
    
    public function newformaddAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession("tracker");
        $applicationController = new \Application\Controller\IndexController;
        $resArr = array();
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $validator = new Alnum(array('allowWhiteSpace' => true));
        if ($post && $validator->isValid($post['form_name'])) {
            if (!isset($userContainer->u_id)) {
                foreach ($post as $key => $value) {
                    $applicationController->saveToLogFile(0, $key, $value, '', 'Add New Form', '', $trackerId, 0, 0, IP, '', 'Session Timeout:Failure');
                }
                return $this->redirect()->toRoute('home');
            } else {
                $dataArr = $post;
                $userDetails = $userContainer->user_details;
                $dataArr['tracker_id'] = $trackerId;
                $checkifexist = $this->getTrackerService()->checkFormExist($trackerId, $dataArr['form_name']);
                if ($checkifexist > 0) {
                    $applicationController->saveToLogFile(0, 'form_name', $dataArr['form_name'], '', 'Add New Form', $userDetails['u_name'], $trackerId, 0, 0, IP, '', 'Form name already exist:Failure');
                    $resArr['responseCode'] = 2;
                    $resArr['errMessage'] = 'Form name exists already.';
                } else {
                    $resArr = $this->getTrackerService()->newformadd($dataArr);
                    foreach ($post as $key => $value) {
                        $applicationController->saveToLogFile(0, $key, $value, '', 'Add New Form', $userDetails['u_name'], $trackerId, 0, 0, IP, '', 'Success');
                    }
                }
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, '', '', '', 'Add New Form', '', $trackerId, 0, 0, IP, '', 'Post data blank,Session timeout :failure');
                return $this->redirect()->toRoute('home');
            } else {
                $applicationController->saveToLogFile(0, '', '', '', 'Add New Form', '', $trackerId, 0, 0, IP, '', 'Post data blank:failure');
            }
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        echo json_encode($resArr);
        exit;
    }

    public function workflowAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('action_id', 0);
            if ($this->isAdmin($trackerId)) { 
                $checkTableArray = $this->getModelService()->trackerCheckForms($trackerId, $formId);
                $formDetails = $checkTableArray['form_details'];
                $responseCode = $checkTableArray['responseCode'];
                if ($responseCode == 0) {
                    return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                }
                $workflowArray = $this->getModelService()->trackerCheckWorkFlows($trackerId, $formId); 
            }
            $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'   => $formId));
            return new ViewModel(
                array(
                    'trackerRsults' => $this->getModelService()->trackerResults($trackerId),
                    'code_lists' => $this->getModelService()->getCodeList($trackerId),
                    'roles' => $this->getModelService()->getRoleForTracker($trackerId),
                    'action_id' => $formId,
                    'tracker_id' => $trackerId,
                    'form_details' => $formDetails,
                    'workflow_array' => $workflowArray
                    )
            ); 
        }
    }    
    public function formAction()
    {
        $userContainer = new Container('user');
        $trackerContainer = new Container('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $actionId = $this->params()->fromRoute('action_id', 0);
            $userDetails = $userContainer->user_details;

            $roleId = $userDetails['group_id'];
            $roleName = $userDetails['group_name'];
            if (isset($trackerContainer->tracker_user_groups) && $roleName != 'SuperAdmin') {
                $trackerUserGroups = $trackerContainer->tracker_user_groups;
                $roleName = $trackerUserGroups[$trackerId]['session_group'];
                $roleId = $trackerUserGroups[$trackerId]['session_group_id'];
            }
            $trackerIds = $trackerContainer->tracker_ids;
           
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                if (in_array($trackerId, $trackerIds)) {
                    if ($actionId == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $checkTableArray = $this->getTrackerService()->trackerCheckForms($trackerId, $actionId);
                    $formDetails = $checkTableArray['form_details'];
                    $responseCode = $checkTableArray['responseCode'];
                    if ($responseCode == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $canUpdateArray=$this->getWorkflowMgmService()->getCanReadAndCanUpdateAccessAllWorkflow($actionId, $roleId, $roleName);
                    
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
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => $trackerId,
                        'form_id'   => $actionId
                            )
                    );
                    $form=array();
                    return new ViewModel(
                        array(
                        'action_id' => $actionId,
                        'tracker_id' => $trackerId,
                        'form_details' => $formDetails,
                        'form' => $form,
                        'canDelete' => $canDelete,
                        'canEdit' => $canEdit,
                        'canRead' => $canRead
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }

    public function getformdataAction()
    {
        set_time_limit(0);
        $userContainer= new Container('user');
        $trackerContainer = new Container('tracker');
        $response = $this->getResponse();
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
                $trackerId = $this->params()->fromRoute('tracker_id', 0); 
                $iTotal = 0;
                $output = array();
            if (strval($trackerId) !== strval(intval($trackerId))) {
                $trackerId = 0;
            }
                $actionId = $this->params()->fromRoute('action_id', 0);
            if (strval($actionId) !== strval(intval($actionId))) {
                $actionId = 0;
            }
            $post  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
              
            if ($trackerId == 0 || $actionId == 0) {
                $output = array(
                    "sEcho" => intval(isset($post['sEcho'])?$post['sEcho']:0),
                    "aaData" => array()
                );
            } else {
                $userDetails = isset($userContainer->user_details)?$userContainer->user_details:array();
                $roleName = isset($userDetails['group_name'])?$userDetails['group_name']:'';

                if (isset($trackerContainer->tracker_user_groups) && $roleName != 'SuperAdmin') {
                    $trackerUserGroups = isset($trackerContainer->tracker_user_groups)?$trackerContainer->tracker_user_groups:array();
                    $roleName = isset($trackerUserGroups[$trackerId]['session_group'])?$trackerUserGroups[$trackerId]['session_group']:'';
                }
                $columns = array();
                $i = 0;
                $updateFlag = 0;
                $aColumns = $this->getTrackerService()->recordsCanRead($trackerId, $actionId);
              
                if ($aColumns != 'Exeption') {
                    foreach ($aColumns as $column) {
                        if (isset($column['can_update'])) {
                            if ($column['can_update'] == 'Yes' || $column['can_update'] == 'Self') {
                                $updateFlag = 1;
                            }
                        }
                            
                        $columns['search_field'][$i] = $column['search_field'];
                        $columns[$i] = $column['field_name'];
                        $i++;
                    }
                      
                    /*
                     * Paging
                     */
                    $validator = new Alnum(array('allowWhiteSpace' => true));

                    $sLimit = "";
                    if (isset($post['iDisplayStart']) && $post['iDisplayLength'] != '-1' && !empty($columns) && $validator->isValid($post['iDisplayLength'])) {
                        $offset = intval($post['iDisplayStart']);
                        $limit = intval($post['iDisplayLength']);
                        $sLimit = " LIMIT " . intval($post['iDisplayStart']) . ", " .
                                        intval($post['iDisplayLength']);
                    }
                    $sWhere = "";
                    $validatorRegex = new Regex(array('pattern' => '/^[\w\s.-]*$/'));
                  
                    if (isset($post['sSearch']) && $post['sSearch'] != "" && !empty($columns)&& $validatorRegex ->isValid($post['sSearch'])) {
                        $sWhere = "(";
                        for ($i = 0; $i < count($columns); $i++) {
                            if ($columns['search_field'][$i]==1) {
                                $sWhere .= "`".$columns[$i] . "` LIKE '%" . ($post['sSearch']) . "%' OR ";
                            }
                        }
                            
                        $sWhere = substr_replace($sWhere, "", -3);
                        $sWhere .= ')';
                       
                    }
                    unset($columns['search_field']);
                    /*
                     * Ordering
                     */
                    $sOrder = "";
                    if (isset($post['iSortCol_0']) && !empty($columns) && $validator->isValid($post['iSortCol_0'])) {
                        $sOrder = " ORDER BY  ";
                        for ($i = 0; $i < intval($post['iSortingCols']); $i++) {
                            if ($post['bSortable_' . intval($post['iSortCol_' . $i])] == "true" && $validator->isValid($post['bSortable_'. $i])) {
                                if ($post['iSortCol_0'] > 0) {
                                    $sOrder .= "`" . $columns[ ($post['iSortCol_' . $i]) - 1] . "` " .
                                                    ($post['sSortDir_' . $i] === 'asc' ? 'asc' : 'desc') . ", ";
                                } else {
                                    $sOrder .= "`" . $columns[intval($post['iSortCol_' . $i])] . "` " .
                                                    ($post['sSortDir_' . $i] === 'asc' ? 'asc' : 'desc') . ", ";
                                }
                            }
                        }

                        $sOrder = substr_replace($sOrder, "", -2);
                        if ($sOrder == " ORDER BY ") {
                            $sOrder = "";
                        }
                    }
                    try {
                        $res = $this->getTrackerService()->formRecords($trackerId, $actionId, $sLimit, $sWhere, $sOrder);
                        $iTotal = $res['tot'];
                        var_dump($res);
                        /*
                          /* Output
                         */
                        $output = array(
                            "sEcho" => intval($post['sEcho']),
                            "aaData" => array()
                        );
                        $index = 0;
                        foreach ($res['form_data'] as $key_data => $valueData) {
                            if (!empty($valueData) && !empty($columns)) {
                                $row = array();
                                $id = $valueData['id'];
                                $idFormData = '';
                                if (isset($aColumns[$index]['can_update'])) {
                                    if ($updateFlag == 1) {
                                        $idFormData = '<a aria-label="Left Align" href="/tracker/editrecord/' . $trackerId . '/' . $actionId . '/' . $id . '">';
                                        $idFormData.='<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>';
                                        $idFormData.='</a>';
                                    }
                                }
                                if ($roleName == 'SuperAdmin' || $roleName == 'Administrator') {
                                    $idFormData = '<a aria-label="Left Align" href="/tracker/editrecord/' . $trackerId . '/' . $actionId . '/' . $id . '">';
                                    $idFormData.='<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>';
                                    $idFormData.='</a>';
                                }
                                $idFormData.='<a aria-label="Left Align" href="/tracker/viewrecord/' . $trackerId . '/' . $actionId . '/' . $id . '">
                                        <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span></a>';
                                if ($roleName == 'SuperAdmin' || $roleName == 'Administrator') {
                                    $idFormData.='<a  onclick="deleterecords(' . $trackerId . ',' . $actionId . ',' . $id . ')"  href="javascript:void(0)" aria-label="Left Align">';
                                    $idFormData.='<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>';
                                    $idFormData.='</a>';
                                }
                                if (isset($aColumns[0]['can_delete'])) {
                                    if ($aColumns[0]['can_delete'] == 'Yes') {
                                        $idFormData.='<a  onclick="deleterecords(' . $trackerId . ',' . $actionId . ',' . $id . ')"  href="javascript:void(0)" aria-label="Left Align">';
                                        $idFormData.='<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>';
                                        $idFormData.='</a>';
                                    }
                                }

                                $row[] = $idFormData;
                                foreach ($columns as $key => $value) {
                                    $fieldNameData = $value;
                                    $fieldNameData = $valueData[$fieldNameData];
                                    $row[] = $fieldNameData;
                                }
                                $output['aaData'][] = $row;
                            } else {
                                $iTotal = $iTotal - 1;
                            }
                            $index++;
                        }
                    } catch (\Zend\Db\Adapter\Exception $e) {
                        $output = array(
                            "sEcho" => intval(isset($post['sEcho'])?$post['sEcho']:0),
                            "aaData" => array()
                        );
                    } catch (\Exception $e) {
                        $output = array(
                            "sEcho" => intval(isset($post['sEcho'])?$post['sEcho']:0),
                            "aaData" => array()
                        );
                    }
                } else {
                    $output = array(
                        "sEcho" => intval(isset($post['sEcho'])?$post['sEcho']:0),
                        "aaData" => array()
                    );
                }
            }
                $output['iTotalRecords'][] = $iTotal;
                $output['iTotalDisplayRecords'][] = $iTotal;
                $response->setContent(\Zend\Json\Json::encode($output['aaData']));
                return $response;
           
        }
    }

    public function settingsAction()
    {
        
        $container = new Container('msg');
        $userContainer= new Container('user');
        $trackerContainer = new Container('tracker');
        $msg = $container->messg;
        unset($container->messg);
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $actionId = $this->params()->fromRoute('action_id', 0);
            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $userDetails = $userContainer->user_details;
                $roleId = $userDetails['group_id'];
                $trackerUserGroups = @$trackerContainer->tracker_user_groups;
                $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
                if ($roleId != 1 && $sessionGroup != "Administrator") {
                    return $this->redirect()->toRoute('tracker');
                }
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId,
                        'form_id'   => $actionId
                            )
                    );
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'settingRsults' => $this->getTrackerService()->settingRsults($trackerId),
                        'action_id' => $actionId,
                        'tracker_id' => $trackerId,
                        'successMsg' =>$msg
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }

    

    public function workflowViewAction()
    {
        $userContainer= new Container('user');
        $trackerContainer = new Container('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $actionId = $this->params()->fromRoute('action_id', 0);
            $userDetails =  $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    if ($actionId == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $checkTableArray = $this->getTrackerService()->trackerCheckForms($trackerId, $actionId);
                    $formDetails = $checkTableArray['form_details'];
                    $responseCode = $checkTableArray['responseCode'];
                    if ($responseCode == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $workflowArray = $this->getTrackerService()->trackerCheckWorkFlows($trackerId, $actionId);
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId,
                        'form_id'   => $actionId
                            )
                    );
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'code_lists' => $this->getTrackerService()->fields($trackerId),
                        'action_id' => $actionId,
                        'tracker_id' => $trackerId,
                        'form_details' => $formDetails,
                        'workflow_array' => $workflowArray
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }

    public function addworkflowAction()
    {
        $userContainer= new Container('user');
        $trackerContainer = new Container('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $actionId = $this->params()->fromRoute('action_id', 0);
            $userDetails = $user_details = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }


            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    if ($actionId == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $checkTableArray = $this->getTrackerService()->trackerCheckForms($trackerId, $actionId);
                    $formDetails = $checkTableArray['form_details'];
                    $responseCode = $checkTableArray['responseCode'];
                    if ($responseCode == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $masterWorkflowArray = $this->getTrackerService()->getMasterWorkFlows();
                    $greatestSortNumber = $this->getTrackerService()->getsortlargest($actionId);
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId,
                        'form_id'   => $actionId                        
                            )
                    );
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'action_id' => $actionId,
                        'tracker_id' => $trackerId,
                        'form_details' => $formDetails,
                        'master_workflow_array' => $masterWorkflowArray,
                        'max_sort_number' => $greatestSortNumber
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }

    public function addfieldsAction()
    {
        $userContainer= new Container('user');
        $trackerContainer = new Container('tracker');
        $workflowContainer = new Container('workflow');
        $formulaFieldsContainer = new Container('formula_fields');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $actionId = $this->params()->fromRoute('action_id', 0);
            $userDetails = $user_details = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }

            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    if ($actionId == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    if (!isset($workflowContainer->wfs) || count($workflowContainer->wfs) == 0) {
                        if (isset($formulaFieldsContainer->formula_fields) && count($formulaFieldsContainer->formula_fields) > 0) {
                            return $this->redirect()->toRoute('tracker', array('action' => 'formulafields', 'tracker_id' => $trackerId, 'action_id' => $actionId));
                        }
                        return $this->redirect()->toRoute('tracker', array('action' => 'workflow', 'tracker_id' => $trackerId, 'action_id' => $actionId));
                    }
                    $sesswfs = $workflowContainer->wfs;
                    $keysArr = array_keys($sesswfs);
                    $sessKey = $keysArr[0];
                    $wfDetails = $sesswfs[$sessKey];
                    $wfName = $wfDetails['wf_name'];
                    $wfId = $wfDetails['wf_id'];
                    $masterWorkflowId = $wfDetails['master_workflow_id'];
                    $masterFieldsArray = $this->getTrackerService()->getMasterFields($masterWorkflowId);
                    $checkTableArray = $this->getTrackerService()->trackerCheckForms($trackerId, $actionId);
                    $formDetails = $checkTableArray['form_details'];
                    $responseCode = $checkTableArray['responseCode'];
                    if ($responseCode == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId,
                        'form_id'   => $actionId
                            )
                    );
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'code_lists' => $this->getTrackerService()->getCodeList($trackerId),
                        'roles' => $this->getUserService()->getRoleForTracker($trackerId),
                        'action_id' => $actionId,
                        'tracker_id' => $trackerId,
                        'form_details' => $formDetails,
                        'master_fields_array' => $masterFieldsArray,
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }

    public function formulafieldsAction()
    {
        $userContainer= new Container('user');
        $trackerContainer = new Container('tracker');
        $workflowContainer = new Container('workflow');
        $formulaFieldsContainer = new Container('formula_fields');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $actionId = $this->params()->fromRoute('action_id', 0);
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    if ($actionId == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }

                    if (!isset($formulaFieldsContainer->formula_fields) || count($formulaFieldsContainer->formula_fields) == 0) {
                        $formulaFields = $this->getTrackerService()->getformulafields($actionId);
                    } else {
                        $formulaFields = $formulaFieldsContainer->formula_fields;
                    }
                    $checkTableArray = $this->getTrackerService()->trackerCheckForms($trackerId, $actionId);
                    $formDetails = $checkTableArray['form_details'];
                    $responseCode = $checkTableArray['responseCode'];
                    if ($responseCode == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId,
                        'form_id'   => $actionId
                            )
                    );
                    $fieldsArray = $this->getTrackerService()->trackerCheckFieldsForFormula($trackerId, $actionId);
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'code_lists' => $this->getTrackerService()->getCodeList($trackerId),
                        'action_id' => $actionId,
                        'tracker_id' => $trackerId,
                        'form_details' => $formDetails,
                        'formula_fields' => $formulaFields,
                        'fields_array' => $fieldsArray,
                        'formula_list' => $this->getTrackerService()->getFormulaList(),
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }

    public function getmaxfieldAction()
    {
        
        $post  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            $dataArray = $post;
            $resArray = $this->getTrackerService()->getmaxfieldsortNumber($dataArray);
            echo json_encode($resArray);
        } else {
            echo "Access denied";
        }
        exit;
    }

    public function saveformulaAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker'); 
        $applicationController = new \Application\Controller\IndexController;
        $dataArray = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $comment = $dataArray['comment'];
        unset($dataArray['comment']);
        $dataArray['formula'] = $dataArray['formula'];
        if (!isset($userContainer->u_id)) {
            $applicationController->saveToLogFile($dataArray['field_id'], 'formula', $dataArray['formula'], '', 'Edit Formula', '', $dataArray['tracker_id'], $dataArray['action_id'], 0, IP, $comment, 'session time out:failure');
            return $this->redirect()->toRoute('home');
        } else {
            $userDetails = $userContainer->user_details;
            if ($dataArray) {
                $formulainfo = $this->getTrackerService()->getFormulaforfield($dataArray['field_id']);
                $resArray = $this->getTrackerService()->saveformula($dataArray);
                if ($formulainfo['field_id'] == $dataArray['field_id'] && $formulainfo['formula'] != $dataArray['formula']) {
                    $formulainfo['formula'] = $formulainfo['formula'];
                    $applicationController->saveToLogFile($dataArray['field_id'], 'formula', $dataArray['formula'], $formulainfo['formula'], 'Edit Formula', $userDetails['u_name'], $dataArray['tracker_id'], $dataArray['action_id'], 0, IP, $comment, 'Success');
                }
                echo json_encode($resArray);
            }
        }
        exit;
    }

    public function getfieldsbyworkflowidAction()
    {
        
        $post  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            $dataArray = $post;
            $resArray = $this->getTrackerService()->getfieldsbyworkflowid($dataArray);
            echo json_encode($resArray);
        } else {
            echo "Access denied";
        }
        exit;
    }

    public function savenewfieldsAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker'); 
        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $actionId = $this->params()->fromRoute('action_id', 0);
        $dataArray = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $comment = $dataArray['comment'];
        if ($dataArray) {
            $dataArrayfrompost['expected'] = $dataArray['expected'];
            $dataArrayfrompost['field_sort_order'] = $dataArray['field_sort_order'];
            $dataArrayfrompost['formula_id'] = $dataArray['formula_id'];
            $dataArrayfrompost['kpivalues'] = $dataArray['kpivalues'];
            if (isset($dataArray['label_names'])) {
                $dataArrayfrompost['label_names'] = $dataArray['label_names'];
            }
            $dataArrayfrompost['names'] = $dataArray['names'];
            $dataArrayfrompost['types'] = $dataArray['types'];
            $dataArrayfrompost['types'] = $dataArray['types'];
            if (!isset($userContainer->u_id)) {
                foreach ($dataArrayfrompost as $key1 => $value1) {
                    foreach ($dataArrayfrompost[$key1] as $key2 => $value2) {
                        $applicationController->saveToLogFile(0, $key1, $value2, '', 'Add Field', '', $dataArray['tracker_id'], $dataArray['action_id'], 0, IP, '', $comment, 'Session Time out:failure');
                    }
                }
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $resArray = $this->getTrackerService()->savenewFields($dataArray);
                foreach ($dataArrayfrompost as $key1 => $value1) {
                    foreach ($dataArrayfrompost[$key1] as $key2 => $value2) {
                        $applicationController->saveToLogFile(0, $key1, $value2, '', 'Add Field', $userDetails['u_name'], $dataArray['tracker_id'], $dataArray['action_id'], 0, IP, '', $comment, 'Success');
                    }
                }
                echo json_encode($resArray);
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, '', '', '', 'Add Field', '', $trackerId, $actionId, 0, IP, '', $comment, 'Session Time out,POST data blank :failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $applicationController->saveToLogFile(0, '', '', '', 'Add Field', $userDetails['u_name'], $trackerId, $actionId, 0, IP, '', $comment, 'POST data blank :failure');
            }
            echo "Access denied";
        }
        exit;
    }

    public function editfieldsAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $dataArray = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);

        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $actionId = $this->params()->fromRoute('action_id', 0);
        if ($dataArray) {
            $comment = $dataArray['comment'];
            unset($dataArray['comment']);
            $dataArrayfrompost['names'] = $dataArray['names'];
            $dataArrayfrompost['field_id_arr'] = $dataArray['field_id_arr'];
            $dataArrayfrompost['field_sort_order'] = $dataArray['field_sort_order'];
            $dataArrayfrompost['kpivalues'] = $dataArray['kpivalues'];
            $i = 0;
            foreach ($dataArrayfrompost as $key => $value) {
                $j = 0;
                foreach ($value as $k => $v) {
                    $postarray[$j][$key] = $v;
                    $j++;
                }
            }
            if (!isset($userContainer->u_id)) {
                for ($i = 0; $i < count($postarray); $i++) {
                    foreach ($postarray[$i] as $k => $v) {
                        $applicationController->saveToLogFile($postarray['names'], $k, $v, '', 'Edit Field', '', $dataArray['tracker_id'], $dataArray['action_id'], 0, IP, $comment, 'Session Timeout:Failure');
                    }
                }
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $resArrayforfields = $this->getTrackerService()->getfieldsAllInfo($dataArray['field_id_arr']);
              
                $resArray = $this->getTrackerService()->editfields($dataArray);
                for ($i = 0; $i < count($postarray); $i++) {
                    foreach ($postarray[$i] as $k => $v) {
                        if ($v != $resArrayforfields[$i][$k]) {
                            $applicationController->saveToLogFile($postarray[$i]['names'], $k, $v, $resArrayforfields[$i][$k], 'Edit Field', $userDetails['u_name'], $dataArray['tracker_id'], $dataArray['action_id'], 0, IP, $comment, 'Success');
                        }
                    }
                }
                echo json_encode($resArray);
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, '', '', '', 'Edit Field', '', $trackerId, $actionId, 0, IP, '', 'Session Time out,POST data blank :failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $applicationController->saveToLogFile(0, '', '', '', 'Edit Field', $userDetails['u_name'], $trackerId, $actionId, 0, IP, '', 'POST data blank :failure');
            }
            echo "Access denied";
        }
        exit;
    }

    public function editworkflowAction()
    {
        $dataArray = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $actionId = $this->params()->fromRoute('action_id', 0);
        $applicationController = new \Application\Controller\IndexController;
        if ($dataArray) {
            $dataArrayfrompost['workflow_name'] = $dataArray['workflow_name'];
            $dataArrayfrompost['status'] = $dataArray['status'];
            $comment = $dataArray['comment'];
            unset($dataArray['comment']);
            if (!isset($userContainer->u_id)) {
                foreach ($dataArrayfrompost as $k => $v) {
                    $applicationController->saveToLogFile($dataArray['workflow_id'], $k, $v, '', 'Edit Workflow', '', $dataArray['tracker_id'], $dataArray['form_id'], 0, IP, $comment, 'Session Timeout:Failure');
                }
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $checkifexist = $this->getTrackerService()->checkWorkflowExist($actionId, $dataArray['workflow_name'], $dataArray['workflow_id'], 'edit');
                if ($checkifexist > 0) {
                    foreach ($dataArrayfrompost as $k => $v) {
                        $applicationController->saveToLogFile($dataArray['workflow_id'], $k, $v, '', 'Edit Workflow', $userDetails['u_name'], $dataArray['tracker_id'], $dataArray['form_id'], 0, IP, $comment, 'Duplicate workflow name for same form id:failure');
                    }
                    $resArray['responseCode'] = 2;
                    $resArray['errMessage'] = 'Workflow name exists already.';
                } else {
                    $workflowinfo = $this->getTrackerService()->getworkflowinfo($dataArray['workflow_id']);
                    $resArray = $this->getTrackerService()->editworkflow($dataArray);
                    foreach ($dataArrayfrompost as $k => $v) {
                        if ($v != $workflowinfo[$k]) {
                            $applicationController->saveToLogFile($dataArray['workflow_id'], $k, $v, '', 'Edit Workflow', $userDetails['u_name'], $dataArray['tracker_id'], $dataArray['form_id'], 0, IP, $comment, 'Success');
                        }
                    }
                }
                echo json_encode($resArray);
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, '', '', '', 'Edit Workflow', '', $trackerId, $actionId, 0, IP, '', 'Session Timeout,POST data blank:failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $applicationController->saveToLogFile(0, '', '', '', 'Edit Workflow', $userDetails['u_name'], $trackerId, $actionId, 0, IP, '', 'POST data blank:failure');
            }
            echo "Access denied";
        }
        exit;
    }

    public function saveworkflowAction()
    {
        $applicationController = new \Application\Controller\IndexController;
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $dataArr = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $resArr = array();
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $actionId = $this->params()->fromRoute('action_id', 0);
        $comment = '';
        $flag = 0;
        if ($dataArr) {
            if (!isset($userContainer->u_id)) {
                $wfNames = $dataArr['wf_names'];
                $wfSortOrder = $dataArr['wf_sort_order'];
                foreach ($wfNames as $key => $value) {
                    $applicationController->saveToLogFile(0, 'workflow_name', $value, '', 'Add workflow', '', $trackerId, $actionId, 0, IP, $comment, 'Session Timeout:Failure');
                    $applicationController->saveToLogFile(0, 'sort_order', $wfSortOrder[$key], '', 'Add workflow', '', $trackerId, $actionId, 0, IP, $comment, 'Session Timeout:Failure');
                }
                return $this->redirect()->toRoute('home');
            } else {
                $error_index = $dataArr['index_of_error'];
                $userDetails = $userContainer->user_details;
                $wfNames = $dataArr['wf_names'];
                $wfSortOrder = $dataArr['wf_sort_order'];
                $resArr = array();
                foreach ($wfNames as $key => $value) {
                    $checkifexist = $this->getTrackerService()->checkWorkflowExist($actionId, $value, 0, 'add');
                    if ($checkifexist > 0) {
                        $flag = 1;
                        $applicationController->saveToLogFile(0, 'workflow_name', $value, '', 'Add workflow', $userDetails['u_name'], $trackerId, $actionId, 0, IP, $comment, 'Duplicate workflow name for same form id:failure');
                        $resArr['responseCode'] = 2;
                        $resArr['errMessage'][$error_index[$key]] = 'Workflow already exist.';
                    }
                }
                foreach ($wfNames as $key => $value) {
                    if ($flag == 0) {
                        $resArr = $this->getTrackerService()->saveworkflow($dataArr);
                        $applicationController->saveToLogFile(0, 'workflow_name', $value, '', 'Add workflow', $userDetails['u_name'], $trackerId, $actionId, 0, IP, $comment, 'Success');
                        $applicationController->saveToLogFile(0, 'sort_order', $wfSortOrder[$key], '', 'Add workflow', $userDetails['u_name'], $trackerId, $actionId, 0, IP, $comment, 'Success');
                    }
                }
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, '', '', '', 'Add workflow', '', $trackerId, $actionId, 0, IP, $comment, 'Session Timeout,Post Array is blank:Failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $applicationController->saveToLogFile(0, '', '', '', 'Add workflow', $userDetails['u_name'], $trackerId, $actionId, 0, IP, $comment, 'Post Array is blank:Failure');
                $responseCode = 0;
                $errMessage = "Access Denied";
                $resArr['responseCode'] = $responseCode;
                $resArr['errMessage'] = $errMessage;
            }
        }
        echo json_encode($resArr);
        exit;
    }

    public function updateworkflowAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $actionId = $this->params()->fromRoute('action_id', 0);
        $resArr = array();
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            $dataArr = $post;
            $comment = $dataArr['comment'];
            unset($dataArr['comment']);
            $dataArrayfrompost['sort_order'] = $dataArr['wf_sort_order'];
            $dataArrayfrompost['workflow_id'] = $dataArr['wf_id_for_sort'];
            $i = 0;
            foreach ($dataArrayfrompost as $key => $value) {
                $j = 0;
                foreach ($value as $k => $v) {
                    $postarray[$j][$key] = $v;
                    $j++;
                }
            }
            $temparray = array();
            foreach ($postarray as $key => $row) {
                $temparray[$key] = $row['workflow_id'];
            }
            array_multisort($temparray, SORT_ASC, $postarray); //Merge two arrays and sort them as numbers, in ascending order
            if (!isset($userContainer->u_id)) {
                for ($i = 0; $i < count($postarray); $i++) {
                    $applicationController->saveToLogFile($postarray[$i]['workflow_id'], 'sort_order', $postarray[$i]['sort_order'], '', 'Edit Workflow -Sort Order', '', $dataArr['tracker_id'], $dataArr['action_id'], 0, IP, $comment, 'Session Time Out:Failure');
                }
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $workflowArray = $this->getTrackerService()->getWorkFlows($dataArr['tracker_id'], $dataArr['action_id']);
                $resArr = $this->getTrackerService()->updateworkflow($dataArr);
                for ($i = 0; $i < count($postarray); $i++) {
                    if ($postarray[$i]['workflow_id'] == $workflowArray[$i]['workflow_id'] && $postarray[$i]['sort_order'] != $workflowArray[$i]['sort_order']) {
                        $applicationController->saveToLogFile($workflowArray[$i]['workflow_id'], 'sort_order', $postarray[$i]['sort_order'], $workflowArray[$i]['sort_order'], 'Edit Workflow -Sort Order', $userDetails['u_name'], $dataArr['tracker_id'], $dataArr['action_id'], 0, IP, $comment, 'Success');
                    }
                }
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, 'sort_order', '', '', 'Edit Workflow -Sort Order', '', $trackerId, $actionId, 0, IP, '', 'Session Time Out,POST array blank:failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $applicationController->saveToLogFile(0, 'sort_order', '', '', 'Edit Workflow -Sort Order', $userDetails['u_name'], $trackerId, $actionId, 0, IP, '', 'POST array blank:failure');
            }
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        echo json_encode($resArr);
        exit;
    }

    public function fieldsAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $actionId = $this->params()->fromRoute('action_id', 0);
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    if ($actionId == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $checkTableArray = $this->getTrackerService()->trackerCheckForms($trackerId, $actionId);
                    $formDetails = $checkTableArray['form_details'];
                    $responseCode = $checkTableArray['responseCode'];
                    if ($responseCode == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $fieldsAarray = $this->getTrackerService()->trackerCheckFields($trackerId, $actionId);
                    $workflowArray = $this->getTrackerService()->getWorkFlows($trackerId, $actionId);
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId,
                        'form_id'   => $actionId
                            )
                    );
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'action_id' => $actionId,
                        'tracker_id' => $trackerId,
                        'form_details' => $formDetails,
                        'fields_array' => $fieldsAarray,
                        'code_lists' => $this->getTrackerService()->getCodeList($trackerId),
                        'roles' => $this->getUserService()->getRoleForTracker($trackerId),
                        'workflow_array' => $workflowArray
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }

    public function deleteWorkflowAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $actionId = $this->params()->fromRoute('action_id', 0);
        $request = $this->getRequest();
        $container = new Container('msg');
        $response = $this->getResponse();
        $post  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $comment = $post['comment'];
        unset($post['comment']);
        if (!isset($userContainer->u_id)) {
            $applicationController->saveToLogFile($post['workflowID'], '', '', '', 'Delete Workflow', '', $post['tracker_id'], $post['form_id'], 0, IP, $comment, 'Session Timeout:Failure');
            return $this->redirect()->toRoute('home');
        } else {
            $userDetails = $userContainer->user_details;
            $container->message = 'Deleted';
            $resultset = $this->getTrackerService()->deleteWorkflow($post);
            $messages = 'Deleted';
            $this->flashMessenger()->addMessage(array('success' => 'Workflow deleted successfully!'));
            $applicationController->saveToLogFile($post['workflowID'], '', '', '', 'Delete Workflow', $userDetails['u_name'], $post['tracker_id'], $post['form_id'], 0, IP, $comment, 'Success');
        }
        if (!empty($messages)) {
            $response->setContent(\Zend\Json\Json::encode($messages));
        }
        return $response;
    }

    public function changeusersessionAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $request = $this->getRequest();
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray();
        $trackerId = $post['tracker_id'];
        $groupId = $post['group_id'];
        $groupName = $post['group_name'];
        if (!isset($userContainer->u_id)) {
            $applicationController->saveToLogFile($groupId, 'group_name', $groupName, '', 'Change role', '', $trackerId, 0, 0, IP, '', 'Session Time out:failure');
            return $this->redirect()->toRoute('home');
        } else {
            $userDetails = $userContainer->user_details;
            $applicationController->saveToLogFile($groupId, 'group_name', $groupName, $trackerContainer->tracker_user_groups[$trackerId]['session_group'], 'Change role', $userDetails['u_name'], $trackerId, 0, 0, IP, '', 'Success');
            $trackerContainer->tracker_user_groups[$trackerId]['session_group_id'] = $groupId;
            $trackerContainer->tracker_user_groups[$trackerId]['session_group'] = $groupName;
            $response = "Success";
            return $response;
        }
    }

    public function deleteFormAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $request = $this->getRequest();
        $container = new Container('msg');
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $comment = $post['comment'];
        unset($post['comment']);
        if (!isset($userContainer->u_id)) {
            $applicationController->saveToLogFile($post['form_id'], '', '', '', 'Delete Form', '', $post['tracker_id'], $post['form_id'], 0, IP, $comment, 'Session Time out:failure');
            return $this->redirect()->toRoute('home');
        } else {
            $userDetails = $userContainer->user_details;
            $response = $this->getResponse();
            $container->message = 'Deleted';
            $resultset = $this->getTrackerService()->deleteForm($post);
            $messages = 'Deleted';
            $this->flashMessenger()->addMessage(array('success' => 'Form deleted successfully!'));
            $applicationController->saveToLogFile($post['form_id'], '', '', '', 'Delete Form', $userDetails['u_name'], $post['tracker_id'], $post['form_id'], 0, IP, $comment, 'Success');
            echo $messages;exit;
        }
    }

    public function deleteFieldAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $request = $this->getRequest();
        $applicationController = new \Application\Controller\IndexController;
        $container = new Container('msg');
        $response = $this->getResponse();
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $actionId = $this->params()->fromRoute('action_id', 0);
        $fieldId = $this->params()->fromRoute('subaction_id', 0);
        $fieldId = filter_var($fieldId, FILTER_SANITIZE_STRING);
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $comment = $post['comment'];
        if (!empty($post)) {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile($post['fieldID'], '', '', '', 'Delete Field', '', $trackerId, $actionId, 0, IP, '', $comment, 'Session timeout:Failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $resultset = $this->getTrackerService()->deleteField($post);
                $messages = 'Deleted';
                $container->message = 'Deleted';
                $applicationController->saveToLogFile($post['fieldID'], '', '', '', 'Delete Field', $userDetails['u_name'], $trackerId, $actionId, 0, IP, '', $comment, 'Success');
                if (!empty($messages)) {
                    $response->setContent(\Zend\Json\Json::encode($messages));
                }
                return $response;
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile($fieldId, '', '', '', 'Delete Field', '', $trackerId, $actionId, 0, IP, '', $comment, 'Session Timeout,Post Array is blank:Failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $applicationController->saveToLogFile($fieldId, '', '', '', 'Delete Field', $userDetails['u_name'], $trackerId, $actionId, 0, IP, '', $comment, 'Post Array is blank:Failure');
                $responseCode = 0;
                $errMessage = "Access Denied";
                $resArr['responseCode'] = $responseCode;
                $resArr['errMessage'] = $errMessage;
                if (!empty($messages)) {
                    $response->setContent(\Zend\Json\Json::encode($resArr));
                }
                return $response;
            }
        }
    }

    public function newrecordAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $userDetails = $userContainer->user_details;
            $role_id = $userDetails['group_id'];
            $role_name = $userDetails['group_name'];
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $actionId = $this->params()->fromRoute('action_id', 0);
            if (isset($trackerContainer->tracker_user_groups) && $role_name != 'SuperAdmin') {
                $tracker_user_groups = $trackerContainer->tracker_user_groups;
                $role_name = $tracker_user_groups[$trackerId]['session_group'];
                $role_id = $tracker_user_groups[$trackerId]['session_group_id'];
            }
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    if ($actionId == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $getCanInsertList = $this->getTrackerService()->getCanInsertList($trackerId, $actionId);
                    if ($role_name == 'SuperAdmin' || $role_name == 'Administrator' || in_array($role_id, $getCanInsertList)) {
                        $checkTableArray = $this->getTrackerService()->trackerCheckForms($trackerId, $actionId);
                        $formDetails = $checkTableArray['form_details'];
                        $responseCode = $checkTableArray['responseCode'];
                        if ($responseCode == 0) {
                            return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                        }
                        $fieldsArray = $this->getTrackerService()->trackerGetFormFields($trackerId, $actionId);
                        $this->layout()->setVariables(
                            array(
                            'tracker_id' => @$trackerId,
                            'form_id'   => $actionId
                                )
                        );
                        $rules_info=$this->getTrackerService()->getValidationRules($actionId, $role_id);

                        $rules='';
                        $messages='';
                        $field_name='';
                        $rules_detail='';
                        $msg_detail='';
                        for ($i=0;$i<count($rules_info);$i++) {
                            $rules_start= ('"'.$rules_info[$i]["field_name"].'":{');
                            $msg_start= ('"'.$rules_info[$i]["field_name"].'":{');
                            $rules1='';
                            $messages1='';
                            for ($j=$i;$j<=$i;$j++) {
                                $ruleType=$rules_info[$j]["rule_name"];
                                switch ($ruleType) {
                                case 'required':
                                case 'url':
                                case 'email':
                                    $rules_info[$j]["value"]='true';
                                    break;
                                default:
                                    $rules_info[$j]["value"]=$rules_info[$j]["value"];
                                }
                                if ($rules_info[$j]["field_name"]!=$field_name) {
                                    $rules_detail=$rules_info[$j]["rule_name"].':'.$rules_info[$j]["value"];
                                    $msg_detail=$rules_info[$j]["rule_name"].':"'.$rules_info[$j]["message"].'"';
                                } else {
                                    $rules_detail= ($rules_detail !=''? $rules_detail.','.$rules_info[$j]["rule_name"].':'.$rules_info[$j]["value"] :$rules_info[$j]["rule_name"].':'.$rules_info[$j]["value"]);
                                    $msg_detail= ($msg_detail !=''? $msg_detail.','.$rules_info[$j]["rule_name"].':"'.$rules_info[$j]["message"].'"' :$rules_info[$j]["rule_name"].':"'.$rules_info[$j]["message"].'"');
                                }
                                $rules_end= ('}');
                                $msg_end= ('}');
                                $rules1=$rules_start.$rules_detail.$rules_end;
                                $messages1=$msg_start.$msg_detail.$msg_end;
                                $field_name=$rules_info[$j]["field_name"];
                            }
                            if (isset($rules_info[$j]["field_name"])) {
                                if ($rules_info[$j]["field_name"]!=$field_name) {
                                    $rules= ($rules !=''? $rules.','. $rules1 :$rules1);
                                    $messages= ($messages !=''? $messages.','. $messages1 :$messages1);
                                }
                            }
                            if (!isset($rules_info[$j]["field_name"])) {
                                $rules= ($rules !=''? $rules.','. $rules1 :$rules1);
                                $messages= ($messages !=''? $messages.','. $messages1 :$messages1);
                            }
                        }

                        $script=htmlentities(
                            '
	                    $("#commentForm").validate({
	                        errorElement: "div",
	                        onkeyup: function(element) {$(element).valid()},
	                        onfocusout: function(element) {$(element).valid()},
	                        onclick: function(element) {$(element).valid()},
	                        onsubmit: function(element) {$(element).valid()},
	                        onchange: function(element) {$(element).valid()},
	                        onselect: function(element) {$(element).valid()},
	                        onClose: function(element) {$(element).valid()},
	                        errorPlacement: function(error, element) {
	                           if (element.type == "checkbox") {
	                                error.appendTo(element.parent());
	                            }
	                            else {
	                                error.insertAfter(element);
	                            }
	                        },
	                    rules: {'.$rules.'},
	                    messages:{'.$messages.'}});
	                    $.validator.addMethod("regex", function(value, element, regexpr) {
	                        return regexpr.test(value);
	                    });
	                    $.validator.addMethod("depends", function(value, element,depends_text) {
	                        if ($("#"+depends_text.id).val()=="" && value=="Yes") {
	                            $("#"+element.id).val("");
	                            return false;
	                        }
	                        else {
	                            return true;
	                        }
	                    });
	                    $.validator.addMethod("dates",function(value, element) {
	                        var dateReg = /^(\d{4})\-(\d{2})\-(\d{2}) (\d{2}):(\d{2})$/;
	                        return value.match(dateReg);
	                    },
	                    "Invalid date"
	                    );
	                    $.validator.addMethod("Dates",function(value, element) {
	                        var inputDate=new Date(value);
	                        var inDate=toDate(inputDate);
	                        var dateReg = /^(\d{2})\-(\d{2})\-(\d{4})$/;
	                        return inDate.match(dateReg);
	                    },
	                    "Invalid date"
	                    );
                        $.validator.addMethod("maxDate", function(value,elemen,param) {
                            if (value=="") {
                                return true;
                            }
                           var inDate=toformatdatetime(value,"date");
                           var curDate = toformatdatetime(new Date(),"date");
                           inDate= Date.parse(inDate)/1000;    //change date time to unix time stamp
                           curDate= Date.parse(curDate)/1000;  //change date time to unix time stamp
                           if (inDate <= curDate)
                           return true;
                           return false;
                        },"Invalid DateTime!");
                        $.validator.addMethod("maxDateTime", function(value, elemen,param) {
                           //var inputDate=new Date(value);
                           //var inDate=toDateTime(inputDate);
                           var inDate=toformatdatetime(value,"datetime");
                           //var curDate = moment().add(param, "day").format("YYYY-MM-DD HH:mm");
                           var curDate = toformatdatetime(new Date(),"datetime");
                           inDate= Date.parse(inDate)/1000;    //change date time to unix time stamp
                           curDate= Date.parse(curDate)/1000;  //change date time to unix time stamp
                           if (inDate <= curDate)
                           return true;
                           return false;
                        },"Invalid DateTime!");

                        $.validator.addMethod("minDateTime", function(value, elemen,param) {
                           var inputDate=new Date(value);
                           var inDate=toDateTime(inputDate);
                           var curDate = moment().subtract(param, "day").format("YYYY-MM-DD HH:mm");
                           if (inDate <= curDate)
                           return true;
                           return false;
                        },"Invalid DateTime!"); '
                        );

                        return new ViewModel(
                            array(
                            'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                            'group_code_list' => $this->getTrackerService()->getCodeListGroup($trackerId),
                            'date_time_formats' => $this->getTrackerService()->getDateTimeFormats($trackerId),
                            'action_id' => $actionId,
                            'tracker_id' => $trackerId,
                            'form_details' => $formDetails,
                            'fields_array_val' => $fieldsArray,
                            'validation_script'=>$script
                            )
                        );
                    } else {
                        return $this->redirect()->toRoute('tracker', array('action' => 'form', 'tracker_id' => $trackerId, 'action_id' => $actionId));
                    }
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }

    public function accessSettingsActionOld()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    $result = $this->getTrackerService()->getWorkflowRoleForForms($trackerId);
                    $result[0] = $this->sortArrOfObj($result[0], 'workflow_id');
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId
                            )
                    );
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'users' => $this->getTrackerService()->trackerUsers($trackerId),
                        'tracker_id' => @$trackerId,
                        'resultset' => $result,
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
    }

    public function saveaccesssettingAction()
    {
        $response = $this->getResponse();           
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $dataArr = $this->getRequest()->getPost()->toArray();   
            $applicationController = new \Application\Controller\IndexController;
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $actionId = $this->params()->fromRoute('action_id', 0);
            $responseCode = 0;
            $errMessage = "";
            $resArr = array();
            if ($dataArr) {
                $comment = $dataArr['comment'];
                unset($dataArr['comment']);
                $datapostarray['can_insert'] = $dataArr['can_insert'];
                $datapostarray['can_delete'] = $dataArr['can_delete'];
                if (!isset($userContainer->u_id)) {
                    foreach ($datapostarray as $key => $value) {
                        $applicationController->saveToLogFile($dataArr['role_id'], $key, $datapostarray[$key], '', 'Access Setting-For insert and delete', '', $trackerId, $actionId, 0, IP, $comment, 'Session timeout:Failure');
                    }
                    return $this->redirect()->toRoute('home');
                } else {
                    $userDetails = $userContainer->user_details;
                    $accessArr = $this->getTrackerService()->getaccesssetting($dataArr);
                    $resArr = $this->getTrackerService()->saveaccesssetting($dataArr);
                    foreach ($datapostarray as $key => $value) {
                        if (!empty($accessArr)) {
                            if ($value != $accessArr[$key]) {
                                $applicationController->saveToLogFile($dataArr['role_id'], $key, $datapostarray[$key], $accessArr[$key], 'Access Setting-For insert and delete', $userDetails['u_name'], $trackerId, $actionId, 0, IP, $comment, 'Success');
                            }
                        } else {
                            $applicationController->saveToLogFile($dataArr['role_id'], $key, $datapostarray[$key], '', 'Access Setting-For insert and delete', $userDetails['u_name'], $trackerId, $actionId, 0, IP, $comment, 'Success');
                        }
                    }
                }
            } else {
                if (!isset($userContainer->u_id)) {
                    $applicationController->saveToLogFile(0, '', '', '', 'Access Setting-For insert and delete', '', $trackerId, $actionId, 0, IP, '', 'Session Timeout,Post Array is blank:Failure');
                    return $this->redirect()->toRoute('home');
                } else {
                    $userDetails = $userContainer->user_details;
                    $applicationController->saveToLogFile(0, '', '', '', 'Access Setting-For insert and delete', $userDetails['u_name'], $trackerId, $actionId, 0, IP, '', 'Post Array is blank:Failure');
                }
                $responseCode = 0;
                $errMessage = "Access Denied";
                $resArr['responseCode'] = $responseCode;
                $resArr['errMessage'] = $errMessage;
            }
            return $response->setContent(\Zend\Json\Json::encode($resArr));
        }
    }
    public function addWorkflowRoleIdAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray();
        $resultset = $this->getTrackerService()->addWorkflowRoleId($post);
        echo $resultset[0]['result'];
        die;
    }

    public function saverecordAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $dataArr = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
       
        if ($dataArr) {
            $request = $this->getRequest();
            $post = array_merge_recursive($request->getPost()->toArray(), $request->getFiles()->toArray());
            $tracker_id = $this->params()->fromRoute('tracker_id', 0);
            $action_id = $this->params()->fromRoute('action_id', 0);
           
            $folderPath = "attachment/attach_".$tracker_id.'_'.$action_id;
            if (!file_exists($folderPath)) {
                mkdir("$folderPath", 0777, true);
            }
            $fileUrl='';

            $filename=array();
            // $filepath should be absolute path to a file on disk
            $uploadToAws= new AwsController();
            if (is_array($_FILES)) {
                $fileindex=0;
                foreach ($_FILES as $fileName=>$fileDetails) {
                    if (!empty($fileDetails['name'])) {
                        $dataArr[$fileName]=$fileDetails['name'];
                        $file = pathinfo($fileDetails['name']);
                        $filename[]= $file['filename'].'_'.time().'.'.$file['extension'];
                        $keyname = $folderPath.'/'.$filename[$fileindex];
                        $filepath = $fileDetails['tmp_name'];
                        $this->forward()->dispatch(
                            'Tracker\Controller\Aws',
                            array(
                            'action' => 'uploadFilesToAws',
                            'keyname' => $keyname,
                            'filepath' => $filepath,
                            )
                        );
                    }
                    $fileindex++;
                }
            }
            if (!isset($userContainer->u_id)) {
                foreach ($dataArr as $key => $value) {
                    if (is_array($value)) {
                        $optionsArray = array();
                        foreach ($value as $keyCheck => $valueCheck) {
                            $valuesCheckboxValues = json_decode($valueCheck, true);
                            $optionsArray[] = $valuesCheckboxValues['option'];
                        }
                        $values = implode(',', $optionsArray);
                        $applicationController->saveToLogFile(0, $key, $values, '', 'Add Record', '', $tracker_id, $action_id, 0, IP, '', 'session time out:failure');
                    } else {
                        $value = addslashes($value);
                        $applicationController->saveToLogFile(0, $key, $value, '', 'Add Record', '', $tracker_id, $action_id, 0, IP, '', 'session time out:failure');
                    }
                }
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $resArr = $this->getTrackerService()->saverecord($dataArr, $tracker_id, $action_id, $filename);
                foreach ($dataArr as $key => $value) {
                    if (is_array($value)) {
                        $optionsArray = array();
                        foreach ($value as $keyCheck => $valueCheck) {
                            $valueCheck=stripslashes(html_entity_decode($valueCheck));
                            $valuesCheckboxValues = json_decode($valueCheck, true);

                            $optionsArray[] = $valuesCheckboxValues['option'];
                        }
                        $values = implode(',', $optionsArray);
                        $applicationController->saveToLogFile(0, $key, $values, '', 'Add record', $userDetails['u_name'], $tracker_id, $action_id, 0, IP, '', 'Success');
                    } else {
                        $value = addslashes($value);
                        $applicationController->saveToLogFile(0, $key, $value, '', 'Add record', $userDetails['u_name'], $tracker_id, $action_id, 0, IP, '', 'Success');
                    }
                }
                return $this->redirect()->toRoute('tracker', array('action' => 'form', 'tracker_id' => $tracker_id, 'action_id' => $action_id));
            }
        } else {
            $userDetails = $userContainer->user_details;
            $applicationController->saveToLogFile(0, '', '', '', 'Add Record', $userDetails['u_name'], $tracker_id, $action_id, 0, IP, '', 'Postdata is blank:failure');
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        exit;
        exit;
    }

    public function saveeditrecordAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $tracker_id = $this->params()->fromRoute('tracker_id', 0);
        $action_id = $this->params()->fromRoute('action_id', 0);
        $subaction_id = $this->params()->fromRoute('subaction_id', 0);
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            $dataArr = $post;
            $comment = $dataArr['addcomment'];
            unset($dataArr['addcomment']);
            $referal = $dataArr['referal'];
            $respJson = 0;
            if (!isset($userContainer->u_id)) {
                foreach ($dataArr as $key => $value) {
                    if (is_array($value)) {
                        $optionsArray = array();
                        foreach ($value as $keyCheck => $valueCheck) {
                            $valuesCheckboxValues = json_decode($valueCheck, true);
                            $optionsArray[] = $valuesCheckboxValues['option'];
                        }
                        $values = implode(',', $optionsArray);
                        $applicationController->saveToLogFile($subaction_id, $key, $values, '', 'Edit Record', '', $tracker_id, $action_id, 0, IP, $comment, 'session time out:failure');
                    } else {
                        $value = addslashes($value);
                        $applicationController->saveToLogFile($subaction_id, $key, $value, '', 'Edit Record', '', $tracker_id, $action_id, 0, IP, $comment, 'session time out:failure');
                    }
                }
                return $this->redirect()->toRoute('home');
            } else {
                $folderPath = "attachment/attach_".$tracker_id.'_'.$action_id;
                if (!file_exists($folderPath)) {
                    mkdir("$folderPath", 0777, true);
                }
                $filename=array();
                // $filepath should be absolute path to a file on disk
                $uploadToAws= new AwsController();
                if (is_array($_FILES)) {
                    foreach ($_FILES as $fileName=>$fileDetails) {
                        if (!empty($fileDetails['name'])) {
                            $dataArr[$fileName]=$fileDetails['name'];
                            $file = pathinfo($fileDetails['name']);

                            $filename[$fileName]= $file['filename'].'_'.time().'.'.$file['extension'];
                            $keyname = $folderPath.'/'.$filename[$fileName];
                            $filepath = $fileDetails['tmp_name'];
                            $this->forward()->dispatch(
                                'Tracker\Controller\Aws',
                                array(
                                'action' => 'uploadFilesToAws',
                                'keyname' => $keyname,
                                'filepath' => $filepath,
                                )
                            );
                        } else {
                            $filename[] =$dataArr['hidden_file_'.$fileName];
                        }
                    }
                }
                $userDetails = $userContainer->user_details;
                $recordArray = $this->getTrackerService()->formRecordsByID($tracker_id, $action_id, $subaction_id);
                $resArr = $this->getTrackerService()->saveeditrecord($dataArr, $tracker_id, $action_id, $subaction_id, $filename);
                foreach ($dataArr as $key => $value) {
                    if (isset($recordArray[$key])) {
                        if ($recordArray[$key] != $dataArr[$key]) {
                            if (is_array($value)) {
                                $optionsArray = array();
                                foreach ($value as $keyCheck => $valueCheck) {
                                    $valueCheck=stripslashes(html_entity_decode($valueCheck));
                                    $valuesCheckboxValues = json_decode($valueCheck, true);
                                    $optionsArray[] = $valuesCheckboxValues['option'];
                                }
                                $values = implode(',', $optionsArray);
                                $applicationController->saveToLogFile($subaction_id, $key, $values, $recordArray[$key], 'Record Edited', $userDetails['u_name'], $tracker_id, $action_id, $subaction_id, IP, $comment, 'Success');
                            } else {
                                $value = addslashes($value);
                                $applicationController->saveToLogFile($subaction_id, $key, $value, $recordArray[$key], 'Record Edited', $userDetails['u_name'], $tracker_id, $action_id, $subaction_id, IP, $comment, 'Success');
                            }
                        }
                    }
                }
                foreach ($resArr['templateids'] as $templateids) {
                    $this->forward()->dispatch(
                        'Notification\Controller\Email',
                        array(
                        'action' => 'sendemail',
                        'param1' => $templateids,
                        'param2' => 'form_' . $tracker_id . '_' . $action_id,
                        'param3' => $subaction_id,
                        )
                    );
                }
                if (isset($referal) && !empty($referal)) {
                    return $this->redirect()->toUrl($referal);
                } else {
                    return $this->redirect()->toRoute('tracker', array('action' => 'form', 'tracker_id' => $tracker_id, 'action_id' => $action_id));
                }
            }
        } else {
            $userDetails = $userContainer->user_details;
            $applicationController->saveToLogFile($subaction_id, '', '', '', 'Edit Record', $userDetails['u_name'], $tracker_id, $action_id, 0, IP, '', 'Postdata is blank:failure');
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        exit;
    }
    public function downloadFileAction($filename, $tracker_id, $action_id)
    {
        $file = '/public/attachment/attach_'.$tracker_id.'_'.$action_id.'/'.$filename;
        $response = new \Zend\Http\Response\Stream();
        $response->setStream(fopen($file, 'r'));
        $response->setStatusCode(200);
        $response->setStreamName(basename($file));
        $headers = new \Zend\Http\Headers();
        $headers->addHeaders(
            array(
                'Content-Disposition' => 'attachment; filename="' . basename($file) .'"',
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => filesize($file),
                'Expires' => '@0', // @0, because zf2 parses date as string to \DateTime() object
                'Cache-Control' => 'must-revalidate',
                'Pragma' => 'public'
            )
        );
        $response->setHeaders($headers);
        return $response;
    }

    public function curlPost($url, $dataArr)
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $dataArr
            )
        );
        $respJson = curl_exec($curl);
        curl_close($curl);
        return $respJson;
    }

    public function codelistAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $actionId = $this->params()->fromRoute('action_id', 0);
            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $userDetails = $userContainer->user_details;
                $roleId = $userDetails['group_id'];
                $trackerUserGroups = @$trackerContainer->tracker_user_groups;
                $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];


                if ($roleId != 1 && $sessionGroup != "Administrator") {
                    return $this->redirect()->toRoute('tracker');
                }

                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId,
                        'form_id'   => $actionId
                            )
                    );
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'settingRsults' => $this->getTrackerService()->settingRsults($trackerId),
                        'action_id' => $actionId,
                        'tracker_id' => $trackerId,
                        'code_lists' => $this->getTrackerService()->getCodeList($trackerId),
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }

    public function addnewcodelistAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $dataArr = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($dataArr) {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, 'new_code_list', $dataArr['new_code_list'], '', 'Codelist creation ', '', $dataArr['tracker_id'], 0, 0, IP, 'Sessoin time out', 'Failure');
                $responseCode = 0;
                $errMessage = "Access Denied";
                $resArr['responseCode'] = $responseCode;
                $resArr['errMessage'] = $errMessage;
            } else {
                $userDetails = $userContainer->user_details;
                $resArr = $this->getTrackerService()->addnewcodelist($dataArr);
                if ($resArr['responseCode'] == 0) {
                    $applicationController->saveToLogFile(0, 'new_code_list', $dataArr['new_code_list'], '', $resArr['errMessage'], $userDetails['u_name'], $dataArr['tracker_id'], 0, 0, IP, '', 'Failure');
                } else {
                    $applicationController->saveToLogFile(0, 'new_code_list', $dataArr['new_code_list'], '', 'New CodeList Created', $userDetails['u_name'], $dataArr['tracker_id'], 0, 0, IP, '', 'success');
                }
            }
        } else {
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
            $applicationController->saveToLogFile(0, 'new_code_list', '', '', $resArr['errMessage'], '', 0, 0, 0, IP, 'Access Denied', 'Failure');
        }
        echo json_encode($resArr);
        exit;
    }

    public function editcodelistAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            $dataArr = $post;
            $comment = $dataArr['comment'];
            unset($dataArr['comment']);

            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, 'edit_code_list', $dataArr['edit_code_list'], '', 'Codelist Edition', '', $dataArr['tracker_id'], 0, 0, IP, 'Sessoin time out', 'Failure');
                $responseCode = 0;
                $errMessage = "Access Denied";
                $resArr['responseCode'] = $responseCode;
                $resArr['errMessage'] = $errMessage;
            } else {
                $userDetails = $userContainer->user_details;
                $resultcode = $this->getTrackerService()->getcodelistinfo($dataArr['edit_code_list_id']);

                if ($resultcode[0]['code_list_name'] != $dataArr['edit_code_list']) {
                    $applicationController->saveToLogFile($dataArr['edit_code_list_id'], 'edit_code_list', $dataArr['edit_code_list'], $resultcode[0]['code_list_name'], 'CodeList Edited', $userDetails['u_name'], $dataArr['tracker_id'], 0, 0, IP, $comment, 'success');
                }
                $resArr = $this->getTrackerService()->editcodelist($dataArr);
            }
        } else {
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
            $applicationController->saveToLogFile(0, 'edit_code_list', '', '', $resArr['errMessage'], '', 0, 0, 0, IP, 'Access Denied', 'Failure');
        }
        echo json_encode($resArr);
        exit;
    }

    public function addoptionscodesAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            $dataArr = $post;
            $trackerid = $dataArr['tracker_id'];
            if (!isset($userContainer->u_id)) {
                unset($dataArr['tracker_id']);
                unset($dataArr['code_list_id']);
                foreach ($dataArr as $key => $value) {
                    foreach ($value as $key1 => $value1) {
                        $applicationController->saveToLogFile(0, $key, $value1, '', 'New Option creation ', '', $trackerid, 0, 0, IP, 'Sessoin time out', 'Failure');
                    }
                }
                $responseCode = 0;
                $errMessage = "Access Denied";
                $resArr['responseCode'] = $responseCode;
                $resArr['errMessage'] = $errMessage;
            } else {
                $userDetails = $userContainer->user_details;
                $resArr = $this->getTrackerService()->addoptionscodes($dataArr);
                if ($resArr['responseCode'] == 0) {
                    $applicationController->saveToLogFile(0, 'new_code_list', $dataArr['names']['0'], '', $resArr['errMessage'], $userDetails['u_name'], $dataArr['tracker_id'], 0, 0, IP, '', 'Failure');
                } else {
                    unset($dataArr['tracker_id']);
                    unset($dataArr['code_list_id']);
                    foreach ($dataArr as $key => $value) {
                        foreach ($value as $key1 => $value1) {
                            $applicationController->saveToLogFile(0, $key, $value1, '', 'New Option Created', $userDetails['u_name'], $trackerid, 0, 0, IP, '', 'Success');
                        }
                    }
                }
            }
        } else {
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
            $applicationController->saveToLogFile(0, 'new_option', '', '', $resArr['errMessage'], '', 0, 0, 0, IP, 'Access Denied', 'Failure');
        }
        echo json_encode($resArr);
        exit;
    }

    public function editoptionscodesAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            $dataArr = $post;
            $comment = $dataArr['comment'];
            unset($dataArr['comment']);
            $postarray = $dataArr;
            unset($postarray['code_list_id']);
            unset($postarray['tracker_id']);
            for ($i = 0; $i <= sizeof($postarray['kpi']); $i++) {
                foreach ($postarray as $key => $value) {
                    for ($j = 0; $j < sizeof($postarray['kpi']); $j++) {
                        $newpostarray[$j][$key] = $value[$j];
                    }
                }
            }
            if (!isset($userContainer->u_id)) {
                unset($dataArr['tracker_id']);
                unset($dataArr['code_list_id']);
                foreach ($dataArr as $key => $value) {
                    foreach ($value as $key1 => $value1) {
                        $applicationController->saveToLogFile(0, 'edit_code_list', $dataArr['edit_code_list'], '', 'Codelist Edition', '', $dataArr['tracker_id'], 0, 0, IP, 'Sessoin time out', 'Failure');
                    }
                }
                $responseCode = 0;
                $errMessage = "Access Denied";
                $resArr['responseCode'] = $responseCode;
                $resArr['errMessage'] = $errMessage;
            } else {
                $userDetails = $userContainer->user_details;
                $resultoption = $this->getTrackerService()->getoptioninfo($dataArr['code_list_id']);
                foreach ($newpostarray as $key => $value) {
                    foreach ($value as $key1 => $value1) {
                        if ($value1 != $resultoption[$key][$key1]) {
                            $applicationController->saveToLogFile($dataArr['code_list_id'], $key, $value1, $resultoption[$key][$key1], 'option Edition', $userDetails['u_name'], $dataArr['tracker_id'], 0, 0, IP, $comment, 'Success');
                        }
                    }
                }
                $resArr = $this->getTrackerService()->editoptionscodes($dataArr);
            }
        } else {
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
            $applicationController->saveToLogFile(0, 'edit_option', '', '', $resArr['errMessage'], '', 0, 0, 0, IP, 'Access Denied', 'Failure');
        }
        echo json_encode($resArr);
        exit;
    }

    public function deletecodelistoptionAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $userDetails = $userContainer->user_details;
        $applicationController = new \Application\Controller\IndexController;
        $request = $this->getRequest();
        $container = new Container('msg');
        $response = $this->getResponse();
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $comment = $post['comment'];
        unset($post['comment']);
        $container->message = 'Deleted';
        $resultset = $this->getTrackerService()->deletecodelistoption($post);
        if (!isset($userContainer->u_id)) {
            $applicationController->saveToLogFile($post['option_id'], '', '', '', 'Codelist Deleted', $userDetails['u_name'], $post['tracker_id'], 0, 0, IP, $comment, 'Failure');
        } else {
            $applicationController->saveToLogFile($post['option_id'], '', '', '', 'Codelist Deleted', $userDetails['u_name'], $post['tracker_id'], 0, 0, IP, $comment, 'success');
        }
        $messages = 'Deleted';
        if (!empty($messages)) {
            $response->setContent(\Zend\Json\Json::encode($messages));
        }
        return $response;
    }

    public function editfieldbyidAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $actionId = $this->params()->fromRoute('action_id', 0);
        $dataArr = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($dataArr) {
            $comment = $dataArr['comment'];
            unset($dataArr['comment']);
            $fieldarray = array();
            $fieldarray[0] = $dataArr['f_id'];
            $dataArrayfrompost['expected'] = $dataArr['code_list_id'];
            $dataArrayfrompost['names'] = $dataArr['fieldName'];
            $dataArrayfrompost['field_id_arr'] = $dataArr['f_id'];
            $dataArrayfrompost['kpivalues'] = $dataArr['kpi'];
            $dataArrayfrompost['types'] = $dataArr['fieldType'];
            if (!isset($userContainer->u_id)) {
                foreach ($dataArrayfrompost as $k => $v) {
                    $applicationController->saveToLogFile($dataArr['f_id'], $k, $v, '', 'Edit Field', '', $dataArr['tracker_id'], $dataArr['form_id'], 0, IP, $comment, 'Session Timeout:Failure');
                }
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $resArrayforfields = $this->getTrackerService()->getfieldsInfo($fieldarray);
                $resArr = $this->getTrackerService()->editfieldbyid($dataArr);
                foreach ($dataArrayfrompost as $k => $v) {
                    if ($dataArrayfrompost[$k] != $resArrayforfields[0][$k]) {
                        $applicationController->saveToLogFile($dataArr['f_id'], $k, $v, $resArrayforfields[0][$k], 'Edit Field', $userDetails['u_name'], $dataArr['tracker_id'], $dataArr['form_id'], 0, IP, $comment, 'Success');
                    }
                }
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, '', '', '', 'Edit Field', '', $trackerId, $actionId, 0, IP, '', 'Session Time out,POST data blank :failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $applicationController->saveToLogFile(0, '', '', '', 'Edit Field', $userDetails['u_name'], $trackerId, $actionId, 0, IP, '', 'POST data blank :failure');
            }
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        echo json_encode($resArr);
        exit;
    }

    public function getoptionsbycodelistAction()
    {
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            $dataArr = $post;
            $resArr = $this->getTrackerService()->getoptionsbycodelist($dataArr);
        } else {
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        echo json_encode($resArr);
        exit;
    }

    // public function deleterecordAction()
    // {
    //     $session = new SessionContainer();
    //     $userContainer = $session->getSession("user");
    //     $trackerContainer = $session->getSession('tracker');
    //     if (!isset($userContainer->u_id)) {
    //         return $this->redirect()->toRoute('home');
    //     } else {
    //         $trackerId = $this->params()->fromRoute('tracker_id', 0);
    //         $actionId = $this->params()->fromRoute('action_id', 0);
    //         $subactionId = $this->params()->fromRoute('subaction_id', 0);
    //         if ($trackerId == 0) {
    //             return $this->redirect()->toRoute('tracker');
    //         } else {
    //         }
    //     }
    // }

    public function viewrecordAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user'); 
        $trackerContainer = $session->getSession('tracker');  
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);

            $userDetails = $userContainer->user_details;
            $role_id = $userDetails['group_id'];
            $role_name = $userDetails['group_name'];
            
            if (isset($trackerContainer->tracker_user_groups) && $role_name != 'SuperAdmin') {
                $tracker_user_groups = $trackerContainer->tracker_user_groups;
                $role_name = $tracker_user_groups[$trackerId]['session_group'];
                $role_id = $tracker_user_groups[$trackerId]['session_group_id'];
            }
            $actionId = $this->params()->fromRoute('action_id', 0);
            $subactionId = $this->params()->fromRoute('subaction_id', 0);
            $userDetails = $userContainer->user_details;

            $trackerIds = $trackerContainer->tracker_ids;
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    if ($actionId == 0 || $subactionId == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    if ($subactionId == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId, 'action_id' => $actionId));
                    }
                    $checkTableArray = $this->getTrackerService()->trackerCheckForms($trackerId, $actionId, $subactionId);
                    $formDetails = $checkTableArray['form_details'];
                    $responseCode = $checkTableArray['responseCode'];
                    if ($responseCode == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $workflowArray = $this->getTrackerService()->trackerCheckWorkFlows($trackerId, $actionId, $role_id, $role_name);
                   
                    $recordData = $this->getTrackerService()->formRecords($trackerId, $actionId, '', '', '', $subactionId);
                    $fieldsArray = $this->getTrackerService()->allFieldsOfForm($trackerId, $actionId);

                    $canReadArray=$this->getWorkflowMgmService()->getCanReadAndCanUpdateAccessAllWorkflow($actionId, $role_id, $role_name);
                    $configData=$this->getWorkflowMgmService()->getconfigDataByForm($actionId);
                    $configData = array_column($configData, 'config_value', 'config_key');
                    
                    $fieldDetails=array();
                    foreach ($fieldsArray as $key => $value) {
                        $fieldDetails[$value['workflow_name']][$value['field_name']]['field_name']=$value['field_name'];
                        $fieldDetails[$value['workflow_name']][$value['field_name']]['fieldlabel']=$value['fieldlabel'];
                        $fieldDetails[$value['workflow_name']][$value['field_name']]['field_type']=$value['field_type'];

                        $fieldDetails[$value['workflow_name']][$value['field_name']]['dateFormat'] = isset($configData['dateFormat'])?$configData['dateFormat']:"";
                        $fieldDetails[$value['workflow_name']][$value['field_name']]['dateTimeFormat'] = isset($configData['dateTimeFormat'])?$configData['dateTimeFormat']:"";
                        $fieldDetails[$value['workflow_name']][$value['field_name']]['fieldValue']=isset($recordData['form_data'][0][$value['field_name']])?$recordData['form_data'][0][$value['field_name']]:"";
                        foreach ($canReadArray as $canRead) {
                            if (isset($canRead['workflow_id']) && $canRead['workflow_id']==$value['workflow_id']) {
                                $fieldDetails[$value['workflow_name']]['can_read']=$canRead['can_read'];
                            } else if ($role_name == 'SuperAdmin'||$role_name == 'Administrator') {
                                $fieldDetails[$value['workflow_name']]['can_read']="Yes";
                            }
                        }
                                            
                        if ($value['code_list_id']!=0) {
                            $optionArray=array();
                            $optionArray['optionValue']=$value['optionValue'];
                            $optionArray['optionLabel']=$value['optionLabel'];
                            $optionArray['kpi']=$value['kpi'];
                            $fieldDetails[$value['workflow_name']][$value['field_name']]['options'][]=$optionArray;
                        }
                        if ($value['field_type']=='Check Box') {
                            $fieldDetails[$value['workflow_name']][$value['field_name']]['comment']=isset($recordData['form_data'][0][$value['field_name']])?$recordData['form_data'][0]['comment_checkbox_'.$value['field_name']]:"";
                        }
                    }
                    
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId,
                        'form_id'   => $actionId
                            )
                    );
                    // $queryString = filter_input(INPUT_SERVER, 'QUERY_STRING');
                    // parse_str(filter_input(INPUT_SERVER, 'QUERY_STRING'), $queryArray);
                    // $type = isset($queryArray['type'])?trim($queryArray['type']):"all";
                    // $filter = isset($queryArray['filter'])?trim($queryArray['filter']):"all";
                    // $queryString='?type='.$type.'&filter='.$filter;
                    $type = $this->getEvent()->getRouteMatch()->getParam('type', 'all');
                    $filter = $this->getEvent()->getRouteMatch()->getParam('filter', 'all');
                    return new ViewModel(
                        array(
                        'action_id' => $actionId,
                        'tracker_id' => $trackerId,
                        'form_details' => $formDetails,
                        'fields_array_val' => $fieldsArray,
                        'record_id' => $subactionId,
                        'user_name' => $userDetails['u_name'],
                        'workflow' => $workflowArray,
                        'field_details' => $fieldDetails,
                        'filter' => $filter,
                        'type' => $type,
                        )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }

    public function editrecordAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user'); 
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = (int)$this->params()->fromRoute('tracker_id', 0);
            $userDetails = $userContainer->user_details;
            $role_id = (int)$userDetails['group_id'];
            $role_name = $userDetails['group_name'];
            
            if (isset($trackerContainer->tracker_user_groups) && $role_name != 'SuperAdmin') {
                $tracker_user_groups = $trackerContainer->tracker_user_groups;
                $role_name = $tracker_user_groups[$trackerId]['session_group'];
                $role_id = $tracker_user_groups[$trackerId]['session_group_id'];
            }
           
            $actionId = (int)$this->params()->fromRoute('action_id', 0); //echo $actionId; die;
            $subactionId = $this->params()->fromRoute('subaction_id', 0);
            $trackerIds = $trackerContainer->tracker_ids;

            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    if ($actionId == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    if ($subactionId == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId, 'action_id' => $actionId));
                    }
                    $checkTableArray = $this->getTrackerService()->trackerCheckForms($trackerId, $actionId);
                    
                    $formDetails = $checkTableArray['form_details'];
                    $responseCode = $checkTableArray['responseCode'];
                    if ($responseCode == 0) {
                        return $this->redirect()->toRoute('tracker', array('action' => 'view', 'tracker_id' => $trackerId));
                    }
                    $fieldsArray = $this->getTrackerService()->recordsCanEdit($trackerId, $actionId);
                    
                    $workflowArray = $this->getTrackerService()->trackerCheckWorkFlows($trackerId, $actionId, $role_id, $role_name);
                    
                    $checkHolidayList = $this->getTrackerService()->checkHolidayList($trackerId);
                    
                    $holidayList = array();
                    foreach ($checkHolidayList as $holidays) {
                        $sDate = date($holidays['start_date']);
                        $eDate = date($holidays['end_date']);
                        if (strtotime($eDate) > strtotime($sDate)) {
                            $holidayList[] = date("d-M-Y", strtotime($sDate));
                            do {
                                $sDate = date('Y-m-d', strtotime($sDate .' +1 day'));
                                $holidayList[] = date("d-M-Y", strtotime($sDate));;
                            } while (strtotime($eDate) > strtotime($sDate));
                        } else {
                            $holidayList[] = date("d-M-Y", strtotime($sDate));
                        }
                    }
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId,
                        'form_id'   => $actionId
                            )
                    );
                    $rules_info=$this->getTrackerService()->getValidationRules($actionId, $role_id);
    
                    $rules='';
                    $messages='';
                    $field_name='';
                    $rules_detail='';
                    $msg_detail='';
                    for ($i=0;$i<count($rules_info);$i++) {
                        $rules_start= ('"'.$rules_info[$i]["field_name"].'":{');
                        $msg_start= ('"'.$rules_info[$i]["field_name"].'":{');
                        $rules1='';
                        $messages1='';
                        for ($j=$i;$j<=$i;$j++) {
                            $ruleType=$rules_info[$j]["rule_name"];
                            switch ($ruleType) {
                            case 'required':
                            case 'url':
                            case 'email':
                                $rules_info[$j]["value"]='true';
                                break;
                            case 'regex':
                                if ($rules_info[$j]["value"]!='') {
                                    if (preg_match("/(^\\/)/", $rules_info[$j]["value"]) > 0 ) {
                                        $rules_info[$j]["value"]=$rules_info[$j]["value"];
                                    } else {
                                        $rules_info[$j]["value"]="/".$rules_info[$j]["value"];
                                    }
                                    if (preg_match("/\\/$/", $rules_info[$j]["value"]) > 0) {
                                        $rules_info[$j]["value"]=$rules_info[$j]["value"];
                                    } else {
                                        $rules_info[$j]["value"]=$rules_info[$j]["value"]."/";
                                    }
                                } else {
                                    $rules_info[$j]["value"]="/^$/";
                                }
                                break;
                            default:
                                $rules_info[$j]["value"]=$rules_info[$j]["value"];
                            }
                            if ($rules_info[$j]["field_name"]!=$field_name) {
                                $rules_detail=$rules_info[$j]["rule_name"].':'.$rules_info[$j]["value"];
                                $msg_detail=$rules_info[$j]["rule_name"].':"'.$rules_info[$j]["message"].'"';
                            } else {
                                $rules_detail= ($rules_detail !=''? $rules_detail.','.$rules_info[$j]["rule_name"].':'.$rules_info[$j]["value"] :$rules_info[$j]["rule_name"].':'.$rules_info[$j]["value"]);
                                $msg_detail= ($msg_detail !=''? $msg_detail.','.$rules_info[$j]["rule_name"].':"'.$rules_info[$j]["message"].'"' :$rules_info[$j]["rule_name"].':"'.$rules_info[$j]["message"].'"');
                            }
                            $rules_end= ('}');
                            $msg_end= ('}');
                            $rules1=$rules_start.$rules_detail.$rules_end;
                            $messages1=$msg_start.$msg_detail.$msg_end;
                            $field_name=$rules_info[$j]["field_name"];
                        }
                        if (isset($rules_info[$j]["field_name"])) {
                            if ($rules_info[$j]["field_name"]!=$field_name) {
                                $rules= ($rules !=''? $rules.','. $rules1 :$rules1);
                                $messages= ($messages !=''? $messages.','. $messages1 :$messages1);
                            }
                        }
                        if (!isset($rules_info[$j]["field_name"])) {
                            $rules= ($rules !=''? $rules.','. $rules1 :$rules1);
                            $messages= ($messages !=''? $messages.','. $messages1 :$messages1);
                        }
                    }
                    $script=htmlentities(
                        ' 
                    $("#workflowForm").validate({
                        errorElement: "div",
                        onkeyup: function(element) {$(element).valid()},
                        onfocusout: function(element) {$(element).valid()},
                        onclick: function(element) {$(element).valid()},
                        onsubmit: function(element) {$(element).valid()},
                        onchange: function(element) {$(element).valid()},
                        onselect: function(element) {$(element).valid()},
                        onClose: function(element) {$(element).valid()},
                        errorPlacement: function(error, element) {
                           if (element[0].type == "checkbox") {
                                error.appendTo(element.parent("div").siblings(":last"));
                            } else {
                                error.insertAfter(element);
                            }
                        },
                    rules: {'.$rules.'},
                    messages:{'.$messages.'},
                    submitHandler: function(form) {
                        
                        submitAfterValidate();
                      }
                    });
                    
                    $.validator.addMethod("regex", function(value, element, regexpr) {
                        return regexpr.test(value);
                    });
                    $.validator.addMethod("depends", function(value, element,depends_text) {
                        if ($("#"+depends_text.id).val()=="" && value=="Yes") {
                            $("#"+element.id).val("");
                            return false;
                        } else {
                            return true;
                        }
                    });
                    $.validator.addMethod("dates",function(value, element) {
                        var dateReg = /^(\d{4})\-(\d{2})\-(\d{2}) (\d{2}):(\d{2})$/;
                        return value.match(dateReg);
                    },
                    "Invalid date"
                    );
                    $.validator.addMethod("Dates",function(value, element) {
                        var inputDate=new Date(value);
                        var inDate=toDate(inputDate);
                        var dateReg = /^(\d{2})\-(\d{2})\-(\d{4})$/;
                        return inDate.match(dateReg);
                    },
                    "Invalid date"
                    );
                    $.validator.addMethod("maxDate", function(value,elemen,param) {
                        if (value=="") {
                            return true;
                        }
                       var inDate=toformatdatetime(value,"date");
                       var curDate = toformatdatetime(new Date(),"date");
                       inDate= Date.parse(inDate)/1000;    //change date time to unix time stamp
                       curDate= Date.parse(curDate)/1000;  //change date time to unix time stamp
                       if (inDate <= curDate)
                       return true;
                       return false;
                    },"Invalid DateTime!");
                    $.validator.addMethod("maxDateTime", function(value, elemen,param) {
                      
                       var inDate=toformatdatetime(value,"datetime");
                       var curDate = toformatdatetime(new Date(),"datetime");
                       inDate= Date.parse(inDate)/1000;    //change date time to unix time stamp
                       curDate= Date.parse(curDate)/1000;  //change date time to unix time stamp
                       if (inDate <= curDate)return true;
                       return false;
                    },"Invalid DateTime!");

                     $.validator.addMethod("minDateTime", function(value, elemen,param) {
                       var inputDate=new Date(value);
                       var inDate=toDateTime(inputDate);
                       var curDate = moment().subtract(param, "day").format("YYYY-MM-DD HH:mm");
                       if (inDate <= curDate) return true;
                       return false;
                    },"Invalid DateTime!"); '
                    );
                    //$queryString = filter_input(INPUT_SERVER, 'QUERY_STRING');
                    //parse_str(filter_input(INPUT_SERVER, 'QUERY_STRING'), $queryArray);
                    //$type = isset($queryArray['type'])?trim($queryArray['type']):"all";
                    //$filter = isset($queryArray['filter'])?trim($queryArray['filter']):"all";
                    $type = $this->getEvent()->getRouteMatch()->getParam('type', 'all');
                    $filter = $this->getEvent()->getRouteMatch()->getParam('filter', 'all');
                    //$workflowOpen = isset($queryArray['workflow'])?trim($queryArray['workflow']):"all";
                    $workflowOpen =  $this->getEvent()->getRouteMatch()->getParam('workflow', 'all');
                    //echo $workflowOpen; die;
                    //$queryString='?type='.$type.'&filter='.$filter;
                    $configData=$this->getWorkflowMgmService()->getconfigDataByForm($actionId);
                    $configData = array_column($configData, 'config_value', 'config_key');
                    
                    return new ViewModel(
                        array(
                        'action_id' => $actionId,
                        'tracker_id' => $trackerId,
                        'form_details' => $formDetails,
                        'fields_array_val' => $fieldsArray,
                        'record_id' => $subactionId,
                        'validation_script'=>$script,
                        'workflow' =>$workflowArray,
                        //'queryString' => $queryString,
                        'label' => $type,
                        'workflowOpen' => $workflowOpen,
                        'dateFormat' => $configData['dateFormat'],
                        'dateTimeFormat' => $configData['dateTimeFormat'],
                        'holidayList' => $holidayList,
                        'type' => $type,
                        'filter' => $filter
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }

    public function sortArrOfObj($array, $sortby, $direction = 'asc')
    {
        $sortedArr = array();
        $tmpArray = array();
        foreach ($array as $k => $v) {
            $tmpArray[] = strtolower($v->$sortby);
        }
        if ($direction == 'asc') {
            asort($tmpArray);
        } else {
            arsort($tmpArray);
        }

        foreach ($tmpArray as $k => $tmp) {
            $sortedArr[] = $array[$k];
        }

        return $sortedArr;
    }

    /*
     * Function to delete record from particular form
     */

    public function deleterecordfromformAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $actionId = $this->params()->fromRoute('action_id', 0);
        $id = $this->params()->fromRoute('subaction_id', 0);
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    
        $comment = $post['addcomment'];
        unset($post['comment']);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $userDetails = $userContainer->user_details;
        if (!isset($userContainer->u_id)) {
            $applicationController->saveToLogFile($id, isset($userDetails['email'])?$userDetails['email']:'', "Delete Record", '', '', $comment, "Failed", $trackerData['client_id']);
            return $this->redirect()->toRoute('home');
        } else {
            $container = new Container('msg');
            $resultset = $this->getTrackerService()->deleterecord($trackerId, $actionId, $id);
            $container->message = 'deleted';
            $applicationController->saveToLogFile($id, isset($userDetails['email'])?$userDetails['email']:'', "Delete Record", '', '', $comment, "Success", $trackerData['client_id']);
            echo $messages = 'Record deleted successfully'; exit;
        }
        exit;
    }

    public function accessSettingsAction()
    {
        $response = $this->getResponse();           
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    $result = $this->getTrackerService()->getWorkflowRoleForForms($trackerId);
                    $result[0] = $this->sortArrOfObj($result[0], 'workflow_id');
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId
                            )
                    );
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'users' => $this->getTrackerService()->trackerUsers($trackerId),
                        'tracker_id' => @$trackerId,
                        'resultset' => $result,
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
    }

    public function saveupdatesettingAction()
    {
        $response = $this->getResponse();           
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');        
        $post = $this->getRequest()->getPost()->toArray();  
        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $actionId = $this->params()->fromRoute('action_id', 0);
        $responseCode = 0;
        $errMessage = "";
        $resArr = array();
        if ($post) {
            $dataArr = $post;
            $comment = $dataArr['comment'];
            unset($dataArr['comment']);
            $can_update = $dataArr['can_update'];
            if (!isset($userContainer->u_id)) {
                foreach ($dataArr as $key => $value) {
                    $applicationController->saveToLogFile($dataArr['role_id'], $key, $dataArr[$key], '', 'Access Setting-For update', '', $trackerId, $actionId, 0, IP, $comment, 'Session timeout:Failure');
                }
                return $this->redirect()->toRoute('home');
            } else {
                $accessArr = $this->getTrackerService()->getupdateaccesssetting($dataArr);
                foreach ($can_update as $key => $value) {
                    if (!empty($accessArr)) {
                        if ($value != $accessArr[$key]['can_update']) {
                            $applicationController->saveToLogFile($dataArr['role_id'], 'can_update', $value, $accessArr[$key]['can_update'], 'Access Setting-For update', '', $trackerId, $actionId, 0, IP, $comment, 'Success');
                        }
                    } else {
                        $applicationController->saveToLogFile($dataArr['role_id'], 'can_update', $value, '', 'Access Setting-For update', '', $trackerId, $actionId, 0, IP, $comment, 'Success');
                    }
                }
                $resArr = $this->getTrackerService()->saveupdatesetting($dataArr);
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, '', '', '', 'Access Setting-For update', '', $trackerId, $actionId, 0, IP, '', 'Session Timeout,Post Array is blank:Failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $applicationController->saveToLogFile(0, '', '', '', 'Access Setting-For update', $userDetails['u_name'], $trackerId, $actionId, 0, IP, '', 'Post Array is blank:Failure');
            }
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        return $response->setContent(\Zend\Json\Json::encode($resArr));
    }

    public function savereadsettingAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $actionId = $this->params()->fromRoute('action_id', 0);
        $post = $this->getRequest()->getPost()->toArray(); 
        $resArr = array();
        if ($post) {
            $dataArr = $post;
            $comment = $dataArr['comment'];
            unset($dataArr['comment']);
            $can_read = $dataArr['can_read'];
            if (!isset($userContainer->u_id)) {
                foreach ($dataArr as $key => $value) {
                    $applicationController->saveToLogFile($dataArr['role_id'], 'can_read', $value, '', 'Access Setting-For read', '', $trackerId, $actionId, 0, IP, $comment, 'Session timeout:Failure');
                }
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $accessArr = $this->getTrackerService()->getreadaccesssetting($dataArr);
                foreach ($can_read as $key => $value) {
                    if (!empty($accessArr)) {
                        if ($value != $accessArr[$key]['can_read']) {
                            $applicationController->saveToLogFile($dataArr['role_id'], 'can_read', $value, $accessArr[$key]['can_read'], 'Access Setting-For read', '', $trackerId, $actionId, 0, IP, $comment, 'Success');
                        }
                    } else {
                        $applicationController->saveToLogFile($dataArr['role_id'], 'can_read', $value, '', 'Access Setting-For read', '', $trackerId, $actionId, 0, IP, $comment, 'Success');
                    }
                }
                $resArr = $this->getTrackerService()->savereadsetting($dataArr);
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, '', '', '', 'Access Setting-For read', '', $trackerId, $actionId, 0, IP, '', 'Session Timeout,Post Array is blank:Failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $applicationController->saveToLogFile(0, '', '', '', 'Access Setting-For read', $userDetails['u_name'], $trackerId, $actionId, 0, IP, '', 'Post Array is blank:Failure');
            }
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        return $response->setContent(\Zend\Json\Json::encode($resArr));
    }

    public function defaultReportSettingAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    $result = $this->getTrackerService()->getformsreport($trackerId);
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId
                            )
                    );
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'tracker_id' => @$trackerId,
                        'resultset' => $result,
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
    }

    /*
     * save default report setting
     */

    public function savedefaultreportsettingAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $resArr = array();
        if ($post) {
            $dataArr = $post;
            $comment = $dataArr['comment'];
            unset($dataArr['comment']);
            $can_access = $dataArr['can_access']['0'];
            $form_id = $dataArr['form_id'];
            $role_id = $dataArr['role_id'];
            $value='';
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile($dataArr['role_id'], 'can_access', $value, '', 'Default Report Setting', '', $trackerId, $form_id, 0, IP, $comment, 'Session timeout:Failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $accessArr = $this->getTrackerService()->getdefaultreportsetting($dataArr);
                if (isset($accessArr[0])) {
                    if ($accessArr[0]['report_id'] != $can_access) {
                        $applicationController->saveToLogFile($dataArr['role_id'], 'can_access', $can_access, $accessArr[0]['report_id'], 'Default Report Setting', $userDetails['u_name'], $trackerId, $form_id, 0, IP, $comment, 'Success');
                    }
                } else {
                    $applicationController->saveToLogFile($dataArr['role_id'], 'can_access', $can_access, '', 'Default Report Setting', $userDetails['u_name'], $trackerId, $form_id, 0, IP, $comment, 'Success');
                }
                $resArr = $this->getTrackerService()->savedefaultreportsetting($dataArr);
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, '', '', '', 'Default Report Setting', '', $trackerId, 0, 0, IP, '', 'Session Timeout,Post Array is blank:Failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $applicationController->saveToLogFile(0, '', '', '', 'Default Report Setting', $userDetails['u_name'], $trackerId, 0, 0, IP, '', 'Post Array is blank:Failure');
            }
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        echo json_encode($resArr);
        exit;
    }

    public function formAccessSettingsAction()
    {
        $response = $this->getResponse();           
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    $result = $this->getTrackerService()->getformaccessdetail($trackerId);
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId
                            )
                    );
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'users' => $this->getTrackerService()->trackerUsers($trackerId),
                        'tracker_id' => @$trackerId,
                        'resultset' => $result,
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
    }

    public function saveformsettingAction()
    {
        $response = $this->getResponse();           
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');        
        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $post = $this->getRequest()->getPost()->toArray();
        $resArr = array();
        if ($post) {
            $dataArr = $post;
            $comment = $dataArr['comment'];
            unset($dataArr['comment']);
            $can_read = $dataArr['can_read'];
            $form_ids = $dataArr['form_id'];
            if (!isset($userContainer->u_id)) {
                foreach ($can_read as $key => $value) {
                    $applicationController->saveToLogFile($dataArr['role_id'], 'can_read', $value, '', 'Form Access Setting', '', $trackerId, $form_ids[$key], 0, IP, $comment, 'Session timeout:Failure');
                }
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $accessArr = $this->getTrackerService()->getformaccesssetting($dataArr);
                foreach ($can_read as $key => $value) {
                    if (isset($accessArr[$key])) {
                        if ($value != $accessArr[$key]['can_read'] && $accessArr[$key]['form_id'] == $form_ids[$key]) {
                            $applicationController->saveToLogFile($dataArr['role_id'], 'can_read', $value, $accessArr[$key]['can_read'], 'Form Access Setting', $userDetails['u_name'], $trackerId, $form_ids[$key], 0, IP, $comment, 'Success');
                        }
                    } else {
                        $applicationController->saveToLogFile($dataArr['role_id'], 'can_read', $value, '', 'Form Access Setting', $userDetails['u_name'], $trackerId, $form_ids[$key], 0, IP, $comment, 'Success');
                    }
                }
                $resArr = $this->getTrackerService()->saveformsetting($dataArr);
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, '', '', '', 'Form Access Setting', '', $trackerId, 0, 0, IP, '', 'Session Timeout,Post Array is blank:Failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $applicationController->saveToLogFile(0, '', '', '', 'Form Access Setting', $userDetails['u_name'], $trackerId, 0, 0, IP, '', 'Post Array is blank:Failure');
            }
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        return $response->setContent(\Zend\Json\Json::encode($resArr));
    }

    public function reportAccessSettingAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                
                if (in_array($trackerId, $trackerIds)) {
                    $result = $this->getTrackerService()->getReportAccessSetting($trackerId);
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId
                            )
                    );
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'users' => $this->getTrackerService()->trackerUsers($trackerId),
                        'tracker_id' => @$trackerId,
                        'resultset' => $result,
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
    }

    public function savereportaccesssettingAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $userDetails = $userContainer->user_details;
        $applicationController = new \Application\Controller\IndexController;
        $response = $this->getResponse(); 
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerContainer = $session->getSession('tracker');
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
            $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
            $resArr = array();
            
            if ($post) {
                $dataArr = $post;
                $comment = $dataArr['comment'];
                unset($dataArr['comment']);
                $can_update = $dataArr['can_update'];
                $can_read = $dataArr['can_read'];
                $form_id = $dataArr['form_id'];
                $report_ids = $dataArr['report_id'];
                $aNewReportAccessSettings = array_combine($dataArr['report_id'], $dataArr['can_update']);
                $aNewReportAccessSettings['form_id'] = $form_id;
                $aNewReportAccessSettings['role_id'] = $dataArr['role_id'];
                // echo json_encode($aAccessSettings); die;
                if (!isset($userContainer->u_id)) {
                    $applicationController->saveToLogFile($dataArr['role_id'], $userDetails['email'], 'Report Access Settings', '', json_encode($aNewReportAccessSettings), $comment, 'Session timeout:Failure', $trackerData['client_id']);
                    return $this->redirect()->toRoute('home');
                } else {
                    $accessArr = $this->getTrackerService()->getreportsetting($dataArr);
                    $aOldReportAccessSettings = array();
                    if (isset($accessArr)) {
                        foreach ($accessArr as $key => $value) {
                            $aOldReportAccessSettings[$value['report_id']] = $value['can_access'];
                        }

                        $aOldReportAccessSettings['form_id'] = $form_id;
                        $aOldReportAccessSettings['role_id'] = $dataArr['role_id'];
                    }
                    $applicationController->saveToLogFile($dataArr['role_id'], $userDetails['email'], 'Report Access Settings', json_encode($aOldReportAccessSettings), json_encode($aNewReportAccessSettings), $comment, 'Success', $trackerData['client_id']);
                    $resArr = $this->getTrackerService()->savereportaccesssetting($dataArr);
                }
            } else {
                $responseCode = 0;
                $errMessage = "Access Denied";
                $resArr['responseCode'] = $responseCode;
                $resArr['errMessage'] = $errMessage;
            }
        }
        echo json_encode($resArr);
        exit;
    }

    public function reportExportSettingAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $tracker_ids = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $tracker_ids)) {
                    $result = $this->getTrackerService()->getReportExportSetting($trackerId);
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => @$trackerId
                            )
                    );
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                        'users' => $this->getTrackerService()->trackerUsers($trackerId),
                        'tracker_id' => @$trackerId,
                        'resultset' => $result,
                            )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
    }

    public function savereportexportsettingAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $resArr = array();
        if ($post) {
            $dataArr = $post;
            $comment = $dataArr['comment'];
            unset($dataArr['comment']);
            $can_update = $dataArr['can_update'];
            $can_read= $dataArr['can_read'];
            $form_id = $dataArr['form_id'];
            $report_ids = $dataArr['report_id'];
            if (!isset($userContainer->u_id)) {
                foreach ($can_read as $key => $value) {
                    $applicationController->saveToLogFile($dataArr['role_id'], 'can_access', $value, '', 'Report Export Setting', '', $trackerId, $form_id, $report_ids[$key], IP, $comment, 'Session timeout:Failure');
                }
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $accessArr = $this->getTrackerService()->getexportreportsetting($dataArr);
                foreach ($can_update as $key => $value) {
                    if (isset($accessArr[$key])) {
                        if ($value != $accessArr[$key]['can_access'] && $accessArr[$key]['report_id'] == $report_ids[$key]) {
                            $applicationController->saveToLogFile($dataArr['role_id'], 'can_access', $value, $accessArr[$key]['can_access'], 'Report Access Setting', $userDetails['u_name'], $trackerId, $form_id, $report_ids[$key], IP, $comment, 'Success');
                        }
                    } else {
                        $applicationController->saveToLogFile($dataArr['role_id'], 'can_access', $value, '', 'Report Export Setting', $userDetails['u_name'], $trackerId, $form_id, $report_ids[$key], IP, $comment, 'Success');
                    }
                }
                $resArr = $this->getTrackerService()->savereportexportsetting($dataArr);
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, '', '', '', 'Report Export Setting', '', $trackerId, 0, 0, IP, '', 'Session Timeout,Post Array is blank:Failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $applicationController->saveToLogFile(0, '', '', '', 'Report Export Setting', $userDetails['u_name'], $trackerId, 0, 0, IP, '', 'Post Array is blank:Failure');
            }
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        echo json_encode($resArr);
        exit;
    }

    /*
     * populate fields from another form
     */

    public function getvaluefromotherformAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray();

        $originfields = implode(',', $post['originfields']);
        $resultset = $this->getTrackerService()->getvaluefromotherform($post['form'], $originfields, $post['id'], $post['val']);
        if (!empty($resultset)) {
            $response->setContent(\Zend\Json\Json::encode($resultset));
        }
        return $response;
    }

    /*
     * function to copy tracker
     */

    public function exportTrackerAction()
    {
        $post = $this->getRequest()->getPost()->toArray();
        $validator = new Alnum(array('allowWhiteSpace' => true));
        $applicationController = new \Application\Controller\IndexController;
        $response = $this->getResponse();
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $container = new Container('successmsg_export');
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;

        $workflowContainer = new Container('workflow');
        $formulaFieldsContainer = new Container('formula_fields');
        if (!isset($userContainer->u_id)) {
            $trackerName = isset($post['t_name'])?$post['t_name']:'';
            $applicationController->saveToLogFile(0, '', $trackerName, '', 'Export Tracker', '', $trackerId, 0, 0, IP, '', 'Session timeout:Failure');
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $userDetails = @$userContainer->user_details;
            $roleId = @$userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $response->setContent('You are not eligible to access this.');
                return $response;
            }
            if ($trackerId == 0) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_204);
                $response->setContent('invalid tracker id. Please choose correct tracker');
                return $response;
            } else {
                $trackerIds = $trackerContainer->tracker_ids;
                if (in_array($trackerId, $trackerIds)) {
                    $this->layout()->setVariables(
                        array(
                                'tracker_id' => $trackerId
                            )
                    );
                    if (isset($post) && !empty($post)) {
                        if ($validator->isValid($post['t_name'])&& $validator->isValid($post['datacopy']) && $validator->isValid($post['userrole'])) {
                            if ($post['t_name'] == '') {
                                $response->setContent(\Zend\Json\Json::encode(false));
                                return $response;
                            }
                            if ($post['datacopy'] == '') {
                                $response->setContent(\Zend\Json\Json::encode(false));
                                return $response;
                            }
                            if ($post['userrole'] == '') {
                                $response->setContent(\Zend\Json\Json::encode(false));
                                return $response;
                            }
                            $trackerName = $post['t_name'];
                            $withdata = $post['datacopy'];
                            $withuserrole = $post['userrole'];
                            $res = $this->getTrackerService()->copytrcker($withdata, $trackerName, $withuserrole, $trackerId, 1/* $userDetails['u_id'] */);
                            $applicationController->saveToLogFile($res, '', $trackerName, '', 'Export Tracker', $userDetails['u_name'], $trackerId, 0, 0, IP, '', 'Success');
                            if ($res > 0) {
                                array_push($trackerContainer->tracker_ids, $res);
                            }
                            array_push($trackerContainer->tracker_ids, $res);
                            if ($res > 0) {
                                $this->flashMessenger()->addMessage(array('success' => 'Tracker copied successfully!'));
                                $container->message = 'exported';
                                return $this->redirect()->toRoute('tracker');
                            } else {
                                if ($res == 0) {
                                    $response->setContent(\Zend\Json\Json::encode('failure'));
                                    return $response;
                                }
                                if ($res == -1) {
                                    $container->message = 'duplicate';
                                    return $this->redirect()->toUrl('/tracker/export_tracker/'.$trackerId);
                                }
                            }
                        }
                    } else {
                        return new ViewModel(
                            array(
                                    'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                                    'users' => $this->getTrackerService()->trackerUsers($trackerId),
                                    'tracker_id' => @$trackerId,
                                )
                        );
                    }
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
    }
    /*
    * function to workflow setting to make a workflow editable
    */
    public function workflowSettingAction()
    {
        $userContainer = new Container('user');
        $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
        $response = $this->getResponse();
        
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $container = new Container('msg');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerRsults = $this->getTrackerService()->trackerRsults($trackerId);
            return new ViewModel(
                array(
                'trackerRsults' => $trackerRsults,
                'workflowRules' => $this->getTrackerService()->getworkflowrule($trackerId),
                'tracker_id' => $trackerId,
                    )
            );
        }
    }

    public function getformfieldsAction()
    {
        $response = $this->getResponse();
        $userContainer = new Container('user');
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $fieldsArray = $this->getTrackerService()->getfields((int)$post['form_id']);
            $response->setContent(\Zend\Json\Json::encode($fieldsArray));
            return $response;
        }

    }

    public function getWorkflowAction()
    {
        $response = $this->getResponse();
        $userContainer = new Container('user');
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $workflowArray = $this->getTrackerService()->getWorkFlowsForWorkflowRule((int)$post['tracker_id'], (int)$post['form_id']);
            $response->setContent(\Zend\Json\Json::encode($workflowArray));
            return $response;
        }
    }

    public function saveruleAction()
    {
        $userContainer = new Container('user');
        $post = $this->getRequest()->getPost()->toArray();
        $response = $this->getResponse();
        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        if (!empty($post)) {
            if ($post['rule_id'] > 0) {
                $datapost=$post;
                unset($datapost['rule_id']);
                unset($datapost['tracker_id']);
                unset($datapost['comment']);
                if (!isset($userContainer->u_id)) {
                    foreach ($datapost as $key => $value) {
                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }
                        $applicationController->saveToLogFile($post['rule_id'], $key, $value, '', 'Edit Workflow Rule', '', $trackerId, $post['form_id'], 0, IP, '', 'Session Timeout:Failure');
                    }
                    return $this->redirect()->toRoute('home');
                } else {
                    $resultset = $this->getTrackerService()->geteditwfruleinfo($post['rule_id']);
                    $fieldsArray = $this->getTrackerService()->saveRule($post);
                    $data_array['form_id']=$resultset[0][0]['form_id'];
                    $data_array['r_cond']=$resultset[0][0]['operator'];
                    for ($i=0;$i<sizeof($resultset[0]);$i++) {
                        $data_array['condition_on_field'][$i]=$resultset[0][$i]['field_id'];
                        $data_array['condition_operand'][$i]=$resultset[0][$i]['comparision_op'];
                        $data_array['value'][$i]=$resultset[0][$i]['val'];
                    }
                    for ($i=0;$i<sizeof($resultset[1]);$i++) {
                        $data_array['action_value'][$i]=$resultset[1][$i]['value'];
                        $data_array['action_name'][$i]=$resultset[1][$i]['action'];
                    }
                    foreach ($datapost as $key => $value) {
                        if (!is_array($value)) {
                            if ($datapost[$key]!=$data_array[$key]) {
                                $applicationController->saveToLogFile($post['rule_id'], $key, $value, $data_array[$key], 'Edit Workflow Rule', '', $trackerId, $post['form_id'], 0, IP, '', 'Success');
                            }
                        } else {
                            $datapostimploded = implode(',', $value);
                            $data_arrayimploded = implode(',', $data_array[$key]);
                            if ($datapostimploded != $data_arrayimploded) {
                                $applicationController->saveToLogFile($post['rule_id'], $key, $datapostimploded, $data_arrayimploded, 'Edit Workflow Rule', '', $trackerId, $post['form_id'], 0, IP, '', 'Success');
                            }
                        }
                    }
                    $this->flashMessenger()->addMessage(array('success' => 'Rule updated successfully!'));
                }
            } else {
                $fieldsArray = $this->getTrackerService()->saveRule($post);
                $datapost = $post;
                $comment = $datapost['comment'];
                unset($datapost['tracker_id']);
                unset($datapost['form_id']);
                unset($datapost['rule_id']);
                unset($datapost['comment']);
                if (!isset($userContainer->u_id)) {
                    foreach ($datapost as $key => $value) {
                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }
                        $applicationController->saveToLogFile($post['rule_id'], $key, $value, '', 'Add Workflow Rule', '', $trackerId, $post['form_id'], 0, IP, $comment, 'Session Timeout:Failure');
                    }
                    return $this->redirect()->toRoute('home');
                } else {
                    $userDetails = @$userContainer->user_details;
                    foreach ($datapost as $key => $value) {
                        if (is_array($value)) {
                            $value = implode(',', $value);
                        }
                        $applicationController->saveToLogFile($post['rule_id'], $key, $value, '', 'Add Workflow Rule', $userDetails['u_name'], $trackerId, $post['form_id'], 0, IP, $comment, 'Success');
                    }
                    $this->flashMessenger()->addMessage(array('success' => 'Rule added successfully!'));
                }
            }
        } else {
            $fieldsArray = "Rule Post Data should not empty!";
        }
        $response->setContent(\Zend\Json\Json::encode($fieldsArray));
        return $response;
    }

    public function getruleinfoAction()
    {
        $userContainer = new Container('user');
        $response = $this->getResponse();
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            if ($this->getRequest()->isXmlHttpRequest()) {
                $response = $this->getResponse();
                $post = $this->getRequest()->getPost()->toArray();
                if (isset($post['rule_id'])) {
                    $post['rule_id']=filter_var($post['rule_id'], FILTER_SANITIZE_NUMBER_INT);
                } else {
                    $response->setContent(\Zend\Json\Json::encode('No data found'));
                    return $response;
                }
                try{
                    $fieldsArray = $this->getTrackerService()->getruleinfo($post);
                    $response->setContent(\Zend\Json\Json::encode($fieldsArray));
                    return $response;
                } catch (\Zend\Db\Adapter\Exception $e) {
                    $response->setContent(\Zend\Json\Json::encode('error'));
                    return $response;
                } catch (\Exception $e) {
                    $response->setContent(\Zend\Json\Json::encode('error'));
                    return $response;
                }
            }
        }

    }

    public function deleteRuleAction()
    {
        $userContainer = new Container('user');
        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $post = $this->getRequest()->getPost()->toArray();
        $comment = $post['comment'];
        unset($post['comment']);
        $response = $this->getResponse();
        if ($post) {
            $dataArr = $post;
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile($dataArr['ruleId'], '', '', '', 'Delete Workflow rule', '', $dataArr['tracker_id'], $dataArr['formId'], 0, IP, $comment, 'Session Timeout:Failure');
                return $this->redirect()->toRoute('home');
            } else {
                if (!empty($post['ruleId'])) {
                    $userDetails = $userContainer->user_details;
                    $archived = $this->getTrackerService()->deleteRule($post['ruleId']);
                    $this->flashMessenger()->addMessage(array('success' => 'Rule deleted successfully!'));
                    $applicationController->saveToLogFile($dataArr['ruleId'], '', '', '', 'Delete Workflow rule', $userDetails['u_name'], $dataArr['tracker_id'], $dataArr['formId'], 0, IP, $comment, 'Success');
                } else {
                    $userDetails = $userContainer->user_details;
                    $archived = "You should have rule id for rule delete !";
                    $applicationController->saveToLogFile(0, '', '', '', 'Delete Workflow rule', $userDetails['u_name'], $dataArr['tracker_id'], $dataArr['formId'], 0, IP, $comment, 'Failure');
                }
                echo $archived;
                exit;
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, '', '', '', 'Delete Workflow rule', '', $trackerId, 0, 0, IP, $comment, 'Session Timeout,Post Array is blank:Failure');
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $applicationController->saveToLogFile(0, '', '', '', 'Delete Workflow rule', $userDetails['u_name'], $trackerId, 0, 0, IP, $comment, 'Postdata is blank:failure');
            }
        }
        return $response;
    }
    public function auditlogAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $applicationController = new \Application\Controller\IndexController;
        if (!isset($userContainer->u_id)) {
            header("HTTP/1.0 401 Unauthorized");
            return $this->redirect()->toRoute('home');
        } else {
            $applicationController->saveToLogFile(0, isset($userDetails['email'])?$userDetails['email']:'', "Audit Trail View", '', '', "Audit Trail View", "Success", 0);
            $config = $this->getServiceLocator()->get('Config');
            $kibanaUrl=$config['kibana']['url']['kibana_url'];
            return new ViewModel(array('kibana_url' => $kibanaUrl));
        }
    }

    public function parameterizedTrackerAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $applicationController = new \Application\Controller\IndexController;
        
        $applicationController = new \Application\Controller\IndexController;
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $roleId = $userDetails['group_id'];
        $trackerContainer = $session->getSession('tracker');
        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        
        $sessionGroup = $trackerUserGroups[$trackerId]['session_group'];
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            return $this->redirect()->toRoute('tracker');
        }
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $trackerResults = $this->getModelService()->trackerResults($trackerId);
        $formId = $trackerResults['forms'][0]['form_id'];
        if (!isset($userContainer->u_id)) {
            header("HTTP/1.0 401 Unauthorized");
            return $this->redirect()->toRoute('home');
        } else {
            $applicationController->saveToLogFile(0, isset($userDetails['email'])?$userDetails['email']:'', "Audit Trail View", '', '', "Audit Trail View", "Success", 0);
            $config = $this->getServiceLocator()->get('Config');
            $kibanaUrl=$config['kibana']['url']['kibana_url'];
            $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id' => $formId));
            return new ViewModel(array('kibana_url' => $kibanaUrl, 'clientId' => $trackerData['client_id'],'trackerId' => $trackerId, 'trackerResults' => $trackerResults));
        }
  
    }

    public function trackerDateSettingAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $container = new Container('msg');
        $userContainer = new Container('user');
        $messages='';
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            $selected=array();
            $selected =  $this->getTrackerService()->getSelectedDate($trackerId);
            if (count($selected)!=0) {
                $date_format_selected = $selected[0]['php_date_format'];
                $date_time_fomat_selected = $selected[0]['php_date_time_format'];
            } else {
                $date_format_selected=array();
                $date_time_fomat_selected=array();
            }
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $request = $this->getRequest();
                $container = new Container('msg');
                $response = $this->getResponse();
                $post = $this->getRequest()->getPost()->toArray();
                if (!empty($post)) {
                    $finalData[0] = explode('@', $post['date_format']);
                    $finalData[1] = explode('@', $post['date_time_format']);
                    foreach ($finalData as $data) {
                        $fd[] = $data[0];
                        $fd[] = $data[1];
                        $fd[] = $data[2];
                        $fd[]=$data[3];
                       
                    }
                    $resultset = $this->getTrackerService()->getTrackerDateSetting($fd, $trackerId);
                    if (!empty($messages)) {
                        $response->setContent(\Zend\Json\Json::encode($messages));
                    }
                    $applicationController->saveToLogFile($finalData, '', '', '', 'Save Date Format', $userDetails['u_name'], $trackerId, '', 0, IP, '', 'Success');
                    $container->messg = 'Tracker date updated successfully';
                    return $this->redirect()->toRoute('tracker', array('action' => 'settings', 'tracker_id' => $trackerId));
                }
               
                return new ViewModel(
                    array(
                            'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                            'tracker_id' => $trackerId,
                            'date_format' => $this->getTrackerService()->getDateFormat($trackerId),
                            'date_format_selected' => $date_format_selected,
                            'date_time_fomat_selected' => $date_time_fomat_selected
                        )
                );
            }
        }
    }

    public function getValidationRuleAction()
    {
        $response = $this->getResponse();
        $userContainer = new Container('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            if ($this->getRequest()->isXmlHttpRequest()) {
                $response = $this->getResponse();
                $post = $this->getRequest()->getPost()->toArray();
                if (isset($post['fieldtype'])) {
                    $fieldtype=filter_var($post['fieldtype'], FILTER_SANITIZE_STRING);
                } else {
                    $response->setContent(\Zend\Json\Json::encode('No data found'));
                    return $response;
                }
                $workflowArray = $this->getTrackerService()->getValidationRule($fieldtype);
                $response->setContent(\Zend\Json\Json::encode($workflowArray));
                return $response;
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('Your dont have permission to access this.');
                return $response;
            }
        }
    }
    public function onDispatch(\Zend\Mvc\MvcEvent $e)
    { 
        $userContainer= new Container('user');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('logout');
        }
        return parent::onDispatch($e);
    }
    
    /*for search setting:to add fields for searching*/
    public function searchSettingAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        $applicationController = new \Application\Controller\IndexController;
        $container = new Container('msg');
        $userContainer= new Container('user');
        $container->messg = '';
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $request = $this->getRequest();
                $container = new Container('msg');
                $response = $this->getResponse();
                $post = $this->getRequest()->getPost()->toArray();
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && ($post['form']=='' || empty($post['serach_field']))) {
                    $container->messg = 'mandatory';
                    return new ViewModel(
                        array(
                                'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                                'tracker_id' => $trackerId,
                            )
                    );
                } else if (!empty($post)) {
                    if ($post['form']>0 && !empty($post['serach_field'])) {
                        $resultset = $this->getTrackerService()->updatefields($post['serach_field'], $post['form']);
                        if ($resultset) {
                            $response->setContent(\Zend\Json\Json::encode($resultset));
                        }
                        $container->messg = 'Search setting updated successfully';
                        return $this->redirect()->toRoute('tracker', array('action' => 'settings', 'tracker_id' => $trackerId));
                    }
                }

                return new ViewModel(
                    array(
                            'trackerRsults' => $this->getTrackerService()->trackerRsults($trackerId),
                            'tracker_id' => $trackerId,
                        )
                );
            }
        }
    }
    /**
     * Delete uploaded file from form 
     */
    public function removefileAction()
    {
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray();
        $result = $this->getTrackerService()->removefile($post);
        $response->setContent(\Zend\Json\Json::encode($result));
        return $response;
    }
    /*For search setting:to add fields for searching*/
    public function getfieldsAction()
    {
        $response = $this->getResponse();
        if ($this->getRequest()->isXmlHttpRequest()) {
            $post = $this->getRequest()->getPost()->toArray();
            $trackerId = $post['tracker_id'];
            $actionId = $post['form_id'];
            $fieldsArray = $this->getTrackerService()->trackerCheckFields($trackerId, $actionId);
            $response->setContent(\Zend\Json\Json::encode($fieldsArray));
            return $response;
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_200);
            $response->setContent('Your dont have permission to access this.');
            return $response;
        }
    }

    public function fetchAllDataAction()
    {
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $formId = $this->params()->fromRoute('action_id', 0);
        $tableName='form_'.$trackerId.'_'.$formId;
        $allData=$this->getTrackerService()->fetchAllData($tableName);
        foreach ($allData as $key => $value) {
            $allData[$key]['action']=$value['id'];
        }
        $allDataJSON = json_encode($allData);
        echo $allDataJSON;
        die;
    }
    public function getAllColumnNameAction()
    { 
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $formId = $this->params()->fromRoute('action_id', 0);
        $formId = $this->params()->fromRoute('action_id', 0);
        $subaction_id = $this->params()->fromRoute('subaction_id', 0);
        $tableName='form_'.$trackerId.'_'.$formId;
        $columnName=$this->getTrackerService()->fetchAllColumnName($formId);
        
        $columnDefs=array();
        if ($subaction_id==0) {
            $columnDefs[0]['headerName']="Action";
            $columnDefs[0]['field']="action";
            $columnDefs[0]['lockPosition']=true; 
            $columnDefs[0]['suppressSorting']=true;
            $columnDefs[0]['suppressMenu']=true;
            $columnDefs[0]['filter']="false";
            $columnDefs[0]['width']=150;
            $columnDefs[0]['cellRenderer']="myCellRenderer";
            $columnDefs[0]['suppressColumnVirtualisation']=false;
            foreach ($columnName as $key => $arrvalue) {
                // if ($key==0) {
                //     $columnDefs[$key+1]['sort']="desc";
                // } 
                $columnDefs[$key+1]['headerName']=$arrvalue['label'];
                $columnDefs[$key+1]['field']=$arrvalue['field_name'];
                $columnDefs[$key+1]['headerTooltip']=$arrvalue['label'];
                //$columnDefs[$key+1]['suppressMenu']=false; 
            }
        } else {
            foreach ($columnName as $key => $arrvalue) {
                $columnDefs[$key]['headerName']=$arrvalue['label'];
                $columnDefs[$key]['field']=$arrvalue['field_name'];
                $columnDefs[$key]['headerTooltip']=$arrvalue['label']; 
            }
        }
        $columnDefsJSON = json_encode($columnDefs);
        echo $columnDefsJSON;
        die;
    }
    public function importRecordsAction()
    {
        $userContainer = new Container('user');
        $trackerContainer = new Container('tracker');
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $actionId = $this->params()->fromRoute('action_id', 0);
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $roleName = $userDetails['group_name'];
            if (isset($trackerContainer->tracker_user_groups) && $roleName != 'SuperAdmin') {
                $trackerUserGroups = $trackerContainer->tracker_user_groups;
                $roleName = $trackerUserGroups[$trackerId]['session_group'];
                $roleId = $trackerUserGroups[$trackerId]['session_group_id'];
            }
            $trackerIds = $trackerContainer->tracker_ids;
            $trackerResults = $this->getTrackerService()->trackerRsults($trackerId);
            $checkFormid = in_array($actionId, array_column($trackerResults['forms'], 'form_id')); 
            if ($trackerId == 0 || $checkFormid == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                if (in_array($trackerId, $trackerIds)) {
                    $this->layout()->setVariables(
                        array(
                        'tracker_id' => $trackerId,
                        'form_id'   => $actionId
                            )
                    );
                    return new ViewModel(
                        array(
                        'trackerRsults' => $trackerResults,
                        'action_id' => $actionId,
                        'tracker_id' => $trackerId,
                        'form_records' => array(),
                        )
                    );
                } else {
                    return $this->redirect()->toRoute('tracker');
                }
            }
            exit;
        }
        exit;
    }

}
