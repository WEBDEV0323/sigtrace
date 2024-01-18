<?php
namespace ASRTRACE\Tracker\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;
use Application\Controller\IndexController;

class AccessController extends AbstractActionController
{
    
    protected $_adminMapper;
    protected $_reportMapper;
    protected $_userMapper;
    protected $_modelService;

    protected $_roleMapper;
    
    public function getRoleService()
    {
        if (!$this->_roleMapper) {
            $sm = $this->getServiceLocator();
            $this->_roleMapper = $sm->get('Role\Model\Role');
        }
        return $this->_roleMapper;
    }

    public function getModelService()
    {
        if (!$this->_modelService) {
            $sm = $this->getServiceLocator();
            $this->_modelService = $sm->get('Tracker\Model\AccessModule');
        }
        return $this->_modelService;
    }
    public function isAdmin($trackerId) 
    {
        $session = new SessionContainer();
        $userContainer= $session->getSession("user");
        $trackerContainer = $session->getSession("tracker");
        $userDetails = $userContainer->user_details;
        $roleId = isset($userDetails['group_id'])?$userDetails['group_id']:0; 
        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        $sessionGroup = isset($trackerUserGroups[$trackerId]['session_group'])?$trackerUserGroups[$trackerId]['session_group']:"";
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
            $formId = $this->params()->fromRoute('form_id', 0);
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
                    $result = $this->getModelService()->getWorkflowRoleForForms($trackerId);
                    $result[0] = $this->sortArrOfObj($result[0], 'workflow_id');
                    $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id'=>$formId));                    
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getModelService()->trackerRsults($trackerId),
                        'users' => $this->getModelService()->trackerUsers($trackerId),
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
        $userDetails = $userContainer->user_details;
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $dataArr = $this->getRequest()->getPost()->toArray();
            $applicationController = new IndexController();
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
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
                    foreach ($datapostarray as $key => $value) { //echo $key;die;
                        $applicationController->saveToLogFile($dataArr['role_id'], $userDetails['email'], 'Access Setting-For insert and delete', '', "{'".$key."': '".$datapostarray[$key]."'}", $comment, 'Session timeout:Failure', $trackerData['client_id']);
                    }
                    return $this->redirect()->toRoute('home');
                } else {
                    
                    $accessArr = $this->getModelService()->getaccesssetting($dataArr);
                    $resArr = $this->getModelService()->saveaccesssetting($dataArr);
                    $applicationController->saveToLogFile($dataArr['role_id'], $userDetails['email'], 'Access Setting-For insert and delete', json_encode($accessArr), json_encode($dataArr), $comment, 'Success', $trackerData['client_id']);                    
                }
            } else {
                // if (!isset($userContainer->u_id)) {
                //     $applicationController->saveToLogFile(0, '', '', '', 'Access Setting-For insert and delete', '', $trackerId, $actionId, 0, IP, '', 'Session Timeout,Post Array is blank:Failure');
                //     return $this->redirect()->toRoute('home');
                // } else {
                //     $userDetails = $userContainer->user_details;
                //     $applicationController->saveToLogFile(0, '', '', '', 'Access Setting-For insert and delete', $userDetails['u_name'], $trackerId, $actionId, 0, IP, '', 'Post Array is blank:Failure');
                // }
                $responseCode = 0;
                $errMessage = "Access Denied";
                $resArr['responseCode'] = $responseCode;
                $resArr['errMessage'] = $errMessage;
            }
            return $response->setContent(\Zend\Json\Json::encode($resArr));
        }
    }
    
    
    public function saveupdatesettingAction()
    {
        $response = $this->getResponse();           
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');  
        $userDetails = $userContainer->user_details;
        $post = $this->getRequest()->getPost()->toArray();      
        $applicationController = new IndexController();
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $responseCode = 0;
        $errMessage = "";
        $resArr = array();
        if ($post) {
            $dataArr = $post;
            $comment = $dataArr['comment'];
            unset($dataArr['comment']);
            $can_update = $dataArr['can_update'];
            if (!isset($userContainer->u_id)) {
                    $applicationController->saveToLogFile($dataArr['role_id'], $userDetails['email'], 'Access Setting-For update', "", "", $comment, 'Session timeout:Failure',  $trackerData['client_id']);
                return $this->redirect()->toRoute('home');
            } else {
                $accessArr = $this->getModelService()->getupdateaccesssetting($dataArr);
                $resArr = $this->getModelService()->saveupdatesetting($dataArr);
                $postDataArr=Array();
                foreach ($can_update as $key => $value) { 
                    $postDataArr[$key]['workflow_id']=$dataArr['workflow_id'][$key];
                    $postDataArr[$key]['can_update']=$value;
                    $postDataArr[$key]['role_id']=$dataArr['role_id'];                    
                    $postDataArr[$key]['form_id']=$dataArr['form_id'];
                }
                $applicationController->saveToLogFile(0, $userDetails['email'], 'Access Setting-For update', json_encode($accessArr), json_encode($postDataArr), $comment, 'Success',  $trackerData['client_id']);
                
            }
        } else {
            // if (!isset($userContainer->u_id)) {
            //     $applicationController->saveToLogFile(0, '', '', '', 'Access Setting-For update', '', $trackerId, $actionId, 0, IP, '', 'Session Timeout,Post Array is blank:Failure');
            //     return $this->redirect()->toRoute('home');
            // } else {                
            //     $applicationController->saveToLogFile(0, '', '', '', 'Access Setting-For update', $userDetails['u_name'], $trackerId, $actionId, 0, IP, '', 'Post Array is blank:Failure');
            // }
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
        $userDetails = $userContainer->user_details;
        $applicationController = new IndexController();
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $post = $this->getRequest()->getPost()->toArray();  
        $resArr = array();
        if ($post) {
            $dataArr = $post;
            $comment = $dataArr['comment'];
            unset($dataArr['comment']);
            $can_read = $dataArr['can_read'];
            if (!isset($userContainer->u_id)) {
                    $applicationController->saveToLogFile($dataArr['role_id'], $userDetails['email'], "Access Setting-For read", "", "", $comment, 'Session timeout:Failure',  $trackerData['client_id']);                   
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $accessArr = $this->getModelService()->getreadaccesssetting($dataArr);
                $postDataArr=Array();
                foreach ($can_read as $key => $value) { 
                    $postDataArr[$key]['workflow_id']=$dataArr['workflow_id'][$key];
                    $postDataArr[$key]['can_read']=$value;
                    $postDataArr[$key]['role_id']=$dataArr['role_id'];                    
                    $postDataArr[$key]['form_id']=$dataArr['form_id'];
                }
                $applicationController->saveToLogFile(0, $userDetails['email'], 'Access Setting-For read', json_encode($accessArr), json_encode($postDataArr), $comment, 'Success',  $trackerData['client_id']);
                $resArr = $this->getModelService()->savereadsetting($dataArr);
            }
        } else {
            if (!isset($userContainer->u_id)) {
                $applicationController->saveToLogFile(0, '', 'Access Setting-For read', '', '', '', 'Session Timeout,Post Array is blank:Failure', $trackerData['client_id']);
                return $this->redirect()->toRoute('home');
            } else {                
                $applicationController->saveToLogFile(0, $userDetails['email'], 'Access Setting-For read', '', '', '', 'Post Array is blank:Failure', $trackerData['client_id']);
            }
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        return $response->setContent(\Zend\Json\Json::encode($resArr));
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
            $formId = $this->params()->fromRoute('form_id', 0);
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
                    $result = $this->getModelService()->getformaccessdetail($trackerId);
                    $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id'=>$formId));
                    return new ViewModel(
                        array(
                        'trackerRsults' => $this->getModelService()->trackerRsults($trackerId),
                        'users' => $this->getModelService()->trackerUsers($trackerId),
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
        $userDetails = $userContainer->user_details;                        
        $applicationController = new IndexController();
        $trackerId = $this->params()->fromRoute('tracker_id', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $post = $this->getRequest()->getPost()->toArray();
        $resArr = array();
        if ($post) {
            $dataArr = $post;
            $comment = $dataArr['comment'];
            unset($dataArr['comment']);
            $can_read = $dataArr['can_read'];
            if (!isset($userContainer->u_id)) {
                foreach ($can_read as $key => $value) {
                    $applicationController->saveToLogFile(0, $userDetails['email'], 'Form Access Setting', "", "", $comment, 'Session timeout:Failure', $trackerData['client_id']);                    
                }
                return $this->redirect()->toRoute('home');
            } else {
                $userDetails = $userContainer->user_details;
                $accessArr = $this->getModelService()->getformaccesssetting($dataArr);
                $resArr = $this->getModelService()->saveformsetting($dataArr);
                $postDataArr=Array();
                foreach ($can_read as $key => $value) { 
                    $postDataArr[$key]['form_id']=$dataArr['form_id'][$key];
                    $postDataArr[$key]['role_id']=$dataArr['role_id'];                    
                    $postDataArr[$key]['can_read']=$value;
                }
                $applicationController->saveToLogFile(0, $userDetails['email'], 'Form Access Setting', json_encode($accessArr), json_encode($postDataArr), $comment, 'Success', $trackerData['client_id']);                
            }
        } else {
            if (!isset($userContainer->u_id)) {
                    $applicationController->saveToLogFile(0, '', 'Form Access Setting', '', '', '', 'Session Timeout,Post Array is blank:Failure', $trackerData['client_id']);                
                return $this->redirect()->toRoute('home');
            } else {
                   $applicationController->saveToLogFile(0, $userDetails['email'], 'Form Access Setting', '', '', '', 'Post Array is blank:Failure', $trackerData['client_id']);                
            }
            $responseCode = 0;
            $errMessage = "Access Denied";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        }
        return $response->setContent(\Zend\Json\Json::encode($resArr));
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
}
