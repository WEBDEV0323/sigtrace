<?php

namespace ASRTRACE\Tracker\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;
use Application\Controller\IndexController;

class CodelistController extends AbstractActionController
{
    protected $_modelService;
    protected $_accessModelService;

    public function getModelService()
    {
        if (!$this->_modelService) {
            $sm = $this->getServiceLocator();
            $this->_modelService = $sm->get('Tracker\Model\Codelist');
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
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            
            if ($this->isAdmin($trackerId)) { 
                $codelists = $this->getModelService()->getCodeList($trackerId);
            }
            $trackerRsults = $this->getAccessModelService()->trackerRsults($trackerId);
            $formId = $trackerRsults['forms'][0]['form_id'];
            $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
            return new ViewModel(
                array(
                    'codelists' => $codelists,
                    'trackerId' => $trackerId,
                    'trackerRsults' => $trackerRsults
                )
            ); 
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
        if ($post) {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
                $responseCode = 0;
            } else {
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
                if ($this->isAdmin($trackerId)) {
                    $reason = isset($post['reason'])?$post['reason']:'';
                    $userDetails = $userContainer->user_details;
                    $result = $this->getModelService()->addNewCodelist($post);
                    if ($result['responseCode'] == 1) {
                        $this->flashMessenger()->addMessage(array('success' => $result['errMessage']));
                        $applicationController = new IndexController();
                        $trackerData = $this->getModelService()->getTrackerDetails($post['trackerId']);
                        $applicationController->saveToLogFile($result['cId'], $userDetails['u_name'], "add codelist", "", $post['newCodeList'], $reason, 'success', $trackerData['client_id']);
                    }
                    $responseCode = $result['responseCode'];
                    $errMessage = $result['errMessage'];
                } 
            }
        } else {
            $responseCode = 0;
            $errMessage = "Access Denied";  
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
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
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
                if ($this->isAdmin($trackerId)) {
                    $userDetails = $userContainer->user_details;
                    $reason = isset($post['reason'])?$post['reason']:'';
                    $resultcode = $this->getModelService()->getCodelistInfo($post['editCodeListId']);
                    $result = $this->getModelService()->editCodelist($post); 
                    if ($result['responseCode'] == 1) {
                        $this->flashMessenger()->addMessage(array('success' => $result['errMessage']));
                        $applicationController = new IndexController();
                        $trackerData = $this->getModelService()->getTrackerDetails($post['trackerId']);
                        $applicationController->saveToLogFile($result['cId'], $userDetails['u_name'], 'edit codelist', $resultcode['code_list_name'], $post['editCodeList'], $reason, 'success', $trackerData['client_id']);
                    }
                    $responseCode = $result['responseCode'];
                    $errMessage = $result['errMessage'];
                }
            }    
        } else {
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
        
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
                $responseCode = 0;
            } else {
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
                if ($this->isAdmin($trackerId)) {
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
                        $applicationController = new IndexController();
                        $trackerData = $this->getModelService()->getTrackerDetails($post['trackerId']);
                        if (!empty($resultcode)) {
                            $applicationController->saveToLogFile($result['cId'], $userDetails['u_name'], 'delete codelist', "{codelist_name:'".$resultcode['code_list_name']."', options: {'".implode("','", $options)."'}, KPI:{'".implode("','", $kpi)."'}}", "", $reason, 'success', $trackerData['client_id']);
                        }
                    }
                    $responseCode = $result['responseCode'];
                    $errMessage = $result['errMessage'];
                }
            }    
        } else {
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
        
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if ($post) {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
                $responseCode = 0;
            } else {
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
                if ($this->isAdmin($trackerId)) {
                    $userDetails = $userContainer->user_details;
                    $reason = isset($post['reason'])?$post['reason']:'';
                    $codeListId = isset($post['code_list_id'])?intval($post['code_list_id']):0;
                    $result = $this->getModelService()->addCodelistOptions($post); 
                    if ($result['responseCode'] == 1) {
                        $applicationController = new IndexController();
                        $trackerData = $this->getModelService()->getTrackerDetails($post['tracker_id']);
                        $optionNamesArr = isset($post['names'])?$post['names']:array();
                        $kpiArr = isset($post['kpi'])?$post['kpi']:array();
                        if (!empty($optionNamesArr)) {
                            $applicationController->saveToLogFile("{".$result['optionIds']."}", $userDetails['u_name'], 'add codelist option', "", "{code_list_id = $codeListId, options: {'".implode("','", $optionNamesArr)."'}, KPI:{'".implode("','", $kpiArr)."'}}", $reason, 'success', $trackerData['client_id']);
                        }
                    }
                    $responseCode = $result['responseCode'];
                    $errMessage = $result['errMessage'];
                }
            }    
        } else {
            $responseCode = 0;
            $errMessage = "Access Denied";
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
    
    public function editCodelistOptionsAction()
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
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
                if ($this->isAdmin($trackerId)) {
                    $userDetails = $userContainer->user_details;
                    $reason = isset($post['reason'])?$post['reason']:'';
                    $codeListId = isset($post['code_list_id'])?intval($post['code_list_id']):0;
                    $codelistOptions = $this->getModelService()->getOptionsByCodelistId($codeListId); 
                    $result = $this->getModelService()->editCodelistOptions($post); 
                    if ($result['responseCode'] == 1) {
                        $applicationController = new IndexController();
                        $trackerData = $this->getModelService()->getTrackerDetails($post['tracker_id']);
                        $optionNamesArr = isset($post['names'])?$post['names']:array();
                        $rmvdOptionArray = $remainOptionArray = array();
                        if ($result['removedIds'] != 0) {
                            foreach (explode(",", $result['removedIds']) as $removedId) {
                                $rmvdOptionArray[] = $codelistOptions[array_search($removedId, array_column($codelistOptions, 'option_id'))];
                            }
                            $options = array_column($rmvdOptionArray, 'label');
                            $kpi = array_column($rmvdOptionArray, 'kpi');
                            $applicationController->saveToLogFile("{".$result['removedIds']."}", $userDetails['u_name'], 'delete codelist option', "{code_list_id = $codeListId, options: {'".implode("','", $options)."'}, KPI:{'".implode("','", $kpi)."'}}", "", $reason, 'success', $trackerData['client_id']);
                        }
                        if (!empty($optionNamesArr) && !empty($codelistOptions)) { 
                            foreach ($post['option_ids'] as $optId) {
                                $remainOptionArray[] = $codelistOptions[array_search($optId, array_column($codelistOptions, 'option_id'))];
                            }
                            $kpiArr = isset($post['kpi'])?$post['kpi']:array();
                            $options = array_column($remainOptionArray, 'label');
                            $kpi = array_column($remainOptionArray, 'kpi');
                            $applicationController->saveToLogFile("{".$result['optionIds']."}", $userDetails['u_name'], 'edit codelist option', "{code_list_id = $codeListId, options: {'".implode("','", $options)."'}, KPI:{'".implode("','", $kpi)."'}}", "{code_list_id = $codeListId, options: {'".implode("','", $optionNamesArr)."'}, KPI:{'".implode("','", $kpiArr)."'}}", $reason, 'success', $trackerData['client_id']);
                        }
                    }
                    $responseCode = $result['responseCode'];
                    $errMessage = $result['errMessage'];
                }
            }    
        } else {
            $responseCode = 0;
            $errMessage = "Access Denied";
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response; 
    }
    
    public function deleteCodelistOptionAction()
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
                $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
                if ($this->isAdmin($trackerId)) {
                    $userDetails = $userContainer->user_details;
                    $reason = isset($post['reason'])?$post['reason']:'';
                    $codeListOptionId = isset($post['optionId'])?intval($post['optionId']):0;
                    $resultcode = $this->getModelService()->getOptionsByCodelistOptionId($codeListOptionId); 
                    $result = $this->getModelService()->deleteCodelistOption($codeListOptionId); 
                    if ($result['responseCode'] == 1) {
                        $applicationController = new IndexController();
                        $trackerData = $this->getModelService()->getTrackerDetails($post['tracker_id']);
                        if (!empty($resultcode)) {
                            $applicationController->saveToLogFile($codeListOptionId, $userDetails['u_name'], 'delete codelist option', "{code_list_id:".$resultcode['code_list_id'].", option:".$resultcode['label'].", KPI:".$resultcode['kpi']."}", "", $reason, 'success', $trackerData['client_id']);
                        }
                    }
                    $responseCode = $result['responseCode'];
                    $errMessage = $result['errMessage'];
                }
            }    
        } else {
            $responseCode = 0;
            $errMessage = "Access Denied";
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage)));
        return $response;
    }
}
