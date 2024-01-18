<?php

namespace Common\Triggerform\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Common\Triggerform\Form\TriggerformForm;
use Session\Container\SessionContainer;

class TriggerformController extends AbstractActionController
{
    protected $_triggerformMapper;
    protected $_auditService;

    public function getTriggerService()
    {
        if (!$this->_triggerformMapper) {
            $sm = $this->getServiceLocator();
            $this->_triggerformMapper = $sm->get('Triggerform\Model');
        }
        return $this->_triggerformMapper;
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
    public function triggerFormAction()
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
                $trackerResults = $this->getTriggerService()->trackerResults($trackerId);
                $trigger = $this->getTriggerService()->getTriggerForTracker($trackerId);
                return new ViewModel(
                    array(
                        'trackerResults' => $trackerResults,
                        'trigger' => $trigger,
                        'trackerId' => $trackerId
                    )
                );
            }  
        }
    }

    /*
    * function to add trigger for tracker
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
            $triggerId = $this->getEvent()->getRouteMatch()->getParam('id', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $form = new TriggerformForm();
                $form->setName('Groupform');
                $form->setAttribute('class', 'form-horizontal');
                $trackerDetails = $this->getTriggerService()->trackerResults($trackerId);
                return array(
                    'form' => $form, 
                    'triggerId' => $triggerId, 
                    'trackerId' => $trackerId,
                    'trackerResults' => $trackerDetails
                );
            }
        }   
    }
    
    /*
    * function to update trigger for tracker
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
            $triggerId = $this->getEvent()->getRouteMatch()->getParam('id', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $form = new TriggerformForm();
                $form->setName('Groupform');
                $form->setAttribute('class', 'form-horizontal');
                $resultset = $this->getTriggerService()->getTrigger($triggerId);
                $form->get('c_name')->setAttribute('value', $resultset['trigger_name']);
                $form->get('trigger_when')->setAttribute('value', $resultset['trigger_when']);
                $form->get('trigger_then')->setAttribute('value', $resultset['trigger_then']);
                $form->get('source')->setAttribute('value', $resultset['source']);
                $form->get('destination')->setAttribute('value', $resultset['destination']);
                $form->get('when_conditions')->setAttribute('value', $resultset['when_conditions']);
                $form->get('fields_to_copy')->setAttribute('value', $resultset['fields_to_copy']);
                $form->get('c_hidden')->setAttribute('value', $triggerId);
                $trackerDetails = $this->getTriggerService()->trackerResults($trackerId);
                return array(
                    'form' => $form, 
                    'triggerData' => $resultset,
                    'triggerId' => $triggerId, 
                    'trackerId' => $trackerId,
                    'trackerResults' => $trackerDetails
                );
            }
        } 
    }
    
    /*
    * function to view trigger for tracker
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
            $triggerId = $this->getEvent()->getRouteMatch()->getParam('id', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            if ($this->isHavingTrackerAccess($trackerId)) {
                $form = new TriggerformForm();
                $resultset = $this->getTriggerService()->getTrigger($triggerId);
                $trackerDetails = $this->getTriggerService()->trackerResults($trackerId);
                return array(
                    'form' => $form,
                    'triggerData' => $resultset, 
                    'triggerId' => $triggerId, 
                    'trackerId' => $trackerId,
                    'trackerResults' => $trackerDetails
                );
            }
        }       
    }
    
    /*
    * function to Add or update trigger in db
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
                if (isset($post) && isset($post['c_name']) && isset($post['triggerId']) && isset($post['trackerId'])) {
                    if (!is_numeric($post['triggerId']) || !is_numeric($post['trackerId']) ) {
                        $resArr['responseCode'] = 0;
                        $resArr['errMessage'] = "Invalid Data";
                        return $response->setContent(\Zend\Json\Json::encode($resArr));
                    }
                    
                    if (!preg_match($match, $post['c_name'])) {
                        $resArr['responseCode'] = 0;
                        $resArr['errMessage'] = "Invalid Trigger Name";
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                        return $response->setContent(\Zend\Json\Json::encode($resArr));                        
                    }
                    $triggerOldValues = $this->getTriggerService()->getTrigger(intval($post['triggerId']));
                    $results = $this->getTriggerService()->saveTrigger($post);
                    $trackerData = $this->getTriggerService()->getTrackerDetails($post['trackerId']);
                    switch ($results['responseCode']) {
                    case 1:
                        if (isset($trackerData['client_id'])) {
                            $this->getAuditService()->saveToLog($results['triggerId'], $userDetails['email'], 'Add Triggerform', '', "{'trigger_name':'".$post['c_name']."','trigger_when':'".$post['trigger_when']."','trigger_then':'".$post['trigger_then']."','source':'".$post['source']."','destination':'".$post['destination']."','when_conditions':'".$post['when_conditions']."','fields_to_copy':'".$post['fields_to_copy']."'}", $post['reason'], 'Success', $trackerData['client_id']);
                        }
                        $this->flashMessenger()->addMessage(array('success' => 'Trigger Form created successfully!'));
                        break;
                    case 2:
                        if (isset($trackerData['client_id']) && !empty($triggerOldValues)) {
                            $this->getAuditService()->saveToLog($post['triggerId'], $userDetails['email'], 'Edit Triggerform', "{'trigger_name':'".$triggerOldValues['c_name']."','trigger_when':'".$triggerOldValues['trigger_when']."','trigger_then':'".$triggerOldValues['trigger_then']."','source':'".$triggerOldValues['source']."','destination':'".$triggerOldValues['destination']."','when_conditions':'".$triggerOldValues['when_conditions']."','fields_to_copy':'".$triggerOldValues['fields_to_copy']."'}", "{'trigger_name':'".$post['c_name']."','trigger_when':'".$post['trigger_when']."','trigger_then':'".$post['trigger_then']."','source':'".$post['source']."','destination':'".$post['destination']."','when_conditions':'".$post['when_conditions']."','fields_to_copy':'".$post['fields_to_copy']."'}", $post['reason'], 'Success', $trackerData['client_id']);
                        }
                        $this->flashMessenger()->addMessage(array('success' => 'Trigger Form updated successfully!'));
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
            $triggerId = isset($post['trigger_id'])?(int) $post['trigger_id']:0;
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
                    $trackerData = $this->getTriggerService()->getTrackerDetails($trackerId);
                    $triggerOldValues = $this->getTriggerService()->getTrigger($triggerId);

                    $results = $this->getTriggerService()->deleteTrigger($triggerId, $comment);
                    switch ($results['responseCode']) {
                    case 1:
                        if (!empty($triggerOldValues)) {
                            $this->getAuditService()->saveToLog($triggerId, isset($userDetails['email'])?$userDetails['email']:'', 'Delete Trigger', "{'trigger_name':'".$triggerOldValues['c_name']."','trigger_when':'".$triggerOldValues['trigger_when']."','trigger_then':'".$triggerOldValues['trigger_then']."','source':'".$triggerOldValues['source']."','destination':'".$triggerOldValues['destination']."','when_conditions':'".$triggerOldValues['when_conditions']."','fields_to_copy':'".$triggerOldValues['fields_to_copy']."'}", '', $comment, 'Success', isset($trackerData['client_id'])?$trackerData['client_id']:0);
                        }
                        $this->flashMessenger()->addMessage(array('success' => 'Trigger Form deleted successfully!'));
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

    public function getformfieldsAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $trackerContainer = $session->getSession('tracker');
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
            $trackerId=(int)$post['tracker_id'];
            $formId=(int)$post['form_id'];
            $roleId = $userDetails['group_id'];
            $trackerUserGroups = $trackerContainer->tracker_user_groups;            
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                return $this->redirect()->toRoute('tracker');
            }
            $this->layout()->setVariables(array('tracker_id' => $trackerId));
            $response = $this->getResponse();
            $notifyWhom = $this->getmailService()->getfieldname($formId);
            $dateFields = $this->getmailService()->getDateFields($formId);
            
            $resArr = array(); 
            $fieldsArray = $this->getmailService()->trackerCheckFieldsForFormula($trackerId, $formId);
            
            $resArr['fieldsArray']=$fieldsArray;
            $resArr['notifyWhom']=$notifyWhom;
            $resArr['dateFields']=$dateFields;            
            $response->setContent(\Zend\Json\Json::encode($resArr));
            return $response;
        }
    }
}
