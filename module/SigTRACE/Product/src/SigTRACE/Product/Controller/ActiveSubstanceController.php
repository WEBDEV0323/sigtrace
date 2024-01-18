<?php
namespace SigTRACE\Product\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Product\Form\ActiveSubstanceForm;
use Zend\Session\Container;
use Session\Container\SessionContainer;

class ActiveSubstanceController extends AbstractActionController
{
    protected $_activeSubstanceMapper;
    protected $_auditService;
    protected $_roleService;

    public function getRoleService()
    {
        if (!$this->_roleService) {
            $sm = $this->getServiceLocator();
            $this->_roleService = $sm->get('Common\Role\Model\Role');
        }
        return $this->_roleService;
    }    
    
    public function getAuditService()
    {
        if (!$this->_auditService) {
            $sm = $this->getServiceLocator();
            $this->_auditService = $sm->get('Audit\Service');
        }
        return $this->_auditService;
    }

    public function getActiveSubstanceService()
    {
        if (!$this->_activeSubstanceMapper) {
            $sm = $this->getServiceLocator();
            $this->_activeSubstanceMapper = $sm->get('Product\Model\ActiveSubstance');
        }
        return $this->_activeSubstanceMapper;
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

    public function activesubstanceManagementAction()
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
            $formId = (int) $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            if ($this->isAdmin($trackerId)) {
                $trackerResults = $this->getActiveSubstanceService()->trackerResults($trackerId);
                //$activeSubstancesData = $this->getActiveSubstanceService()->getAllActiveSubstances('as_archive=0');
                $activeSubstancesData = $this->getActiveSubstanceService()->getAllActiveSubstancesWithProducts();//echo "<pre>"; print_r($activeSubstancesData);die;
                $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
                return new ViewModel(
                    array(
                        'trackerResults' => $trackerResults,
                        'activeSubstances' => $activeSubstancesData,
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
            $formId = (int) $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            if ($this->isAdmin($trackerId)) {
                $trackerResults = $this->getActiveSubstanceService()->trackerResults($trackerId);
                $products = $this->getActiveSubstanceService()->getProductNames('*', 'tracker_id', $trackerId, 'as_id=0');
                $activeSubstancesData = $this->getActiveSubstanceService()->getAllActiveSubstances('as_archive=0');
                $activeSubstancesId = (int) $this->getEvent()->getRouteMatch()->getParam('activeSubstanceId', 0);
                $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
                return new ViewModel(
                    array(
                        'activeSubstancesId' => $activeSubstancesId,
                        'products' => $products,
                        'trackerResults' => $trackerResults,
                        'activeSubstances' => $activeSubstancesData,
                        'trackerId' => $trackerId
                    )
                );
            }
        }      
    }
    
    public function activeSubstanceCheckAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $actSubId = 0;
        $dataArr = $this->getRequest()->getPost()->toArray();
        $dataArr  = filter_var_array($dataArr, FILTER_SANITIZE_STRING);
        $trackerId = (int) $this->params()->fromRoute('trackerId', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        
        if (!isset($userContainer->u_id) && empty($dataArr)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {            
            $reason = isset($dataArr['reason'])?$dataArr['reason']:'';
            $result =array();
            $result['responseCode']=0;
            if (isset($dataArr['actSubName']) && preg_match("/^([a-zA-Z0-9._ ])*$/", $dataArr['actSubName'])) {
                $result = $this->getActiveSubstanceService()->addActiveSubstance($dataArr, $userContainer, $actSubId);
                $response->setContent(json_encode($result));
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_400);
            }
            
            if ($result['responseCode'] > 0) {
                $this->getAuditService()->saveToLog(intval($result['actionId']), $userContainer->email, 'Add Active Substance', '', "{'active_substance':'".$dataArr['actSubName']."','products':{".json_encode($dataArr['productIds'])."}}", $reason, 'Success', $trackerData['client_id']);
                $this->flashMessenger()->addMessage(array('success' => 'Active substance added successfully!'));
            }
            return $response;
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
            $activeSubstancesId = (int) $this->getEvent()->getRouteMatch()->getParam('activeSubstanceId', 0);
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int) $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            if ($this->isAdmin($trackerId)) {
                $trackerResults = $this->getActiveSubstanceService()->trackerResults($trackerId);
                $products = $this->getActiveSubstanceService()->getProductNames('*', 'tracker_id', $trackerId, 'as_id=0 OR as_id='.$activeSubstancesId);
                $selectedProducts = $this->getActiveSubstanceService()->getProductNames('*', 'as_id', $activeSubstancesId, 'as_id<>0');                
                $selectedActSubName = $this->getActiveSubstanceService()->getSelectedActiveSubstance($activeSubstancesId);
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
                return new ViewModel(
                    array(
                        'activeSubstancesId' => $activeSubstancesId,
                        'selectedActSubName' => $selectedActSubName,
                        'selectedProducts' => $selectedProducts,
                        'trackerResults' => $trackerResults,
                        'trackerId' => $trackerId,
                        'products' => $products
                    )
                );
            }
            
        }
    }
    
    public function saveEditActiveSubstanceAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $dataArr = $this->getRequest()->getPost()->toArray();
        $dataArr  = filter_var_array($dataArr, FILTER_SANITIZE_STRING);
        $trackerId = $this->params()->fromRoute('trackerId', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        $formId = (int) isset($dataArr['formId']) ? $dataArr['formId'] : 0;
        
        if (!isset($userContainer->u_id) && empty($dataArr)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {                    
            $configVal = $this->getActiveSubstanceService()->getQuantitativeSettingsConfigs($formId);//echo '<pre>';print_r($config);die;
            $activeSubstancesId = (int) isset($dataArr['activeSubstancesId'])?$dataArr['activeSubstancesId']:'';
            $prevActSubName = $this->getActiveSubstanceService()->getAllActiveSubstances('as_archive=0 AND as_id='.$activeSubstancesId);
            $previousActSubName = isset($prevActSubName['0']['as_name']) ? $prevActSubName['0']['as_name'] : '';
            $prevProducts = $this->getActiveSubstanceService()->getProductNames('product_id', 'as_id', $activeSubstancesId, 'as_id<>0');
            $previousProd = array();
            if (count($prevProducts) == 0) {
                $prevProducts = array();
            }
            if (!empty($prevProducts)) {
                foreach ($prevProducts as $key => $value) {
                    $previousProd[] = $value['product_id'];
                } 
            }
            $result = $this->getActiveSubstanceService()->saveActiveSubstance($dataArr, $userContainer, $activeSubstancesId, $previousActSubName, $configVal);
            
            if ($result['responseCode'] > 0) {
                $this->getAuditService()->saveToLog(intval($activeSubstancesId), $userContainer->email, 'Edit Active Substance', "{'active_substance':'".$previousActSubName."','products':{".json_encode($previousProd)."}}", "{'active_substance':'".$dataArr['actSubName']."','products':{".json_encode($dataArr['productIds'])."}}", $dataArr['reason'], 'Success', $trackerData['client_id']);
                $this->flashMessenger()->addMessage(array('success' => 'Active substance updated successfully!'));
            }
            $response->setContent(json_encode($result));
            return $response;
        }        
    }
    
    public function deleteActiveSubstanceAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $dataArr = $this->getRequest()->getPost()->toArray();
        $dataArr  = filter_var_array($dataArr, FILTER_SANITIZE_STRING);
        $trackerId = $this->params()->fromRoute('trackerId', 0);
        $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);
        
        if (!isset($userContainer->u_id) && empty($dataArr)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {            
            $trackerId = (int) isset($dataArr['tracker_id'])?$dataArr['tracker_id']:'';
            $activeSubstancesId = (int) isset($dataArr['substance_id'])?$dataArr['substance_id']:'';
            //$prevActSub = $this->getActiveSubstanceService()->getAllActiveSubstances('as_id='.$activeSubstancesId);
            $result = $this->getActiveSubstanceService()->deleteActiveSubstance($trackerId, $activeSubstancesId, $userContainer);
            if ($result['responseCode'] > 0) {
                $this->getAuditService()->saveToLog(intval($dataArr['substance_id']), $userContainer->email, 'Delete Active Substance', $dataArr['substance_name'], "", $dataArr['comment'], 'Success', $trackerData['client_id']);
                $this->flashMessenger()->addMessage(array('success' => 'Active substance deleted successfully!'));
                $response->setContent(\Zend\Json\Json::encode('deleted'));
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $response->setContent(\Zend\Json\Json::encode('error'));
            }
            //$response->setContent(json_encode($result));
            return $response;
        }
    }
    
}
