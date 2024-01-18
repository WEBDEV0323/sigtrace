<?php

namespace Common\Customer\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Common\Customer\Form\CustomerForm;
use Session\Container\SessionContainer;

class CustomerController extends AbstractActionController
{
    protected $_adminMapper;
    protected $_auditService;

    public function getModelService()
    {
        if (!$this->_adminMapper) {
            $sm = $this->getServiceLocator();
            $this->_adminMapper = $sm->get('Customer\Model\Customer');
        }
        return $this->_adminMapper;
    }
    
    public function getAuditService()
    {
        if (!$this->_auditService) {
            $sm = $this->getServiceLocator();
            $this->_auditService = $sm->get('Audit\Service');
        }
        return $this->_auditService;
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
        $userContainer = $session->getSession("user");
        $customers = array();
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else { //if ($this->isSuperAdmin()) {
            $customers = $this->getModelService()->getAllClients();
        }
        return new ViewModel(array('customers' => $customers));
    }
    public function addAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $form = new CustomerForm();
        $form->setName('clientform');
        $customerId = $this->getEvent()->getRouteMatch()->getParam('customer_id', 0);
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            //if ($this->isSuperAdmin()) {
                $countryName = $this->getModelService()->getCountryName();
                $options = array();
            foreach ($countryName as $option) {
                $options[] = array('value' => $option['option_id'], 'label' => $option['label']);
            }
                $form->get('c_country')->setAttribute('options', $options);
                $form->get('c_hidden')->setAttribute('value', $customerId);
            //}
        }
        return array('form' => $form, 'customerId' => $customerId);
    }
    public function editAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $form = new CustomerForm();
        $form->setName('clientform');
        $customerId = $this->getEvent()->getRouteMatch()->getParam('customer_id', 0);
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            //if ($this->isSuperAdmin()) {
                $countryName = $this->getModelService()->getCountryName();
                $options = array();
            foreach ($countryName as $option) {
                $options[] = array('value' => $option['option_id'], 'label' => $option['label']);
            }
                $form->get('c_country')->setAttribute('options', $options);
                $form->get('c_hidden')->setAttribute('value', $customerId);
                $customerData = $this->getModelService()->getCustomerInfo($customerId);
                $form->get('c_name')->setAttribute('value', stripslashes($customerData['client_name']));
                $form->get('c_email')->setAttribute('value', stripslashes($customerData['client_email']));
                $form->get('c_proj_manager_name')->setAttribute('value', stripslashes($customerData['project_manager_name']));
                $form->get('c_description')->setAttribute('value', stripslashes($customerData['description']));
                $form->get('c_country')->setAttribute('value', stripslashes($customerData['country']));
            //}
        }
        return array('form' => $form, 'customerId' => $customerId);
    }
    public function viewAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $form = new CustomerForm();
        $form->setName('clientform');
        $customerId = $this->getEvent()->getRouteMatch()->getParam('customer_id', 0);
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            //if ($this->isSuperAdmin()) {
                $form->get('c_hidden')->setAttribute('value', $customerId);
                $customerData = $this->getModelService()->getCustomerInfo($customerId);
            if (intval($customerData['country']) > 0) {
                $customerData['country'] = $this->getModelService()->getCountryNameById($customerData['country']);
            }
            //}
        }
        return array('form' => $form, 'customerId' => $customerId, 'customerData'=>$customerData);
    }
    public function deleteAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $post  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
        if (!empty($post)) {
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            } else {
                $customerId = $this->getEvent()->getRouteMatch()->getParam('customer_id', 0);
                $reason = $post['comment'];
                $userDetails = $userContainer->user_details;
                $resultset = $this->getModelService()->delete($customerId);
                if ($resultset['responseCode'] == 1) {
                    $customerData = $this->getModelService()->getCustomerInfo($customerId);
                    $this->getAuditService()->saveToLog($customerId, $userDetails['email'], 'Delete Customer', "{'Customer Name':'".$customerData['client_name']."', 'Customer Email address':'".$customerData['client_email']."', 'Project Manager Name':'".$customerData['project_manager_name']."', 'Description':'".$customerData['description']."', 'Country':'".$customerData['country']."'}", "", $reason, 'Success', $customerId);
                    $this->flashMessenger()->addMessage(array('success' => 'Customer deleted successfully!'));
                    $response->setContent('Deleted');
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_501);
                    $this->flashMessenger()->addMessage(array('error' => 'Customer not deleted due to an error'));
                    $response->setContent('Customer not deleted due to an error');
                }
            }
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
            $response->setContent('Method Not Allowed');
        }
        return $response;
    }
    public function saveCustomerAction()
    {
        $customerId = $this->getEvent()->getRouteMatch()->getParam('customer_id', 0);
        if ($this->getRequest()->isPost()) {
            $response = $this->getResponse();
            $session = new SessionContainer();
            $userContainer = $session->getSession("user");
            if (!isset($userContainer->u_id)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $responseCode = 0;
                $errMessage = 'Your session has been expired. Please <a href="/">login</a> again';
            } else {
                //if ($this->isSuperAdmin()) {
                    $results = array();
                    $post = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
                    $checkduplicate = $this->getModelService()->checkDuplicate($post, $customerId);
                    $customerOldData = $this->getModelService()->getCustomerInfo($customerId);
                    $responseCode = 0;
                    switch ($checkduplicate['checkduplicate']) {
                case 0:
                    $results = $this->getModelService()->saveCustomers($post, $customerId);
                    $userDetails = $userContainer->user_details;
                    $reason = isset($post['reason'])?$post['reason']:"";
                    switch ($results['responseCode']) {
                    case 1:
                        if ($results['customerId'] > 0) {
                            $this->getAuditService()->saveToLog($results['customerId'], $userDetails['email'], 'Add Customer', '', "{'Customer Name':'".$post['name']."', 'Customer Email address':'".$post['email']."', 'Project Manager Name':'".$post['pmName']."', 'Description':'".$post['description']."', 'Country':'".$post['country']."'}", $reason, 'Success', $customerId);
                        }
                        $this->flashMessenger()->addMessage(array('success' => $results['errMessage']));
                        break;
                    case 2:
                        if (!empty($customerOldData)) {
                            $this->getAuditService()->saveToLog($customerId, $userDetails['email'], 'Edit Customer', "{'Customer Name':'".$customerOldData['client_name']."', 'Customer Email address':'".$customerOldData['client_email']."', 'Project Manager Name':'".$customerOldData['project_manager_name']."', 'Description':'".$customerOldData['description']."', 'Country':'".$customerOldData['country']."'}", "{'Customer Name':'".$post['name']."', 'Customer Email address':'".$post['email']."', 'Project Manager Name':'".$post['pmName']."', 'Description':'".$post['description']."', 'Country':'".$post['country']."'}", $reason, 'Success', $customerId);
                        }
                        $this->flashMessenger()->addMessage(array('success' => $results['errMessage']));
                        break;
                    default:
                        break;
                    }
                    $responseCode = $results['responseCode'];
                    $errMessage = $results['errMessage'];
                    break;
                case 1:
                    $errMessage = 'Customer with the same email id already exists';
                    break;
                case 2:
                    $errMessage = 'Customer with the same name already exists';
                    break;
                case 3:
                    $errMessage = 'Customer with the same name and email id already exists';
                    break;
                default:
                    break;
                    }
                    //}
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
