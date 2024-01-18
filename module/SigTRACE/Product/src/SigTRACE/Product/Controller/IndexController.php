<?php
namespace SigTRACE\Product\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Session\Container\SessionContainer;

class IndexController extends AbstractActionController
{
    protected $_productMapper;
    protected $_logMapper;
    protected $_auditMapper;
    protected $_caseMapper;
    protected $_importMapper;
    protected $_activeSubstanceMapper;
    protected $_auditService;


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
    public function getProductService()
    {
        if (!$this->_productMapper) {
            $sm = $this->getServiceLocator();
            $this->_productMapper = $sm->get('Product\Model\Product');
        }
        return $this->_productMapper;
    }
    
    public function getLogService()
    {
        if (!$this->_logMapper) {
            $sm = $this->getServiceLocator();
            $this->_logMapper = $sm->get('Product\Model\Log');
        }
        return $this->_logMapper;
    }
    public function getImportModel()
    {
        if (!$this->_importMapper) {
            $sm = $this->getServiceLocator();
            $this->_importMapper = $sm->get('Settings\Model\Import');
        }
        return $this->_importMapper;
    }
    public function getCasedataModel()
    {
        if (!$this->_caseMapper) {
            $sm = $this->getServiceLocator();
            $this->_caseMapper = $sm->get('Casedata\Model\Casedata');
        }
        return $this->_caseMapper;
    }
    public function indexAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');        
        $response = $this->getResponse();
        $view = new ViewModel();
        $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
        $this->layout()->setVariables(array('tracker_id' => $trackerId));
        //$products = $this->getProductService()->getProductsList(array('tracker_id'=>$trackerId),array());
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $products = $this->getCasedataModel()->getProductsList($trackerId);            
            $prodActSubList = $this->getProductService()->getProductActiveSubstanceList();
            $trackerResults = $this->getActiveSubstanceService()->trackerResults($trackerId);
            $view->setVariables(array('products'=>$products,'trackerId' => $trackerId,'formId'=>$formId,'trackerResults'=>$trackerResults,'prodActSubList'=>$prodActSubList));
            $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
            return $view;
        }
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
    
    public function addAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
        $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $trackerResults = $this->getActiveSubstanceService()->trackerResults($trackerId);
            $formId = (int) $this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
            return new ViewModel(
                array(
                        'trackerResults' => $trackerResults,
                        'trackerId' => $trackerId,
                        'formId' => $formId
                    )
            );
        }
    }
    
    public function saveProductAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
        $resp = 0;        
        if (!isset($userContainer->u_id) && empty($post)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {            
            $productName = isset($post['productName'])?$post['productName']:'';
            $productName = ucwords(strtolower($productName));
            $productCode = isset($post['productCode'])?$post['productCode']:'';
            $trackerId = (int) isset($post['trackerId'])?$post['trackerId']:'';
            $result = $this->getProductService()->addProduct($trackerId, $productName, array('product_name'=>$productName, 'product_code'=>$productCode,'product_created_date'=>date('Y-m-d H:i:s'), 'product_status' => 1, 'product_archive'=>0, 'tracker_id'=>$trackerId, 'last_modified_by'=>$userContainer->email, 'last_modified_date_time'=>date('Y-m-d H:i:s')));
            if ($result['responseCode'] > 0) {
                $this->getAuditService()->saveToLog(intval($result['actionId']), $userContainer->email, 'Add Product', "", $productName, $post['reason'], 'Success', $result['clientName']);
                $this->flashMessenger()->addMessage(array('success' => 'Product added successfully!'));
            }
            $response->setContent(json_encode($result));
            return $response;
        }
    }


    public function editAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
        $resp = 0;
        $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            if ($this->isAdmin($trackerId)) {                
                $productId = (int) $this->getEvent()->getRouteMatch()->getParam('productId', 0);
                $formId = (int) $this->getEvent()->getRouteMatch()->getParam('formId', 0);
                $res = $this->getProductService()->getProductsList(array('product_id'=>$productId), array('product_name', 'product_code'));
                $trackerResults = $this->getActiveSubstanceService()->trackerResults($trackerId);
                $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
                return new ViewModel(
                    array(
                        'trackerResults' => $trackerResults,
                        'trackerId' => $trackerId,
                        'name' => $res['0']['product_name'],
                        'code' => $res['0']['product_code'],
                        'id' => $productId,
                        'formId' => $formId
                    )
                );
            }
        }
    }

    public function productCheckAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
        $resp = 0;
        $trackerId = (int) $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        if (!isset($userContainer->u_id) && empty($post)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            if (!empty($post)) {
                $resp = 0; $errMessage = "";
                $productName = isset($post['productName'])?$post['productName']:'';
                $productName = ucwords(strtolower($productName));
                $productId = (int) isset($post['productIds'])?$post['productIds']:'';
                $productCode = isset($post['productCode'])?$post['productCode']:'';
                $trackerId =(int) isset($post["trackerId"])?$post["trackerId"]:'';
                if ($productId > 0) { 
                    $where = array('product_id !='.$productId,'product_name'=>$productName,'tracker_id'=>$trackerId,'product_archive != 1');
                } else {
                    $where = array('product_name'=>$productName,'tracker_id'=>$trackerId);
                }
                $prodCondition = array('product_id'=>$productId); 
                $oldProductName = $this->getProductService()->getProductsList($prodCondition, array());
                $products = $this->getProductService()->getProductsList($where, array());
                if (empty($products)) {
                    if (count($oldProductName) > 0) {
                        $result = $this->getProductService()->updateProduct($trackerId, $oldProductName['0']['product_name'], array('product_id' => $productId), array('product_name' => $productName,'product_code'=>$productCode,'last_modified_by' => $userContainer->email,'last_modified_date_time' => date("Y-m-d H:i:s")), $oldProductName['0']['product_name']);                    
                        if ($result['responseCode'] > 0) {
                            $this->getAuditService()->saveToLog(intval($productId), $userContainer->email, 'Update product', "{'productName':'".$oldProductName['0']['product_name']."','productName':'".$oldProductName['0']['product_code']."'}", "{'productName':'".$productName."','productName':'".$productCode."'}", $post['reason'], 'Success', $result['clientName']);
                            $this->flashMessenger()->addMessage(array('success' => 'Product updated successfully!'));
                        }    
                        $result = array('responseCode' => 1, 'errMessage' => 'Product updated successfully!');
                    } else {
                        $result = array('responseCode' => 0, 'errMessage' => 'No productfound!');
                    }
                } else {
                    $responseCode = 0;
                    $errMessage = "Product already exists. Enter new product name";
                    $result = array('responseCode' => $responseCode, 'errMessage' => $errMessage);
                }                
            }
            $response->setContent(json_encode($result));
            return $response;
        }
    }
    public function deleteProductAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $post = array_merge_recursive($this->getRequest()->getPost()->toArray());
        $resp = 0;
        if (!isset($userContainer->u_id) && empty($post)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int) isset($post['trackerId'])?$post['trackerId']:'';
            $id = (int) isset($post['id'])?$post['id']:'';
            $prodData = $this->getProductService()->getProductsList(array('product_id'=>$id), array());
            $productName = isset($prodData['0']['product_name']) ? $prodData['0']['product_name'] : '';
            $result = $this->getProductService()->updateProduct($trackerId, array('product_id' => $id), array('product_id'=>$id), array('product_name'=>$productName,'product_archive' => 1,'product_archived_date'=>date('Y-m-d H:i:s'),'last_modified_by' => $userContainer->email,'last_modified_date_time' => date("Y-m-d H:i:s")));             
            if ($result['responseCode'] > 0) {
                $this->getAuditService()->saveToLog(intval($id), $userContainer->email, 'Delete product', $productName, "", $post['reason'], 'Success', $result['clientName']);
                $this->flashMessenger()->addMessage(array('success' => 'Product deleted successfully!'));
            }
            $response->setContent(json_encode($result));
            return $response;
        }               
    }
    public function historyAction()
    {
        $view = new ViewModel();
        $trackerId = $this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = $this->getEvent()->getRouteMatch()->getParam('formId', 0);
        $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
        $productId = $this->getEvent()->getRouteMatch()->getParam('productId', 0); 
        if ((int)$productId != 0) {
            $res = $this->getLogService()->fetch(
                array('log_type_id'=>$productId,'log_type'=>'product'),
                array()
            );
            $view->setVariables(array('id'=>$productId,'history'=>$res,'trackerId'=>$trackerId,'formId'=>$formId));
        }
        return $view;
    }
}
