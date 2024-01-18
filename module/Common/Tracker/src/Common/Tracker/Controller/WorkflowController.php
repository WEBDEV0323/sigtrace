<?php

namespace Common\Tracker\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;

class WorkflowController extends AbstractActionController
{
    protected $_modelService;
    protected $_auditService;
    
    public function getAuditService()
    {
        if (!$this->_auditService) {
            $sm = $this->getServiceLocator();
            $this->_auditService = $sm->get('Audit\Service');
        }
        return $this->_auditService;
    }

    public function getModelService()
    {
        if (!$this->_modelService) {
            $sm = $this->getServiceLocator();
            $this->_modelService = $sm->get('Workflow\Model');
        }
        return $this->_modelService;
    }
    public function isHavingTrackerAccess($trackerId)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $isSuperAdmin = isset($userDetails['isSuperAdmin']) ? $userDetails['isSuperAdmin'] : 0;
        if ($isSuperAdmin == 1 || ($userContainer->trackerRoles != '' && in_array($trackerId, array_keys($userContainer->trackerRoles)))) {
            return true;
        }
        return false;
    }
    public function isAdmin($trackerId) 
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        $userDetails = $userContainer->user_details;
        $roleId = isset($userDetails['group_id'])?$userDetails['group_id']:0; 
        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        $sessionGroup = isset($trackerUserGroups[$trackerId]['session_group'])?$trackerUserGroups[$trackerId]['session_group']:'';
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            return $this->redirect()->toRoute('tracker');
        } 
        return true;
    }
    
    public function workflowManagementAction() 
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'   => $formId));
            if ($this->isHavingTrackerAccess($trackerId) && $formId > 0) { 
                $checkTableArray = $this->getModelService()->trackerCheckForms($trackerId, $formId);
                $formDetails = $checkTableArray['form_details'];
                $responseCode = $checkTableArray['responseCode'];
                if ($responseCode == 0) {
                    $response->setContent('You do not have permission to access this form data');
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    return $response;
                }
                $workflowArray = $this->getModelService()->trackerCheckWorkFlows($formId); 
                $trackerResults = $this->getModelService()->trackerResults($trackerId);
                $codeLists = $this->getModelService()->getCodeList($trackerId);
                $roles = $this->getModelService()->getRoleForTracker($trackerId);
                return new ViewModel(
                    array(
                        'trackerResults' => $trackerResults,
                        'code_lists' => $codeLists,
                        'roles' => $roles,
                        'formId' => $formId,
                        'trackerId' => $trackerId,
                        'form_details' => $formDetails,
                        'workflow_array' => $workflowArray
                    )
                ); 
            } else {
                $response->setContent('You do not have permission to access this tracker');
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                return $response;
            }
            
        }
    }
    public function addAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $trackerContainer = $session->getSession("tracker");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'   => $formId));
            if ($this->isHavingTrackerAccess($trackerId) && $formId > 0) {
                $checkTableArray = $this->getModelService()->trackerCheckForms($trackerId, $formId);
                $formDetails = $checkTableArray['form_details'];
                $responseCode = $checkTableArray['responseCode'];
                if ($responseCode == 0) {
                    $response->setContent('You do not have permission to access this form data');
                    return $response;
                }
                $masterWorkflowArray = $this->getModelService()->getMasterWorkFlows();
                $greatestSortNumber = $this->getModelService()->getsortlargest($formId);
                $trackerResult = $this->getModelService()->trackerResults($trackerId);
                return new ViewModel(
                    array(
                    'trackerRsults' => $trackerResult,
                    'formId' => $formId,
                    'trackerId' => $trackerId,
                    'form_details' => $formDetails,
                    'master_workflow_array' => $masterWorkflowArray,
                    'max_sort_number' => $greatestSortNumber
                        )
                );
            } else {
                $response->setContent('You do not have permission to access this tracker');
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                return $response;
            }
        }
    }
    public function addUpdateWorkflowAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        
        $post  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $flag = 0;
        if (!empty($post)) {
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $responseCode = 0;
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $formId = (int) $this->getEvent()->getRouteMatch()->getParam('form_id', 0);
                    $workflowId = (int) $this->getEvent()->getRouteMatch()->getParam('workflow_id', 0);
                    $type = ($workflowId > 0)?"edit":"add";
                    $workflowData = $this->getModelService()->getWorkflowInformation($workflowId);
                    $wfNames = $post['wfNames'];
                    $match = "/^[a-zA-Z][a-zA-Z0-9 ]+$/"; 
                    foreach ($wfNames as $key => $value) {
                        if (preg_match($match, $value)) { 
                            $checkForExist = $this->getModelService()->checkWorkflowExist($formId, $value, $workflowId, $type);
                            if ($checkForExist > 0) {
                                $flag = 1;
                                break;
                            }
                        } else {
                            $flag = 2; 
                            break;
                        } 
                    }
                    if ($flag == 0) {
                        $reason = isset($post['reason'])?$post['reason']:'';
                        $userDetails = $userSession->user_details;
                        $resultset =  $this->getModelService()->saveWorkflow($post);
                        $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                        if ($resultset['responseCode'] == 1) {
                            $wfSortOrder = $post['wfSortOrder'];
                            $this->getAuditService()->saveToLog($resultset['ids'], $userDetails['email'], 'Add Workflow', "", "{'workflow_name':{'".implode("','", $post['wfNames'])."'}}", $reason, 'Success', $trackerData['client_id']);
                            $this->flashMessenger()->addMessage(array('success' => 'Workflow added successfully!'));
                            $responseCode = 1;
                            $errMessage = "Workflow added successfully!";
                        } else if ($resultset['responseCode'] == 2) {
                            $this->getAuditService()->saveToLog($post['workflowId'], $userDetails['email'], 'Update Workflow', $workflowData['workflow_name'], implode(",", $post['wfNames']), $reason, 'Success', $trackerData['client_id']);
                            $this->flashMessenger()->addMessage(array('success' => 'Workflow updated successfully!'));
                            $responseCode = 1;
                            $errMessage = "Workflow edited successfully!";
                        } else {
                            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_501);
                            $this->flashMessenger()->addMessage(array('error' => $resultset['errMessage']));
                            $responseCode = 0;
                            $errMessage = $resultset['errMessage'];
                        }
                    } else if ($flag == 2) {
                        $responseCode = 0;
                        $errMessage = 'Invalid Workflow Name';                                            
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    } else {
                        $responseCode = 0;
                        $errMessage = 'Workflow name already exists';
                        
                    } 
                } else {
                    $responseCode = 0;
                    $errMessage = 'You do not have permission to access this tracker';
                } 
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = 'Method Not Allowed';
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
    public function deleteAction()
    { 
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $post  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if (!empty($post)) {
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            } else {
                $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $reason = isset($post['reason'])?$post['reason']:'';
                    $userDetails = $userSession->user_details;
                    if (strval(intval($post['workflowID'])) == $post['workflowID'] && strval(intval($post['trackerId'])) == $post['trackerId'] && strval(intval($post['formId'])) == $post['formId']) {
                        $workflowId = htmlspecialchars(filter_var($post['workflowID'], FILTER_SANITIZE_STRING), ENT_QUOTES);
                        $workflowData = $this->getModelService()->getWorkflowInformation($workflowId);
                        $resultset = $this->getModelService()->deleteWorkflow($post);
                        if ($resultset['responseCode'] == 1) {
                            $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                            $this->getAuditService()->saveToLog($post['workflowID'], $userDetails['email'], 'Delete Workflow', "{'workflow_name': '".$workflowData['workflow_name']."', 'fields':{'".implode("','", $resultset['fields'])."'}}", "", $reason, 'Success', $trackerData['client_id']);
                            $this->flashMessenger()->addMessage(array('success' => 'Workflow deleted successfully!'));
                            $response->setContent(\Zend\Json\Json::encode('Deleted'));
                        } else {
                            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_501);
                            $this->flashMessenger()->addMessage(array('error' => 'Workflow not deleted due to an error'));
                            $response->setContent('Workflow not deleted due to an error');
                        }
                    } else {
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_501);
                        $this->flashMessenger()->addMessage(array('error' => 'Workflow received invalid data'));
                        $response->setContent('Workflow received invalid data');                        
                    }
                } else {
                    $response->setContent('You do not have permission to access this tracker');
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                } 
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $response->setContent('Method Not Allowed');
        }
        return $response;
    }
    public function changeOrderAction() 
    {
        
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING); //echo "<pre>"; print_r($post); die;
        $responseCode=0;
        $errMessage='';
        if (!empty($post)) {
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
                $responseCode = 0;
            } else {
                $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $reason = isset($post['reason'])?$post['reason']:'';
                    $dataArray['sort_order'] = $post['wf_sort_order'];
                    $dataArray['workflow_id'] = $post['wf_id_for_sort'];
                    $i = 0;
                    foreach ($dataArray as $key => $value) {
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
                    array_multisort($temparray, SORT_ASC, $postarray);
                    $userDetails = $userSession->user_details;
                    $workflowArray = $this->getModelService()->getWorkFlows($post['formId']);
                    $resArr = $this->getModelService()->updateWorkflowSorting($post);
                    if ($resArr['responseCode'] == 1) {
                        $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                        for ($i = 0; $i < count($postarray); $i++) {
                            if ($postarray[$i]['workflow_id'] == $workflowArray[$i]['workflow_id'] && $postarray[$i]['sort_order'] != $workflowArray[$i]['sort_order']) {
                                $this->getAuditService()->saveToLog($workflowArray[$i]['workflow_id'], $userDetails['email'], 'sort_order', $postarray[$i]['sort_order'], $workflowArray[$i]['sort_order'], $reason, 'Success', $trackerData['client_id']);
                            }
                        } 
                        $responseCode = 1;
                        $errMessage = "Workflows were reordered successfully!";
                        $this->flashMessenger()->addMessage(array('success' => $errMessage));
                    } else {
                        $responseCode = 0;
                        $errMessage = $resArr['errMessage'];
                    }
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    $response->setContent('You do not have permission to access this tracker');
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $response->setContent('Method Not Allowed');
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
    public function getFieldsByWorkflowIdAction() 
    {
        $response = $this->getResponse();
        $post  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $result = array();
        if ($post) {
            $result = $this->getModelService()->getFieldsByWorkflowId($post);
        }
        $response->setContent(\Zend\Json\Json::encode($result));
        return $response;
    }
    public function getmaxfieldAction()
    {
        $post  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            $resArray = $this->getModelService()->getMaxFieldSortNumber($post);
            echo json_encode($resArray);
        } else {
            echo "Access denied";
        }
        exit;
    }
    
    
    /* Workflow Rules */
    public function settingsAction()
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
            $rules = $this->getModelService()->getWorkflowRule($trackerId);
            $trackerResults = $this->getModelService()->trackerResults($trackerId);
            $formId = (isset($trackerResults['forms']))?$trackerResults['forms'][0]['form_id']:0;
            $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
            if ($this->isAdmin($trackerId)) {
                return new ViewModel(
                    array(
                    'trackerResults' => $trackerResults,
                    'workflowRules' => $rules,
                    'trackerId' => $trackerId
                    )
                );
            }
        }
    }
    public function deleteRuleAction()
    {
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray(); 
        $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
        if ($post) {
            $session = new SessionContainer();
            $userSession = $session->getSession("user");
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $responseCode = 0;
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
                if ($this->isAdmin($trackerId)) {
                    $ruleId = isset($post['ruleId'])?intval($post['ruleId']):0;
                    $formId = isset($post['formId'])?intval($post['formId']):0;
                    $resArr = $this->getModelService()->deleteRule($ruleId, $formId);
                    switch ($resArr['responseCode']) {
                    case 1:
                        $reason = isset($post['reason'])?$post['reason']:'';
                        $userDetails = $userSession->user_details;
                        $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                        $this->getAuditService()->saveToLog($ruleId, $userDetails['email'], 'Delete Workflow Rule', $resArr['actual'], "", $reason, 'Success', $trackerData['client_id']);
                        $this->flashMessenger()->addMessage(array('success' => $resArr['errMessage']));
                        $responseCode = $resArr['responseCode'];
                        $errMessage = $resArr['errMessage'];
                        break;
                    default:
                        $responseCode = $resArr['responseCode'];
                        $errMessage = $resArr['errMessage'];
                        break; 
                    }
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = 'Method Not Allowed';
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
    public function getWorkflowsAndFieldsAction()
    {
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray(); 
        $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
        $fieldsArray = $workflowArray = array();
        if ($post) {
            $session = new SessionContainer();
            $userSession = $session->getSession("user");
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $responseCode = 0;
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
                if ($this->isAdmin($trackerId)) { 
                    $formId = isset($post['form_id'])?(int)$post['form_id']:0;
                    $fieldsArray = $this->getModelService()->getFields($formId);
                    $workflowArray = $this->getModelService()->getWorkFlows($formId);
                    $responseCode = 1;
                    $errMessage = 'successfully fetched';
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = 'Method Not Allowed';
        }
        $response->setContent(\Zend\Json\Json::encode(array('workflows' => $workflowArray, 'fields'=>$fieldsArray,'responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
    public function saveRuleAction()
    {
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray();
        $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
        if ($post) {
            $session = new SessionContainer();
            $userSession = $session->getSession("user");
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $responseCode = 0;
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
                if ($this->isAdmin($trackerId)) { 
                    $result = $this->getModelService()->saveRule($post);
                    switch ($result['responseCode']) {
                    case 1:
                        $reason = isset($post['reason'])?$post['reason']:'';
                        $userDetails = $userSession->user_details;
                        $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                        switch ($result['action']) {
                        case 'adding':
                            $this->getAuditService()->saveToLog($result['ruleId'], $userDetails['email'], 'Add Workflow Rule', "", $result['new'], $reason, 'Success', $trackerData['client_id']);
                            break;
                        case 'updating':
                            $this->getAuditService()->saveToLog($result['ruleId'], $userDetails['email'], 'Update Workflow Rule', $result['old'], $result['new'], $reason, 'Success', $trackerData['client_id']);
                            break;
                        default:
                            break;
                        }
                        $this->flashMessenger()->addMessage(array('success' => $result['errMessage']));
                        $responseCode = $result['responseCode'];
                        $errMessage = $result['errMessage'];
                        break;
                    default:
                        $responseCode = $result['responseCode'];
                        $errMessage = $result['errMessage'];
                        break; 
                    }
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = 'Method Not Allowed';
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
    
    public function getRuleInfoAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $fieldsArray = array();
        $responseCode = 0; $errMessage = "";
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
            if ($post) {
                $fieldsArray = $this->getModelService()->getRuleInfo($post);
                $responseCode = 1;
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $errMessage = 'Method Not Allowed';
            }
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage, 'data'=>$fieldsArray)));
        return $response;
    }
    
    public function savefieldsAction()
    {
        set_time_limit(0);
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray();
        if ($post) {
            $session = new SessionContainer();
            $userSession = $session->getSession("user");
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $responseCode = 0;
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $match = "/^[a-zA-Z][a-zA-Z0-9 \-()]+$/";
                    if (isset($post['names']) && count($post['names']) > 0) {
                        foreach ($post['names'] as $key => $value) {
                            if (!preg_match($match, $value)) {
                                $responseCode = 0;
                                $errMessage = "Invalid Field Lable Name Entered";
                                $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
                                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                                return $response;                            
                            }
                        }
                    }
                    $result = $this->getModelService()->saveFields($post);
                    switch ($result['responseCode']) {
                    case 1:
                        $reason = isset($post['reason'])?$post['reason']:'';
                        $userDetails = $userSession->user_details;
                        $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                        switch ($result['action']) {
                        case 'adding':
                            $this->getAuditService()->saveToLog($result['fieldId'], $userDetails['email'], 'Add Bulk Fields', "", $result['new'], $reason, 'Success', $trackerData['client_id']);
                            break;
                        case 'updating':
                            $this->getAuditService()->saveToLog($result['fieldId'], $userDetails['email'], 'Update Bulk Fields', $result['old'], $result['new'], $reason, 'Success', $trackerData['client_id']);
                            break;
                        default:
                            break;
                        }
                        $this->flashMessenger()->addMessage(array('success' => $result['errMessage']));
                        $responseCode = $result['responseCode'];
                        $errMessage = $result['errMessage'];
                        break;
                    default:
                        $responseCode = $result['responseCode'];
                        $errMessage = $result['errMessage'];
                        break; 
                    }
                } else {
                    $responseCode = 0;
                    $errMessage = 'You do not have permission to access this tracker';
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = 'Method Not Allowed';
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
}

