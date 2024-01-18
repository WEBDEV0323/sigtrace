<?php
namespace SigTRACE\SignalCalendar\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Session\Container;
use Session\Container\SessionContainer;


class SignalCalendarController extends AbstractActionController
{
    protected $_serviceMapper;
    protected $_auditMapper;
    protected $_dbMapper;
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
        if (!$this->_auditMapper) {
            $sm = $this->getServiceLocator();
            $this->_auditMapper = $sm->get('Common\Audit\Service');
        }
        return $this->_auditMapper;
    }
    public function getService()
    {
        if (!$this->_serviceMapper) {
            $sm = $this->getServiceLocator();
            $this->_serviceMapper = $sm->get('SignalCalendar\Model\SignalCalendar');
        }
        return $this->_serviceMapper;
    }
    public function getDbService()
    {
        if (!$this->_dbMapper) {
            $sm = $this->getServiceLocator();
            $this->_dbMapper = $sm->get('SignalCalendar\Model\Db');
        }
        return $this->_dbMapper;
    }
    public function indexAction()
    {
        set_time_limit(0);
        $view = new ViewModel();
        return $view; 
    }
    public function analysisAction()
    {
        set_time_limit(0);
        $view = new ViewModel();
        $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = (int) $this->getEvent()->getRouteMatch()->getParam('formId', 0);
        $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
        $productsList = $serious = $nonSerious = $fatal = $products = $field = array();
        if ($trackerId != 0 && $formId != 0) {
            $productsList = $this->getService()->getAllProducts($trackerId, $formId);
        }
        $view->setVariables(array('html'=>$productsList,'trackerId'=>$trackerId,'formId'=>$formId));
        return $view; 
    }
    public function viewAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");        
        
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {        
            $view = new ViewModel();
            //$filterArr = json_decode(base64_decode($this->params()->fromQuery('filter'))); 
            $filter = $this->params()->fromQuery('filter'); 
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $productId = (int)$this->getEvent()->getRouteMatch()->getParam('productId', 0);
            $dashboardId = (int)$this->getEvent()->getRouteMatch()->getParam('dashboardId', 0);
            $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
            $dashboardQuery = $this->getService()->getDashboardById($dashboardId);
            $filterFields = json_decode($dashboardQuery[0]['filters'] ??'');

            $filterCondition='';
            $decodedFilter=json_decode(base64_decode($filter));
           
            if (!empty($filter)) { 
                $filterCondition=$this->filterConf($decodedFilter);
            }
            $dateRange = trim((isset($filterCondition[1]))?$filterCondition[1]:'');
            //print_r($filterCondition);die;
            foreach ($filterFields as $key => $field) {
                if ($field->type == 'date') {
                    $filterFields[$key]->value = trim((isset($filterCondition[1]))?$filterCondition[1]:'');
                }
            }
            $list = array();
            $product = $this->getService()->getActiveIngradientNameById($productId);
            
            $productName = isset($product[0]) ? $product[0] : '';
            $view->setVariables(
                array(
                    'listOfView'=>$list,
                    'trackerId'=>$trackerId,
                    'formId'=>$formId,
                    'productId'=>$productId,
                    'product_name'=>$productName,
                    'queryFilter' =>$filter,
                    'dashboardId'=>$dashboardId,
                    'filterFields'=>$filterFields,
                    'dateRange' => $dateRange,
                )
            );
            return $view;
        }
    }
    public function updateSignalCalendarAction()
    {
        set_time_limit(0);
        $products = $this->getDbService()->fetch(
            array('t'=>'product'),
            array('tracker_id'=>109,'product_archive'=> 0),
            array('product_id','product_name'),
            array('product_name'=>'ASC')
        );
        foreach ($products as $product) {
            $this->getService()->updateSignalCalendarAnalysis(109, 199, $product['product_id']);
        }
        exit;
    }
    public function updateMedicalEvaluationAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;    
        
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {                        
            $response = $this->getResponse();
            $post = $this->getRequest()->getPost()->toArray();
            if (!empty($post)) {
                $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
                $trackerId = (int)isset($post['trackerId'])?$post['trackerId']:0;
                $formId = (int)isset($post['formId'])?$post['formId']:0;
                $productId = (int)isset($post['productId'])?$post['productId']:0;
                $ptId = (int)isset($post['ptId'])?$post['ptId']:0;
                $oldValue = (int)isset($post['oldValue'])?$post['oldValue']:0;
                $newValue = (int) isset($post['newValue'])?$post['newValue']:0;
                $reason = htmlentities($post['reason']);
                $responseCode = 1;
                $message = 'Medical value updated successfully.';
                $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);

                $result = $this->getService()->updateMedicalEvaluation($ptId, $newValue, $reason);
                $reason = date("Y-m-d H:i:s") . " : " . $reason;
                if ($result == 1) {
                    $this->getAuditService()->saveToLog($ptId, isset($userDetails['email']) ? $userDetails['email'] : '', "Medical Evaluation Edit Record", $oldValue, $newValue, $reason, "Success", $trackerData['client_id']);
                } else {
                    $responseCode = 0;
                    $message = 'Medical value record not found or updated already.';            
                }

                $returnData = array('responseCode' => $responseCode, 'message'=>$message, 'reason'=>$reason);        
                $response->setContent(\Zend\Json\Json::encode($returnData));

                return $response;    
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $response->setContent('Method Not Allowed');
                return $response;                
            }
        }
    }    
    public function getDataAction()
    {
        //print_r ("testtt"); die;
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");        
        $trackerContainer = $session->getSession('tracker');
        
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {                
            $response = $this->getResponse();
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
           // print_r ($trackerId); die;

            // $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            // $dashboardId = (int)$this->getEvent()->getRouteMatch()->getParam('dashboardId', 0);
            $res = $this->getService()->getQuantitativeAnalysis($trackerId);
            //print_r ($res); die;
            $response->setContent(\Zend\Json\Json::encode($allData));
            
            return $response;
        }
    }

    
    public function downloadEXCELAction()
    {
        set_time_limit(0);
        $request = $this->getRequest();
        $excel = new Container('quantitativeViewExcel');
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");        
        $userDetails = $userContainer->user_details; 
        $startDate = $excel['startDate'] = '';
        $endDate = $excel['endDate'] = '';
        $dateArray = $this->getQuarterDateRange();
        if ($request->isPost()) {
            $post = $this->getRequest()->getPost()->toArray();
            $trackerId = $excel['trackerId'] = (int)$post['trackerId'];
            $formId = $excel['formId'] = (int)$post['formId'];
            $productId = $excel['productId'] = (int)$post['productId'];    
            $dateRange = $post['dateRange'];
        } else {
            $trackerId = $excel['trackerId'];
            $formId = $excel['formId'];
            $productId = $excel['productId'];  
           
            $dateRange = $excel['dateRange'];
        }

        $filter = isset($post['filter'])?$post['filter']:'';
        $filterCondition=array();  
        if (!empty($filter)) {
            $filterCondition=$this->filterConf(json_decode(base64_decode($filter)));
        }
        $listFilter = json_decode(base64_decode($this->params()->fromQuery('listFilter'))); 
        $condition = base64_decode($this->params()->fromQuery('cond')); 
    
        $allFilter;
        if (!empty($filterCondition)) {
            $allFilter = $filterCondition[0];
        } else {
            $allFilter = $condition;
        }

        $configValue = $this->getService()->getQuantitativeSettingsConfigs($formId);
        $this->getService()->downloadExcel($productId, $trackerId, $formId, $configValue, $userDetails['u_name'], $dateRange, $allFilter, '');
        exit; 
    }
    public function downloadCSVAction()
    {
        set_time_limit(0);
        $request = $this->getRequest();
        $csv = new Container('quantitativeViewCSV');
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");        
        $userDetails = $userContainer->user_details; 
        $startDate = $csv['startDate'] = '';
        $endDate = $csv['endDate'] = '';
        $dateArray = $this->getQuarterDateRange();
        
        if ($request->isPost()) {
            $post = $this->getRequest()->getPost()->toArray();
            $trackerId = $csv['trackerId'] = (int)$post['trackerId'];
            $formId = $csv['formId'] = (int)$post['formId'];
            $productId = $csv['productId'] = (int)$post['productId']; 
            $dateRange = $post['dateRange'];   
                     
        } else {
            $trackerId = $csv['trackerId'];
            $formId = $csv['formId'];
            $productId = $csv['productId']; 
            $dateRange = $csv['dateRange']; 
                     
        }

        $filter = isset($post['filter'])?$post['filter']:'';
        $filterCondition=array();  
        if (!empty($filter)) {
            $filterCondition=$this->filterConf(json_decode(base64_decode($filter)));
        }
        $listFilter = json_decode(base64_decode($this->params()->fromQuery('listFilter'))); 
        $condition = base64_decode($this->params()->fromQuery('cond')); 
    
        $allFilter;
        if (!empty($filterCondition)) {
            $allFilter = $filterCondition[0];
        } else {
            $allFilter = $condition;
        }

        $configValue = $this->getService()->getQuantitativeSettingsConfigs($formId);
        $this->getService()->downloadCSV($productId, $trackerId, $formId, $configValue, $userDetails['u_name'], $dateRange, $allFilter, '');
        exit; 
    }
    public function downloadPDFAction()
    {
        set_time_limit(0);
        $request = $this->getRequest();
        $pdf = new Container('quantitativeViewPDF');
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");        
        $userDetails = $userContainer->user_details; 
        $startDate = $pdf['startDate'] = '';
        $endDate = $pdf['endDate'] = '';
        $dateArray = $this->getQuarterDateRange();
        
        if ($request->isPost()) {
            $post = $this->getRequest()->getPost()->toArray();
            $trackerId = $pdf['trackerId'] = (int)$post['trackerId'];
            $formId = $pdf['formId'] = (int)$post['formId'];
            $productId = $pdf['productId'] = (int)$post['productId'];  
            $dateRange = $post['dateRange'];    
                                
        } else {
            $trackerId = $pdf['trackerId'];
            $formId = $pdf['formId'];
            $productId = $pdf['productId']; 
            $dateRange = $pdf['dateRange'];                         
        }

        $filter = isset($post['filter'])?$post['filter']:'';
        $filterCondition=array();  
        if (!empty($filter)) {
            $filterCondition=$this->filterConf(json_decode(base64_decode($filter)));
        }
        $listFilter = json_decode(base64_decode($this->params()->fromQuery('listFilter'))); 
        $condition = base64_decode($this->params()->fromQuery('cond')); 
    
        $allFilter;
        if (!empty($filterCondition)) {
            $allFilter = $filterCondition[0];
        } else {
            $allFilter = $condition;
        }

        $configValue = $this->getService()->getQuantitativeSettingsConfigs($formId);
        $this->getService()->downloadPDF($productId, $trackerId, $formId, $configValue, $userDetails['u_name'], $dateRange, $allFilter, '');
        exit; 
    } 

    public function getQuarterDateRange()
    {
        $current_month = date('m');
        $current_year = date('Y');
        if ($current_month>=1 && $current_month<=3) {
            $start_date = strtotime('1-October-'.($current_year-1));  // timestamp or 1-October Last Year 12:00:00 AM
            $end_date = strtotime('1-January-'.$current_year);  // // timestamp or 1-January  12:00:00 AM means end of 31 December Last year
        } else if ($current_month>=4 && $current_month<=6) {
            $start_date = strtotime('1-January-'.$current_year);  // timestamp or 1-Januray 12:00:00 AM
            $end_date = strtotime('1-April-'.$current_year);  // timestamp or 1-April 12:00:00 AM means end of 31 March
        } else if ($current_month>=7 && $current_month<=9) {
            $start_date = strtotime('1-April-'.$current_year);  // timestamp or 1-April 12:00:00 AM
            $end_date = strtotime('1-July-'.$current_year);  // timestamp or 1-July 12:00:00 AM means end of 30 June
        } else if ($current_month>=10 && $current_month<=12) {
            $start_date = strtotime('1-July-'.$current_year);  // timestamp or 1-July 12:00:00 AM
            $end_date = strtotime('1-October-'.$current_year);  // timestamp or 1-October 12:00:00 AM means end of 30 September
        }
        return array('startDate' => $start_date,'endDate' => $end_date);
    }  

    public function updatePriorityAction()
    {
        set_time_limit(0);
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');        
        $userDetails = $userContainer->user_details;    
        
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {                        
            $response = $this->getResponse();
            $post = $this->getRequest()->getPost()->toArray();
            if (!empty($post)) {
                $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
                $trackerId = (int)isset($post['trackerId'])?$post['trackerId']:0;
                $formId = (int)isset($post['formId'])?$post['formId']:0;
                $productId = (int)isset($post['productId'])?$post['productId']:0;
                $ptId = (int)isset($post['ptId'])?$post['ptId']:0;
                $oldValue = (int)isset($post['oldValue'])?$post['oldValue']:0;
                $newValue = (int) isset($post['newValue'])?$post['newValue']:0;
                $reason = htmlentities($post['reason']);
                $responseCode = 1;
                $message = 'Priority updated successfully.';
                $trackerData = $this->getRoleService()->getTrackerDetails($trackerId);

                $result = $this->getService()->updatePriority($ptId, $newValue, $reason);
                $reason = date("Y-m-d H:i:s") . " : " . $reason;
                if ($result == 1) {
                    $this->getAuditService()->saveToLog($ptId, isset($userDetails['email']) ? $userDetails['email'] : '', "Edit Priority", $oldValue, $newValue, $reason, "Success", $trackerData['client_id']);
                } else {
                    $responseCode = 0;
                    $message = 'Priority record not found or updated already.';            
                }

                $returnData = array('responseCode' => $responseCode, 'message'=>$message, 'reason'=>$reason);        
                $response->setContent(\Zend\Json\Json::encode($returnData));

                return $response;    
            } else {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_405);
                $response->setContent('Method Not Allowed');
                return $response;                
            }
        }
    }   
    public function getDateInMysqlFormat($indate,$inFormat)
    { 
        switch ($inFormat) { 
        case 'YYYY-MM-DD': 
            try{ 
                $date = new \DateTime($indate);
                $req_date = $date->format('Y-m-d');
                return $req_date;
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
            break;
        case 'DD-MMM-YYYY':

            break;
        }
    } 
    
    public function filterConf($filterArr)
    {
        $condition =array();
        if (isset($filterArr)) {
            $selectCondition =  $dateCondition = "";
            foreach ($filterArr as $key => $field) { 
                list($type, $name) = explode(':', $field->name); 
                if ($type == 'date') { 
                    list($sDate, $eDate) = explode(' to ', $field->value);
                    
                    $dateRange = $field->value;
                    $sDateM = $this->getDateInMysqlFormat($sDate, 'YYYY-MM-DD');
                    $eDateM = $this->getDateInMysqlFormat($eDate, 'YYYY-MM-DD');
                    $dateCondition = $dateCondition." date(".$name.") BETWEEN '".$sDateM."' AND '".$eDateM."'";
                    //$dateConditionV = $name." between '".$sDate."' and '".$eDate."'";
                    $dateConditionV = $field->value;
                    
                } else if ($type == 'select') {
                    // if ($selectCondition == "") {
                    //     $selectCondition = $name." = '".$field->value."'";
                    // } else {
                    //     $selectCondition = $selectCondition." OR ".$name." = '".$field->value."'";
                    // }
                }
            }
            $selectCondition = " and (".$selectCondition.")";
            if ($selectCondition!=' and ()') {
                $condition[0] = $dateCondition." ".$selectCondition;
                $condition[1] = $dateConditionV." ".$selectCondition;
            } else {
                $condition[0]= $dateCondition;
                $condition[1]= $dateConditionV;
            }
        }
        return $condition;
    }
}
