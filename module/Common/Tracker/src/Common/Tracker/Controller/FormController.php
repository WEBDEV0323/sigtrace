<?php

namespace Common\Tracker\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;

class FormController extends AbstractActionController
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
            $this->_modelService = $sm->get('Form\Model');
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
    
    public function addNewFormAction()
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
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $trackerResults = $this->getModelService()->trackerResults($trackerId);
                return new ViewModel(
                    array(
                        'trackerResults' => $trackerResults,
                        'trackerId' => $trackerId
                    )
                );
            } else {
                $response->setContent('You do not have permission to access this tracker');
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                return $response;
            }  
        }
    }
    
    public function ajaxFormAddAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        $formId = $responseCode = 0; $errMessage = "";
        if (!empty($post)) {
            if (!isset($userSession->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $checkIfExist = $this->getModelService()->checkFormExist($trackerId, $post['form_name']);
                    if ($checkIfExist > 0) {
                        $errMessage = 'Form Name exists already.';
                    } else {
                        $reason = isset($post['reason'])?$post['reason']:'';
                        $formName = isset($post['form_name'])?$post['form_name']:'';
                        $match = "/^[a-zA-Z][a-zA-Z0-9 ]+$/";
                        if (preg_match($match, $formName)) {
                            $formName = htmlspecialchars($post['form_name'], ENT_QUOTES);
                            $resArr = $this->getModelService()->addNewForm($post);
                            $responseCode = $resArr['responseCode'];
                            $errMessage = $resArr['errMessage'];
                            $userDetails = $userSession->user_details;
                            $formId = $resArr['formId'];
                            if ($resArr['responseCode'] == 1) {
                                $trackerData = $this->getModelService()->getTrackerInformation($trackerId);
                                $this->flashMessenger()->addMessage(array('success' => $errMessage));
                                $this->getAuditService()->saveToLog($formId, $userDetails['email'], "add form", "", $formName, $reason, 'Success', $trackerData['client_id']);
                            }
                        } else {
                            $errMessage = "Invalid Form Name";     
                            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                        }
                         
                    }
                } else {
                    $errMessage = 'You do not have permission to access this tracker';
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                } 
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $response->setContent('Method Not Allowed');
        }
        $response->setContent(\Zend\Json\Json::encode(array('responseCode' => $responseCode, 'errMessage'=>$errMessage, 'formId'=>$formId)));
        return $response;       
    }
}

