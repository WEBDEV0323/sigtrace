<?php

namespace Common\Tracker\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;

class CodelistController extends AbstractActionController
{
    protected $_modelService;
    protected $_accessModelService;
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
            $this->_modelService = $sm->get('Codelist\Model');
        }
        return $this->_modelService;
    }

    public function getAccessModelService()
    {
        if (!$this->_accessModelService) {
            $sm = $this->getServiceLocator();
            $this->_accessModelService = $sm->get('Tracker\Model\AccessModule');
        }
        return $this->_accessModelService;
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
    
    public function codelistManagementAction() 
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            if ($this->isHavingTrackerAccess($trackerId)) {
                $codelists = $this->getModelService()->getCodeList($trackerId);
                $trackerRsults = $this->getModelService()->trackerResults($trackerId);
                $formId = $trackerRsults['forms'][0]['form_id'];
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
                return new ViewModel(
                    array(
                        'codelists' => $codelists,
                        'trackerId' => $trackerId,
                        'trackerRsults' => $trackerRsults
                    )
                ); 
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $response->setContent('You do not have permission to access this tracker');
                return $response;
            }  
        } 
    }
    
    public function getoptionsbycodelistAction()
    {
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            $dataArr = $post;
            $resArr = $this->getModelService()->getoptionsbycodelist($dataArr);
        } else {
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        echo json_encode($resArr);
        exit;
    }
    public function addNewCodelistAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $cId = 0;
        if ($post) {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
                $responseCode = 0;
            } else {
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $match = "/^[a-zA-Z0-9 ']+$/";
                    if (!preg_match($match, $post['newCodeList'])) {
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                        $errMessage = ($post['newCodeList'] == '') ? "Code List Name cannot be empty" : "Invalid Code Name";
                        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => 0, 'errMessage'=>$errMessage, 'cId'=>$cId)));
                        return $response;
                    }
                    $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
                    $reason = isset($post['reason'])?htmlentities($post['reason']):'';
                    $userDetails = $userContainer->user_details;
                    $result = $this->getModelService()->addNewCodelist($post);
                    if ($result['responseCode'] == 1) {
                        $cId = $result['cId'];
                        $this->flashMessenger()->addMessage(array('success' => $result['errMessage']));
                        $trackerData = $this->getModelService()->getTrackerDetails($post['trackerId']);
                        $this->getAuditService()->saveToLog($result['cId'], $userDetails['u_name'], "add codelist", "", $post['newCodeList'], $reason, 'success', $trackerData['client_id']);
                    }
                    $responseCode = $result['responseCode'];
                    $errMessage = $result['errMessage'];
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    $errMessage = 'You do not have permission to access this tracker';
                    $responseCode = 0;
                }   
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = "Access Denied";  
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage, 'cId'=>$cId)));
        return $response;
    }
    
    public function editCodelistAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
                $responseCode = 0;
            } else {
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $userDetails = $userContainer->user_details;
                    $match = "/^[a-zA-Z0-9 ']+$/";
                    if (!preg_match($match, $post['editCodeList'])) {
                        $errMessage = ($post['editCodeList'] == '') ? "Code List Name cannot be empty" : "Invalid Code Name";
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => 0, 'errMessage'=>$errMessage)));
                        return $response;
                    }
                    $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
                    $reason = isset($post['reason'])?htmlentities($post['reason']):'';
                    $resultcode = (int)$this->getModelService()->getCodelistInfo($post['editCodeListId']);
                    $result = $this->getModelService()->editCodelist($post); 
                    if ($result['responseCode'] == 1) {
                        $this->flashMessenger()->addMessage(array('success' => $result['errMessage']));
                        $trackerData = $this->getModelService()->getTrackerDetails($post['trackerId']);
                        $this->getAuditService()->saveToLog($result['cId'], $userDetails['u_name'], 'edit codelist', $resultcode['code_list_name'], $post['editCodeList'], $reason, 'success', $trackerData['client_id']);
                    }
                    $responseCode = $result['responseCode'];
                    $errMessage = $result['errMessage'];
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    $errMessage = 'You do not have permission to access this tracker';
                    $responseCode = 0;
                }   
            }    
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = "Access Denied";
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
    
    public function deleteCodelistAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        
        $post = $this->getRequest()->getPost()->toArray();
        $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
        if ($post) {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
                $responseCode = 0;
            } else {
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $userDetails = $userContainer->user_details;
                    $reason = isset($post['reason'])?$post['reason']:'';
                    $codeListId = isset($post['codeListID'])?intval($post['codeListID']):0;
                    $resultcode = $this->getModelService()->getCodelistInfo($codeListId);
                    $codelistOptions = $this->getModelService()->getOptionsByCodelistId($codeListId);
                    $options = array_column($codelistOptions, 'label');
                    $kpi = array_column($codelistOptions, 'kpi');
                    $result = $this->getModelService()->deleteCodelist($post); 
                    if ($result['responseCode'] == 1) {
                        $this->flashMessenger()->addMessage(array('success' => $result['errMessage']));
                        $trackerData = $this->getModelService()->getTrackerDetails($post['trackerId']);
                        if (!empty($resultcode)) {
                            $this->getAuditService()->saveToLog($result['cId'], $userDetails['u_name'], 'delete codelist', "{codelist_name:'".$resultcode['code_list_name']."', options: {'".implode("','", $options)."'}, KPI:{'".implode("','", $kpi)."'}}", "", $reason, 'success', $trackerData['client_id']);
                        }
                    }
                    $responseCode = $result['responseCode'];
                    $errMessage = $result['errMessage'];
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    $errMessage = 'You do not have permission to access this tracker';
                    $responseCode = 0;
                }  
            }    
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = "Access Denied";
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
    public function addCodelistOptionsAction()
    {
        
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $optionIds = '0';
        
        $post = $this->getRequest()->getPost()->toArray();  
        $post = filter_var_array($post, FILTER_SANITIZE_STRING);
        if ($post) {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
                $responseCode = 0;
            } else {
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $match = "/^[a-zA-Z0-9 ']+$/";
                    if (isset($post['names']) && count($post['names']) > 0) {
                        foreach ($post['names'] as $key => $value) {
                            if ($value == '' || !preg_match($match, $value)) {
                                $errMessage = "Codelist Option details are not correct";
                                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                                $response->setContent(\Zend\Json\Json::encode(array('responseCode' => 0, 'errMessage'=>$errMessage, 'optionIds' => $optionIds)));
                                return $response;                            
                            }
                        }
                    } else {
                        $errMessage = "Codelist Option details are not correct";;
                        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => 0, 'errMessage'=>$errMessage, 'optionIds' => $optionIds)));
                        return $response;                                                    
                    }
                    $userDetails = $userContainer->user_details;
                    $reason = isset($post['reason'])?$post['reason']:'';
                    $codeListId = isset($post['code_list_id'])?intval($post['code_list_id']):0;
                    $result = $this->getModelService()->addCodelistOptions($post); 
                    if ($result['responseCode'] == 1) {
                        $optionIds = $result['optionIds'];
                        $trackerData = $this->getModelService()->getTrackerDetails($post['tracker_id']);
                        $optionNamesArr = isset($post['names'])?$post['names']:array();
                        $kpiArr = isset($post['kpi'])?$post['kpi']:array();
                        if (!empty($optionNamesArr)) {
                            $this->getAuditService()->saveToLog("{".$result['optionIds']."}", $userDetails['u_name'], 'add codelist option', "", "{code_list_id = $codeListId, options: {'".implode("','", $optionNamesArr)."'}, KPI:{'".implode("','", $kpiArr)."'}}", $reason, 'success', $trackerData['client_id']);
                        }
                    }
                    $responseCode = $result['responseCode'];
                    $errMessage = $result['errMessage'];
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    $errMessage = 'You do not have permission to access this tracker';
                    $responseCode = 0;
                }  
            }    
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = "Access Denied";
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage, 'optionIds' => $optionIds)));
        return $response;
    }
    
    public function editCodelistOptionsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        
        $post = $this->getRequest()->getPost()->toArray();
        $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
        $optionIds = '0';
        if ($post) {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
                $responseCode = 0;
            } else {
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $userDetails = $userContainer->user_details;
                    $reason = isset($post['reason'])?$post['reason']:'';
                    $codeListId = isset($post['code_list_id'])?intval($post['code_list_id']):0;
                    $codelistOptions = $this->getModelService()->getOptionsByCodelistId($codeListId); 
                    $result = $this->getModelService()->editCodelistOptions($post); 
                    if ($result['responseCode'] == 1) {
                        $optionIds = $result['optionIds'];
                        $trackerData = $this->getModelService()->getTrackerDetails($post['tracker_id']);
                        $optionNamesArr = isset($post['names'])?$post['names']:array();
                        $rmvdOptionArray = $remainOptionArray = array();
                        if ($result['removedIds'] != 0) {
                            foreach (explode(",", $result['removedIds']) as $removedId) {
                                $rmvdOptionArray[] = $codelistOptions[array_search($removedId, array_column($codelistOptions, 'option_id'))];
                            }
                            $options = array_column($rmvdOptionArray, 'label');
                            $kpi = array_column($rmvdOptionArray, 'kpi');
                            $this->getAuditService()->saveToLog("{".$result['removedIds']."}", $userDetails['u_name'], 'delete codelist option', "{code_list_id = $codeListId, options: {'".implode("','", $options)."'}, KPI:{'".implode("','", $kpi)."'}}", "", $reason, 'success', $trackerData['client_id']);
                        }
                        if (!empty($optionNamesArr) && !empty($codelistOptions)) { 
                            foreach ($post['option_ids'] as $optId) {
                                $remainOptionArray[] = $codelistOptions[array_search($optId, array_column($codelistOptions, 'option_id'))];
                            }
                            $kpiArr = isset($post['kpi'])?$post['kpi']:array();
                            $options = array_column($remainOptionArray, 'label');
                            $kpi = array_column($remainOptionArray, 'kpi');
                            $this->getAuditService()->saveToLog("{".$result['optionIds']."}", $userDetails['u_name'], 'edit codelist option', "{code_list_id = $codeListId, options: {'".implode("','", $options)."'}, KPI:{'".implode("','", $kpi)."'}}", "{code_list_id = $codeListId, options: {'".implode("','", $optionNamesArr)."'}, KPI:{'".implode("','", $kpiArr)."'}}", $reason, 'success', $trackerData['client_id']);
                        }
                    }
                    $responseCode = $result['responseCode'];
                    $errMessage = $result['errMessage'];
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    $errMessage = 'You do not have permission to access this tracker';
                    $responseCode = 0;
                }  
            }    
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = "Access Denied";
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage, 'optionIds' => $optionIds)));
        return $response; 
    }
    
    public function deleteCodelistOptionAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        
        $post = $this->getRequest()->getPost()->toArray();
        $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
        if ($post) {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
                $responseCode = 0;
            } else {
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $userDetails = $userContainer->user_details;
                    $reason = isset($post['reason'])?$post['reason']:'';
                    $codeListOptionId = isset($post['optionId'])?intval($post['optionId']):0;
                    $resultcode = $this->getModelService()->getOptionsByCodelistOptionId($codeListOptionId); 
                    $result = $this->getModelService()->deleteCodelistOption($codeListOptionId); 
                    if ($result['responseCode'] == 1) {
                        $trackerData = $this->getModelService()->getTrackerDetails($post['tracker_id']);
                        if (!empty($resultcode)) {
                            $this->getAuditService()->saveToLog($codeListOptionId, $userDetails['u_name'], 'delete codelist option', "{code_list_id:".$resultcode['code_list_id'].", option:".$resultcode['label'].", KPI:".$resultcode['kpi']."}", "", $reason, 'success', $trackerData['client_id']);
                        }
                    }
                    $responseCode = $result['responseCode'];
                    $errMessage = $result['errMessage'];
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    $errMessage = 'You do not have permission to access this tracker';
                    $responseCode = 0;
                }  
            }    
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $responseCode = 0;
            $errMessage = "Access Denied";
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
}
