<?php

namespace Common\Tracker\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;
use Common\Tracker\Form\TrackerForm;

class TrackerController extends AbstractActionController
{
    protected $_appModelService;
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
            $this->_modelService = $sm->get('Tracker\Model');
        }
        return $this->_modelService;
    }
    public function getApplicationModelService()
    {
        if (!$this->_appModelService) {
            $sm = $this->getServiceLocator();
            $this->_appModelService = $sm->get('Application\Model');
        }
        return $this->_appModelService;
    }
    public function isHavingTrackerAccess($trackerId)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $isSuperAdmin = isset($userDetails['isSuperAdmin'])?$userDetails['isSuperAdmin']:0; 
        if ($isSuperAdmin == 1 || ($userContainer->trackerRoles != '' && in_array($trackerId, array_keys($userContainer->trackerRoles)))) {
            return true;
        }
        return false;
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
        // print_r ("tracker index part"); die;         

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
                array('dashboardResults' => $dashboardResults, 'dashboardUrl'=>$dashboardUrl)
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
            $trackersList = $this->getModelService()->getAllClientsWithTracker();
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
            //if ($this->isSuperAdmin()) {
                $form->setName('trackerForm');
                $form->get('c_hidden')->setAttribute('value', 0);
                $optionRecords = $this->getModelService()->getAllClients();
                $options = array();
            foreach ($optionRecords as $option) {
                $options[] = array('value' => $option['client_id'], 'label' => stripslashes($option['client_name']));
            }
                $form->get('c_select_client')->setAttribute('options', $options);
            //}
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
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $form = new TrackerForm();
            //if ($this->isSuperAdmin()) { 
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
            //}
            return new ViewModel(array('form' => $form,'trackerId' => $trackerId,'clientName' => $clientName,'clientId'  => $clientId));
        }  
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
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $form = new TrackerForm();
            if ($trackerId == 0) {
                return $this->redirect()->toRoute('tracker');
            } else {
                $this->layout()->setVariables(array('tracker_id' => $trackerId));
                $trackerInfo = $this->getModelService()->getTrackerInformation($trackerId);
                $clientInfo = $this->getModelService()->getClientInfo(isset($trackerInfo['client_id'])?$trackerInfo['client_id']:0);
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
            $clientId = isset($dataArr['clientId'])?(int)$dataArr['clientId']:0;
            $trackerName = isset($dataArr['trackerName'])?$dataArr['trackerName']:'';
            $trackerId = isset($dataArr['trackerId']) ? (int)$dataArr['trackerId'] : 0;
            $reason = isset($dataArr['reason'])?$dataArr['reason']:'';
            $trackerOldValues = $this->getModelService()->getTrackerInformation($trackerId);
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
                return $response; 
            } else {
                $match = "/^[a-zA-Z0-9 ]+$/";
                if (preg_match($match, $trackerName)) {
                    $userDetails = $userSession->user_details;
                    $results = $this->getModelService()->saveUpdateTracker($dataArr);
                    switch ($results['responseCode']) {
                    case 1:
                        if ($results['tracker_id'] > 0) {
                            $trackerOldValues = $this->getModelService()->getTrackerInformation($results['tracker_id']);
                            $this->getAuditService()->saveToLog($results['tracker_id'], $userDetails['email'], 'Add Tracker', '', "{'tracker name':'".$trackerName."', 'client_id':'".$clientId."'}", $reason, 'Success', $trackerOldValues['client_id']);
                        }
                        $this->flashMessenger()->addMessage(array('success' => 'Tracker created successfully!'));
                        break;
                    case 2:
                        if (!empty($trackerOldValues)) {
                            $this->getAuditService()->saveToLog($trackerId, $userDetails['email'], 'Edit Tracker', "{'tracker name':'".$trackerOldValues['name']."', 'client_id':'".$trackerOldValues['client_id']."'}", "'tracker name':'".$trackerName."', 'client_id':'".$clientId."'", $reason, 'Success', $trackerOldValues['client_id']);
                        }
                        $this->flashMessenger()->addMessage(array('success' => 'Tracker updated successfully!'));
                        break;
                    default:
                        break;
                    }
                } else {
                    $results['tracker_id'] = $trackerId;
                    $results['responseCode'] = 0;
                    $results['errMessage'] = "Invalid Tracker Name";  
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                }
                    $response->setContent(json_encode($results));
                    return $response;
                    //}
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
        $trackerId = isset($post['trackerId'])?(int)$post['trackerId']:0;
        $reason = isset($post['reason'])?$post['reason']:'';
        $resArr = array();
        if (!empty($post)) {
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $resArr['responseCode'] = 2;
                $resArr['errMessage'] = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                //if ($this->isSuperAdmin()) {
                $userDetails = $userSession->user_details;
                $trackerOldValues = $this->getModelService()->getTrackerInformation($trackerId);
                $results = $this->getModelService()->deleteTracker($trackerId);
                switch ($results['responseCode']) {
                case 1:
                    if (!empty($trackerOldValues)) {
                        $this->getAuditService()->saveToLog($trackerId, $userDetails['email'], 'Delete Tracker', "{'tracker name':'".$trackerOldValues['name']."', 'client_id':'".$trackerOldValues['client_id']."'}", "", $reason, 'Success', $trackerOldValues['client_id']);
                    }
                    $this->flashMessenger()->addMessage(array('success' => 'Tracker deleted successfully!')); 
                    $resArr['responseCode'] = 1;
                    $resArr['errMessage'] = "deleted";
                    break;
                default:
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    $resArr['responseCode'] = 0;
                    $resArr['errMessage'] = "error";
                    break;
                }
                //return $response;
                //}
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $response->setContent('Method Not Allowed');
            $resArr['responseCode'] = 0;
            $resArr['errMessage'] = "Method Not Allowed";
        }
        return $response->setContent(\Zend\Json\Json::encode($resArr));
    }
    
    public function settingsAction()
    {
        
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            if ($this->isAdmin($trackerId)) {
                $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
                $this->layout()->setVariables(array('tracker_id' => $trackerId, 'form_id' => $formId));
                return new ViewModel(
                    array(
                        'trackerResults' => $this->getModelService()->trackerResults($trackerId),
                        'action_id' => $formId,
                        'tracker_id' => $trackerId
                    )
                );
            }
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
            $roleNameType = $this->getModelService()->getUserType($groupId);
            
            $session->updateSession('user', array("user_details"=> array('group_id' =>$groupId, 'group_name'=>$groupName)));
            $userSession->offsetSet('roleName', $groupName);
            $userSession->offsetSet('roleNameType', $roleNameType);
            
            $session->updateSession('user', array("trackerRoles"=>array($trackerId =>array('sessionRoleId'=>$groupId, 'sessionRoleName'=>$groupName, 'sessionRoleType'=>$roleNameType))));
            $session->updateSession('tracker', array("tracker_user_groups"=>array($trackerId =>array('session_group_id'=>$groupId, 'session_group'=>$groupName))));
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_200);
            $this->getModelService()->updateUserSession($userSession->u_id, $trackerId, $groupId);
            $response->setContent('success');
            return $response;
        }
    }
}
