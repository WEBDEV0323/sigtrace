<?php

namespace Common\Role\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Common\Role\Form\RoleForm;
use Session\Container\SessionContainer;

class RoleController extends AbstractActionController
{
    protected $_roleMapper;
    protected $_auditService;

    public function getRoleService()
    {
        if (!$this->_roleMapper) {
            $sm = $this->getServiceLocator();
            $this->_roleMapper = $sm->get('Role\Model');
        }
        return $this->_roleMapper;
    }
    public function getAuditService()
    {
        if (!$this->_auditService) {
            $sm = $this->getServiceLocator();
            $this->_auditService = $sm->get('Audit\Service');
        }
        return $this->_auditService;
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
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        $userDetails = $userContainer->user_details;
        $roleId = isset($userDetails['group_id'])?$userDetails['group_id']:0; 
        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        $sessionGroup = $trackerUserGroups[$trackerId]['session_group'];
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            return $this->redirect()->toRoute('tracker');
        } 
        return true;
    }
    public function roleManagementAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $trackerResults = $this->getRoleService()->trackerResults($trackerId);
                $roles = $this->getRoleService()->getRoleForTracker($trackerId);
                return new ViewModel(
                    array(
                        'trackerResults' => $trackerResults,
                        'roles' => $roles,
                        'trackerId' => $trackerId
                    )
                );
            }  
        }
    }

    /*
    * function to add role for tracker
    */

    public function addAction()
    {
        
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $roleId = $this->getEvent()->getRouteMatch()->getParam('id', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $form = new RoleForm();
                $form->setName('Groupform');
                $form->setAttribute('class', 'form-horizontal');
                $trackerDetails = $this->getRoleService()->trackerResults($trackerId);
                return array(
                    'form' => $form, 
                    'roleId' => $roleId, 
                    'trackerId' => $trackerId,
                    'trackerResults' => $trackerDetails
                );
            }
        }   
    }
    
    /*
    * function to update role for tracker
    */

    public function editAction()
    {
        
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $roleId = $this->getEvent()->getRouteMatch()->getParam('id', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $form = new RoleForm();
                $form->setName('Groupform');
                $form->setAttribute('class', 'form-horizontal');
                $resultset = $this->getRoleService()->getRole($roleId);
                if (isset($resultset['role_name']) && strtolower($resultset['role_name']) == 'administrator') {
                    return $this->redirect()->toRoute('tracker');
                }
                $form->get('c_name')->setAttribute('value', $resultset['role_name']);
                $form->get('c_hidden')->setAttribute('value', $roleId);
                $trackerDetails = $this->getRoleService()->trackerResults($trackerId);
                return array(
                    'form' => $form, 
                    'roleId' => $roleId, 
                    'trackerId' => $trackerId,
                    'trackerResults' => $trackerDetails
                );
            }
        } 
    }
    
    /*
    * function to view role for tracker
    */

    public function viewAction()
    { 
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $roleId = $this->getEvent()->getRouteMatch()->getParam('id', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $form = new RoleForm();
                $resultset = $this->getRoleService()->getRole($roleId);
                $trackerDetails = $this->getRoleService()->trackerResults($trackerId);
                return array(
                    'form' => $form,
                    'roleData' => $resultset, 
                    'roleId' => $roleId, 
                    'trackerId' => $trackerId,
                    'trackerResults' => $trackerDetails
                );
            }
        }       
    }
    
    /*
    * function to Add or update role in db
    */

    public function addUpdateAction()
    {       
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            if ($this->isHavingTrackerAccess($trackerId)) {
                $userDetails = $userContainer->user_details;
                $post = $this->getRequest()->getPost()->toArray();
                $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
                $match = "/^[a-zA-Z0-9 ]+$/";
                if (isset($post) && isset($post['c_name']) && isset($post['roleId']) && isset($post['trackerId'])) {
                    if (!is_numeric($post['roleId']) || !is_numeric($post['trackerId']) ) {
                        $resArr['responseCode'] = 0;
                        $resArr['errMessage'] = "Invalid Data";
                        return $response->setContent(\Zend\Json\Json::encode($resArr));
                    }
                    
                    if (!preg_match($match, $post['c_name'])) {
                        $resArr['responseCode'] = 0;
                        $resArr['errMessage'] = "Invalid Role Name";
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                        return $response->setContent(\Zend\Json\Json::encode($resArr));                        
                    }
                    $roleOldValues = $this->getRoleService()->getRole(intval($post['roleId']));
                    $results = $this->getRoleService()->saveRole($post);
                    $trackerData = $this->getRoleService()->getTrackerDetails($post['trackerId']);
                    switch ($results['responseCode']) {
                    case 1:
                        if (isset($trackerData['client_id'])) {
                            $this->getAuditService()->saveToLog($results['roleId'], $userDetails['email'], 'Add Role', '', "{'rolename':'".$post['c_name']."'}", $post['reason'], 'Success', $trackerData['client_id']);
                        }
                        $this->flashMessenger()->addMessage(array('success' => 'Role created successfully!'));
                        break;
                    case 2:
                        if (isset($trackerData['client_id']) && !empty($roleOldValues)) {
                            $this->getAuditService()->saveToLog($post['roleId'], $userDetails['email'], 'Edit Role', "{'rolename':'".$roleOldValues['role_name']."'}", "{'rolename':'".$post['c_name']."'}", $post['reason'], 'Success', $trackerData['client_id']);
                        }
                        $this->flashMessenger()->addMessage(array('success' => 'Role updated successfully!'));
                        break;
                    default:
                        break;
                    }
                    $response->setContent(json_encode($results));
                    return $response;   
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                    $response->setContent('Method Not Allowed');
                    return $response;
                }
                
            }
        }
    }
    
    /*
     * Function to delete group :to make group archive
     */

    public function deleteAction()
    {
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray();
        $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
        if (!empty($post)) {
            $comment = isset($post['comment'])?$post['comment']:'';
            $roleId = isset($post['role_id'])?(int) $post['role_id']:0;
            $trackerId = isset($post['tracker_id'])?(int) $post['tracker_id']:0;

            $session = new SessionContainer();
            $userContainer = $session->getSession('user');
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
                return $response;
            } else {
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $userDetails = $userContainer->user_details;
                    $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
                    $roleOldValues = $this->getRoleService()->getRole($roleId);
                    if (isset($roleOldValues['role_name']) && strtolower($roleOldValues['role_name']) == 'administrator') {
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                        return $response;
                    }
                    $results = $this->getRoleService()->deleteRole($roleId);
                    switch ($results['responseCode']) {
                    case 1:
                        if (!empty($roleOldValues)) {
                            $this->getAuditService()->saveToLog($roleId, isset($userDetails['email'])?$userDetails['email']:'', 'Delete Role', "{'rolename':'".$roleOldValues['role_name']."'}", '', $comment, 'Success', isset($trackerData['client_id'])?$trackerData['client_id']:0);
                        }
                        $this->flashMessenger()->addMessage(array('success' => 'Role deleted successfully!'));
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
}
