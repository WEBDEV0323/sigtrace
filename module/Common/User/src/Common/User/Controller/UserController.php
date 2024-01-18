<?php

namespace Common\User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Common\User\Form\UserForm;
use Session\Container\SessionContainer;

class UserController extends AbstractActionController
{
    protected $_userMapper;
    protected $_auditService;
    
    public function getAuditService()
    {
        if (!$this->_auditService) {
            $sm = $this->getServiceLocator();
            $this->_auditService = $sm->get('Audit\Service');
        }
        return $this->_auditService;
    }

    public function getUserService()
    {
        if (!$this->_userMapper) {
            $sm = $this->getServiceLocator();
            $this->_userMapper = $sm->get('User\Model\UserModel');
        }
        return $this->_userMapper;
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
    
    /*
     * function to show all listing of one particular tracker
     */

    public function userManagementAction()
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
                $trackerResults = $this->getUserService()->trackerResults($trackerId);
                $usersData = $this->getUserService()->getAllUser($trackerId);
                return new ViewModel(
                    array(
                        'trackerResults' => $trackerResults,
                        'user' => $usersData,
                        'trackerId' => $trackerId
                    )
                );
            }
        }
    }
    
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
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $form = new UserForm();
                $form->setName('Userform');
                $resultset = array();
                $form->setAttribute('class', 'form-horizontal');
                $userId = (int) $this->getEvent()->getRouteMatch()->getParam('id', 0); 
                $form->get('t_hidden')->setAttribute('value', $trackerId);
                if ($userId > 0) {
                    $resultset = $this->getUserService()->getUserInfo($userId);
                    $form->get('c_status')->setAttribute('value', $resultset[0]['status']);
                    $form->get('c_archive')->setAttribute('value', $resultset[0]['user_archived']);
                    $form->get('c_hidden')->setAttribute('value', $userId);
                }

                $trackerResults = $this->getUserService()->trackerResults($trackerId);
                $userRolesData = $this->getUserService()->getUserRoles($trackerId);

                return new ViewModel(
                    array(
                        'trackerResults' => $trackerResults,
                        'groups' => $userRolesData,
                        'result' => $resultset,
                        'userId' => $userId,
                        'trackerId' => $trackerId,
                        'form' => $form
                    )
                );
            }
        }
    }
    
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
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $form = new UserForm();
                $form->setName('Userform');
                $resultset = array();
                $form->setAttribute('class', 'form-horizontal');
                $userId = (int) $this->getEvent()->getRouteMatch()->getParam('id', 0);

                $form->get('t_hidden')->setAttribute('value', $trackerId);
                if ($userId > 0) {
                    $resultset = $this->getUserService()->getUserInfo($userId);
                    $form->get('c_status')->setAttribute('value', $resultset[0]['status']);
                    $form->get('c_archive')->setAttribute('value', $resultset[0]['user_archived']);
                    $form->get('c_hidden')->setAttribute('value', $userId);
                }

                $trackerResults = $this->getUserService()->trackerResults($trackerId);
                $userRolesData = $this->getUserService()->getUserRoles($trackerId);
                return new ViewModel(
                    array(
                        'trackerResults' => $trackerResults,
                        'groups' => $userRolesData,
                        'result' => $resultset,
                        'userId' => $userId,
                        'trackerId' => $trackerId,
                        'form' => $form
                    )
                );
            }
        }
    }
    
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
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            $userId = (int) $this->getEvent()->getRouteMatch()->getParam('id', 0);
            if ($this->isHavingTrackerAccess($trackerId)) {
                $form = new UserForm();
                $form->setName('Userform');
                $resultset = array();
                $form->setAttribute('class', 'form-horizontal');
                $form->get('t_hidden')->setAttribute('value', $trackerId);
                if ($userId > 0) {
                    $resultset = $this->getUserService()->getUserInfo($userId);
                    $form->get('c_status')->setAttribute('value', $resultset[0]['status']);
                    $form->get('c_archive')->setAttribute('value', $resultset[0]['user_archived']);
                    $form->get('c_hidden')->setAttribute('value', $userId);
                }

                $trackerResults = $this->getUserService()->trackerResults($trackerId);
                $userRolesData = $this->getUserService()->getUserRoles($trackerId);
                return new ViewModel(
                    array(
                       'trackerResults' => $trackerResults,
                       'groups' => $userRolesData,
                       'result' => $resultset,
                       'userId' => $userId,
                       'trackerId' => $trackerId,
                       'form' => $form
                    )
                );
            }   
        }
    }
    
    public function userCheckAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $dataArr = $this->getRequest()->getPost()->toArray();
        if (!empty($dataArr)) {
            if (!is_numeric($dataArr['user_id']) || !is_numeric($dataArr['t_hidden']) ) {
                $resArr['responseCode'] = 0;
                $resArr['errMessage'] = "Invalid Data";
                return $response->setContent(\Zend\Json\Json::encode($resArr));
            }

            if (isset($dataArr['email']) && !preg_match('/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/', $dataArr['email'])) {
                $resArr['responseCode'] = 0;
                $resArr['errMessage'] = "Invalid Email";
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                return $response->setContent(\Zend\Json\Json::encode($resArr));                        
            }

            if (!preg_match('/^[a-zA-Z0-9\.]+$/', $dataArr['u_name'])) {
                $resArr['responseCode'] = 0;
                $resArr['errMessage'] = "Invalid User Name";
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                return $response->setContent(\Zend\Json\Json::encode($resArr));                        
            }
            $dataArr  = filter_var_array($dataArr, FILTER_SANITIZE_STRING);
            $userId = isset($dataArr['user_id'])?$dataArr['user_id']:0;
            $email = (isset($dataArr['email']) && preg_match('/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+$/', $dataArr['email']))?$dataArr['email']:'';
            $userName = (isset($dataArr['u_name']) && preg_match('/^[a-zA-Z0-9\.]+$/', $dataArr['u_name']))?$dataArr['u_name']:'';
            $groupName = (isset($dataArr['role_id']) && is_array($dataArr['role_id']))?implode(',', $dataArr['role_id']):'';
            $reason = isset($dataArr['reason'])?$dataArr['reason']:'';
            $trackerId = isset($dataArr['t_hidden'])?$dataArr['t_hidden']:0;
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
                return $response;
            } else {
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $userOldValues = $this->getUserService()->getUserInfoForTracker($userId, $trackerId);
                    $userDetails = $userContainer->user_details;
                    $trackerData = $this->getUserService()->getTrackerDetails($trackerId);
                    $results = $this->getUserService()->userAdd($dataArr);
                    switch ($results['responseCode']) {
                    case 1:
                        if (isset($trackerData['client_id']) && !empty($userOldValues)) {
                            $this->getAuditService()->saveToLog(intval($results['user_id']), $userDetails['email'], 'Add User', '', "{'username':'".$userName."', 'roles':{".$groupName."}, 'email':'".$email."'}", $reason, 'Success', $trackerData['client_id']);
                        }
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_200);
                        $this->flashMessenger()->addMessage(array('success' => 'User created successfully!'));
                        break;
                    case 2:
                        if (isset($trackerData['client_id']) && !empty($userOldValues)) {    
                            $this->getAuditService()->saveToLog(intval($results['user_id']), $userDetails['email'], 'Edit User', "{'username':'".$userOldValues['u_name']."', 'roles':{".$userOldValues['group_name']."}, , 'email':'".$userOldValues['email']."'}", "{'username':'".$userName."', 'roles':{".$groupName."}, 'email':'".$email."'}", $reason, 'Success', $trackerData['client_id']);
                        }
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_200);
                        $this->flashMessenger()->addMessage(array('success' => 'User updated successfully!'));
                        break;
                    default:
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                        break;
                    }
                    $response->setContent(json_encode($results));
                    return $response;
                } else {
                        $resArr['responseCode'] = 0;
                        $resArr['errMessage'] = "Method Not Allowed";
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                        return $response->setContent(\Zend\Json\Json::encode($resArr));                        
                }
            }
        } else {
            $resArr['responseCode'] = 0;
            $resArr['errMessage'] = "Method Not Allowed";
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            return $response->setContent(\Zend\Json\Json::encode($resArr));                        
        }
        exit;
    }

    /*
     * Function to delete settings :to make settings archive
     */

    public function deleteuserAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        
        $post = $this->getRequest()->getPost()->toArray();
        $post  = filter_var_array($post, FILTER_SANITIZE_STRING); 
        $comment = isset($post['comment'])?$post['comment']:'';
        $userId = isset($post['user_id'])?(int) $post['user_id']:0;
        $trackerId = isset($post['tracker_id'])?(int) $post['tracker_id']:0;

        if (!empty($post)) {
            if (!is_numeric($post['user_id']) || !is_numeric($post['tracker_id']) ) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $response->setContent(\Zend\Json\Json::encode('error'));
                return $response;
            }
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
                return $response;
            } else {
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $userDetails = $userContainer->user_details;
                    $trackerData = $this->getUserService()->getTrackerDetails($trackerId);
                    $userOldValues = $this->getUserService()->getUserInfoForTracker($userId, $trackerId);
                    $results = $this->getUserService()->deleteUser($userId, $trackerId);

                    switch ($results['responseCode']) {
                    case 1:
                        if (!empty($userOldValues)) {
                            $this->getAuditService()->saveToLog(intval($userId), isset($userDetails['email'])?$userDetails['email']:'', 'Delete User', "{'username':'".$userOldValues['u_name']."', 'roles':{".$userOldValues['group_name']."},'email':'".$userOldValues['email']."'}", '', $comment, 'Success', isset($trackerData['client_id'])?$trackerData['client_id']:0);
                        }
                        $this->flashMessenger()->addMessage(array('success' => 'User deleted successfully!'));
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
