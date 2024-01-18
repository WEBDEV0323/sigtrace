<?php
namespace SigTRACE\Dashboard\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;

class IndexController extends AbstractActionController
{
    protected $_dashboardMapper;
    protected $_auditMapper;
    protected $_trackerMapper;
    protected $_dbMapper;
    public function getAuditService()
    {
        if (!$this->_auditMapper) {
            $sm = $this->getServiceLocator();
            $this->_auditMapper = $sm->get('Audit\Model\Audit');
        }
        return $this->_auditMapper;
    }
    public function getService()
    {
        if (!$this->_dashboardMapper) {
            $sm = $this->getServiceLocator();
            $this->_dashboardMapper = $sm->get('Dashboard\Model\Dashboard');
        }
        return $this->_dashboardMapper;
    }
    public function getTrackerService()
    {
        if (!$this->_trackerMapper) {
            $sm = $this->getServiceLocator();
            $this->_trackerMapper = $sm->get('Tracker\Model\TrackerModule');
        }
        return $this->_trackerMapper;
    }
    public function getDbService()
    {
        if (!$this->_dbMapper) {
            $sm = $this->getServiceLocator();
            $this->_dbMapper = $sm->get('Dashboard\Model\Db');
        }
        return $this->_dbMapper;
    }
    public function indexAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $config = $session->getSession("config");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $filterData = $this->params()->fromQuery('filter'); 
            
            $filterArr='';
            if ($filterData == null || base64_encode(base64_decode($filterData, true)) === $filterData) { 
                $filterArr = json_decode(base64_decode($filterData)); 
            } else { 
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_400);
                return $response;
            }

            $selectCondition = "";
            $dateCondition = "";
            if (isset($filterArr)) {
                foreach ($filterArr as $key => $field) { 
                    list($type, $name) = explode(':', $field->name); 
                    if ($type == 'date') { 
                        list($sDate, $eDate) = explode(' to ', $field->value);
                        $dateRange = $field->value;
                        $sDate = $this->getDateInMysqlFormat($sDate, 'YYYY-MM-DD');
                        $eDate = $this->getDateInMysqlFormat($eDate, 'YYYY-MM-DD');
                        $dateCondition = $dateCondition." date(".$name.") BETWEEN '".$sDate."' AND '".$eDate."'";
                        
                    } else if ($type == 'select') { 
                        $selectedValues[] = $field->value;
                        if ($selectCondition == "") {
                            $selectCondition = $name." = '".$field->value."'";
                        } else {
                            $selectCondition = $selectCondition." OR ".$name." = '".$field->value."'";
                        }
                    } else {
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_400);
                        return $response;
                    }
                }
                $selectCondition = " and (".$selectCondition.")";
                
            }
            $condition ='';
            if ($selectCondition!=' and ()') {
                $condition = $dateCondition." ".$selectCondition;
            } else {
                $condition= $dateCondition;
            }
            $quarterDateRange = $this->getQuarterDateRange();
            if ($dateRange =='') {
                $dateRange =" ".date('d-M-Y', $quarterDateRange['StartDate'])." to ".date('d-M-Y', $quarterDateRange['endDate'] -1);
            }
            
            $dateRangeSql ="BETWEEN '".date('Y-m-d', $quarterDateRange['StartDate'])."' AND '".date('Y-m-d', $quarterDateRange['endDate'] -1)."'";
            
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 109);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 199);
            $getformInfo=$this->getService()->getFormsInfo($formId);
            $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
            $userDetails = $userSession->user_details;
            $this->getService()->dashboardResults($userDetails['u_id'], $userDetails['group_id']); 
           
            $tabList = $this->getService()->getAllTabs($trackerId);
            print_r ($tabList); die;
            $dataArr = $allTabs = array();
            foreach ($tabList as $key => $tabs) {
                if ($tabs['within_dashId'] !=0) {
                    $allTabs[$tabs['within_dashId']]['tab'][] = $tabs;
                } else {
                    $allTabs[$tabs['dashboard_id']] = $tabs;
                }
            }
            $formId = $allTabs[1]['formId'];
            $tabId = $allTabs[1]['dashboard_id'];
            $tabName = $allTabs[1]['dashboard_name'];
            $dataArr['tabData'] = $allTabs[1];
            $allFilter = json_decode($allTabs[1]['filters'] ??'');
            if ($condition == ' ') {
                if (isset($allFilter)) {  
                    foreach ($allFilter as $filter) {
                        if ($filter->type == 'date') {
                            $dateRangeSql = " and date(".$filter->field.") ".$dateRangeSql;
                        }
                    }
                }
            } else {
                 $dateRangeSql = " and ".$condition;
            }  
             
            $activeSubstanceList = $this->getService()->getAllProductList($dataArr, $dateRangeSql);
            
            foreach ($activeSubstanceList as $key => $activeSubtance) {
                $activeSubstanceList[$key]['tabId'] = $tabId;
                $activeSubstanceList[$key]['tabName'] = $tabName;
            }
            
            $view = new ViewModel();
            return $view->setVariables(
                array(
                    'trackerId' => $trackerId, 
                    'formId' => $formId, 
                    'productsList' => $activeSubstanceList,
                    'dateRange' => $dateRange,
                    'tabList' =>  $allTabs,
                    'filters' => $allFilter,
                    'selectedValues' => json_encode($selectedValues),
                )
            );
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
    public function fatalCumulativeAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            set_time_limit(0);
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
            $list = $this->getService()->getFatalData($trackerId, $formId);
            $header = $this->getService()->getHeaderList($formId);
            $view = new ViewModel();
            return $view->setVariables(array('trackerId'=>$trackerId,'formId'=>$formId,'lists'=>$list,'headers'=>$header)); 
        }
    }
    public function getDashboardDataAction()
    {
        $response = $this->getResponse();
        $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        //$formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
        $dataArr = $this->getRequest()->getPost()->toArray();

        if (isset($dataArr['activeTab']) && (trim($dataArr['activeTab']) != '') && is_string($dataArr['activeTab']) ) {
            $activeTab = $dataArr['activeTab'] ?? ''; 
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_400);
            return $response;
        }

        if (isset($dataArr['tabId']) && (trim($dataArr['tabId']) != '') && is_numeric($dataArr['tabId']) ) {
            $tabId = $dataArr['tabId'] ?? '';
        } else {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_400);
            return $response;
        }
       
        $dashboardData = $this->getService()->getDashboardTabById($tabId);
        $formId=$dashboardData[0]['formId'];
        $dataArr['tabData'] = $dashboardData[0];
        $filter = json_decode($dashboardData[0]['filters']);
        $quarterDateRange = $this->getQuarterDateRange();
       
        $dateRangeSql ="BETWEEN '".date('Y-m-d', $quarterDateRange['StartDate'])."' AND '".date('Y-m-d', $quarterDateRange['endDate'] -1)."'";

        if ($dataArr['filter'] == 'all') {
            if (isset($filter)) {  
                foreach ($filter as $filterField) {
                    switch ($filterField->type) {
                    case 'date' :
                        $dateRangeSql = " and date(".$filterField->field.") ".$dateRangeSql;
                        break;
                    case 'select':
                        $options =$this->getService()->executeQuery($filterField->data);
                        break;
                    default :
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_400);
                        return $response;
                    break;
                    }
                }
            }
            $dateRange =date('d-M-Y', $quarterDateRange['StartDate'])." to ".date('d-M-Y', $quarterDateRange['endDate'] -1);
        } else {
            if (!empty($dataArr['filter'])) { 
                $selectCondition =  $dateCondition = "";
                foreach ($dataArr['filter'] as $key => $field) { 
                    if (is_array($field)) {
                        list($type, $name) = explode(':', $field['name']); 
                        if ($type == 'date') { 
                            list($sDate, $eDate) = explode(' to ', $field['value']);
                            $dateRange = $field['value'];
                            $sDate = $this->getDateInMysqlFormat($sDate, 'YYYY-MM-DD');
                            $eDate = $this->getDateInMysqlFormat($eDate, 'YYYY-MM-DD');
                            $dateCondition = $dateCondition." date(".$name.") BETWEEN '".$sDate."' AND '".$eDate."'";
                            
                        } else if ($type == 'select') {  
                            $selectedValues[] = $field['value'];
                            if ($selectCondition == "") { 
                                $selectCondition = $name." = '".$field['value']."'"; 
                            } else {
                                $selectCondition = $selectCondition." OR ".$name." = '".$field['value']."'";
                            }
                        } else {
                            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_400);
                            return $response;
                        }
                    } else { 
                        $options =$this->getService()->executeQuery($field);
                    }
                    
                }
                if ($selectCondition !='') {
                    $selectCondition = " and (".$selectCondition.")";
                }
                $dateRangeSql = " and ".$dateCondition.$selectCondition;
            }
        }
        $productsList = array();
        if (!empty($dataArr)) {
            $productsList['data'] = $this->getService()->getAllProductList($dataArr, $dateRangeSql);
        }
        if (!empty($productsList['data'])) {
            foreach ($productsList['data'] as $key => $product) {
                $productsList['data'][$key]['activeTab'] = $activeTab;
                $productsList['data'][$key]['tabId'] = $tabId;
                $productsList['data'][$key]['formId'] = $formId;
            }
        }
        $productsList['filter'] = $filter;
        $productsList['tabId'] = $tabId;
        $productsList['dateRange'] = $dateRange;  
        $productsList['options'] = $options;
        $productsList['activeTab'] = $activeTab;
        $productsList['selectedValues'] = $selectedValues;
        
        $response->setContent(\Zend\Json\Json::encode($productsList));
        return $response;
    }

    public function listAction()
    {  
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $trackerSession = $session->getSession("tracker");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 109);
            $userDetails = $userSession->user_details;
            $this->getService()->dashboardResults($userDetails['u_id'], $userDetails['group_id']); 
            
            $dashboardId = (int)$this->getEvent()->getRouteMatch()->getParam('dashboardId', 0);
            if ($dashboardId != 0) {
                $dashboardQuery = $this->getService()->getDashboardById($dashboardId); 
                $formId = $dashboardQuery[0]['formId'];
            } else {
                $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 199);
            }
            $filterFields = json_decode($dashboardQuery[0]['filters'] ??'');
           
            $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));

            $asId = (int)$this->getEvent()->getRouteMatch()->getParam('asId', 0);
            $filterArr = $this->params()->fromQuery('filter');
           
            $filterCondition='';
            $decodedFilter=json_decode(base64_decode($filterArr));
           
            if (!empty($filterArr)) {
                $filterCondition=$this->filterConf($decodedFilter);
            }
            
            foreach ($filterFields as $key => $field) {
                if ($field->type == 'date') {
                    $filterFields[$key]->value = $filterCondition[1];
                }
            }
            $condition = $this->params()->fromQuery('cond');
           
            $trackerRoles = ($userSession->trackerRoles != '' && !empty($userSession->trackerRoles))?$userSession->trackerRoles:array();
            $userRoleId = ((!empty($trackerRoles)) && isset($trackerRoles[$trackerId]['sessionRoleId']))?$trackerRoles[$trackerId]['sessionRoleId']: 0;
            $userRoleType = ((!empty($trackerRoles)) && isset($trackerRoles[$trackerId]['sessionRoleType']))?$trackerRoles[$trackerId]['sessionRoleType']:$userSession->offsetGet('roleNameType');
            $sessionRoleName = ((!empty($trackerRoles)) && isset($trackerRoles[$trackerId]['sessionRoleName']))?$trackerRoles[$trackerId]['sessionRoleName']:$userDetails['group_name'];            
            $bulkActions = $this->getService()->fetchBulkActions($formId, $userRoleId, $userRoleType);
            $canInsertInfo = $this->getService()->canInsert($formId, $userRoleId, $sessionRoleName);
            $canInsert = !empty($canInsertInfo) ? $canInsertInfo[0]['can_insert']:'No';            
            $recordName = !empty($canInsertInfo) ? $canInsertInfo[0]['record_name']:'Record';

            $listFilter = $this->params()->fromQuery('listfilter');
          
            return new ViewModel(
                array(
                    'formId' => $formId,
                    'trackerId' => $trackerId,
                    //'filter' => $filterArr,
                    'label' => $dashboardQuery[0]['label'].' Cases',
                    'dashboardId' => $dashboardId,
                    'asId' => $asId,
                    'bulkActions' => $bulkActions,
                    'canInsert' => $canInsert,
                    'recordName' => $recordName,
                    'afilter' => $filterArr,
                    'listFilter' =>$listFilter,
                    'filterFields'=>$filterFields,
                    'condition' => $condition,
                )
            );
           
        }
    }

    public function activesubstanceAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $config = $session->getSession("config");
        
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $filterData = $this->params()->fromQuery('filter'); 
            
            $filterArr='';
            if ($filterData == null || base64_encode(base64_decode($filterData, true)) === $filterData) { 
                $filterArr = json_decode(base64_decode($filterData)); 
            } else { 
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_400);
                return $response;
            }

            $selectCondition = "";
            $dateCondition = "";
            if (isset($filterArr)) {
                foreach ($filterArr as $key => $field) { 
                    list($type, $name) = explode(':', $field->name); 
                    if ($type == 'date') { 
                        list($sDate, $eDate) = explode(' to ', $field->value);
                        $dateRange = $field->value;
                        $sDate = $this->getDateInMysqlFormat($sDate, 'YYYY-MM-DD');
                        $eDate = $this->getDateInMysqlFormat($eDate, 'YYYY-MM-DD');
                        $dateCondition = $dateCondition." date(".$name.") BETWEEN '".$sDate."' AND '".$eDate."'";
                        
                    } else if ($type == 'select') { 
                        $selectedValues[] = $field->value;
                        if ($selectCondition == "") {
                            $selectCondition = $name." = '".$field->value."'";
                        } else {
                            $selectCondition = $selectCondition." OR ".$name." = '".$field->value."'";
                        }
                    } else {
                        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_400);
                        return $response;
                    }
                }
                $selectCondition = " and (".$selectCondition.")";
                
            }
            $condition ='';
            if ($selectCondition!=' and ()') {
                $condition = $dateCondition." ".$selectCondition;
            } else {
                $condition= $dateCondition;
            }
            $quarterDateRange = $this->getQuarterDateRange();
            if ($dateRange =='') {
                $dateRange =" ".date('d-M-Y', $quarterDateRange['StartDate'])." to ".date('d-M-Y', $quarterDateRange['endDate'] -1);
            }
            
            $dateRangeSql ="BETWEEN '".date('Y-m-d', $quarterDateRange['StartDate'])."' AND '".date('Y-m-d', $quarterDateRange['endDate'] -1)."'";
            
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $getformInfo=$this->getService()->getFormsInfo($formId);
            $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
            $userDetails = $userSession->user_details;
            $this->getService()->dashboardResults($userDetails['u_id'], $userDetails['group_id']); 
           
            $tabList = $this->getService()->getAllTabs($trackerId);
           
            $dataArr = $allTabs = array();
            foreach ($tabList as $key => $tabs) {
                if ($tabs['within_dashId'] !=0) {
                    $allTabs[$tabs['within_dashId']]['tab'][] = $tabs;
                } else {
                    $allTabs[$tabs['dashboard_id']] = $tabs;
                }
            }

            
            $formId = $allTabs[10]['formId'];
            $tabId = $allTabs[10]['dashboard_id'];
            $tabName = $allTabs[10]['dashboard_name'];
            $dataArr['tabData'] = $allTabs[10];
            $allFilter = json_decode($allTabs[10]['filters'] ??'');
            if ($condition == ' ') {
                if (isset($allFilter)) {  
                    foreach ($allFilter as $filter) {
                        if ($filter->type == 'date') {
                            $dateRangeSql = " and date(".$filter->field.") ".$dateRangeSql;
                        }
                    }
                }
            } else {
                 $dateRangeSql = " and ".$condition;
            }  
             
            $activeSubstanceList = $this->getService()->getAllProductList($dataArr, $dateRangeSql);
            
            foreach ($activeSubstanceList as $key => $activeSubtance) {
                $activeSubstanceList[$key]['tabId'] = $tabId;
                $activeSubstanceList[$key]['tabName'] = $tabName;
            }
            
            $view = new ViewModel();
            return $view->setVariables(
                array(
                    'trackerId' => $trackerId, 
                    'formId' => $formId, 
                    'productsList' => $activeSubstanceList,
                    'dateRange' => $dateRange,
                    'tabList' =>  $allTabs,
                    'filters' => $allFilter,
                    'selectedValues' => json_encode($selectedValues),
                )
            );
        }
    }

    public function sourceAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $config = $session->getSession("config");

        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $tabId = $this->params()->fromQuery('tabId'); 
            $asId = $this->params()->fromQuery('asId'); 
            $filterData = $this->params()->fromQuery('filter');

            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $getformInfo=$this->getService()->getFormsInfo($formId);
            $this->layout()->setVariables(array('tracker_id'=>$trackerId,'form_id'=>$formId));
            $userDetails = $userSession->user_details;
            $this->getService()->dashboardResults($userDetails['u_id'], $userDetails['group_id']); 

            $view = new ViewModel();
            return $view->setVariables(
                array(
                    'trackerId' => $trackerId, 
                    'formId' => $formId, 
                    'tabId' => $tabId,
                    'asId' => $asId,
                    'filter' =>  $filterData,
                )
            );            
        }
    }

    public function fetchAllDataAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $configSession = $session->getSession('config');
        
        $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
        $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
        $asId = (int)$this->getEvent()->getRouteMatch()->getParam('asId', 0);
        $dashboardId = (int)$this->getEvent()->getRouteMatch()->getParam('dashboardId', 0);

        $trackerRoles = ($userSession->trackerRoles != '' && !empty($userSession->trackerRoles))?$userSession->trackerRoles:array();
        $userRoleId = ((!empty($trackerRoles)) && isset($trackerRoles[$trackerId]['sessionRoleId']))?$trackerRoles[$trackerId]['sessionRoleId']: 0;
        $userRoleType = ((!empty($trackerRoles)) && isset($trackerRoles[$trackerId]['sessionRoleType']))?$trackerRoles[$trackerId]['sessionRoleType']:$userSession->offsetGet('roleNameType');
        $bulkActions = $this->getService()->fetchBulkActions($formId, $userRoleId, $userRoleType);
        $bActionStatus = isset($configSession->bulk_action)?strtolower($configSession->bulk_action):'off';         
        
        $filterArr = json_decode(base64_decode($this->params()->fromQuery('filter'))); 
        
        $filterCondition=array();  
        if (!empty($filterArr)) {
            $filterCondition=$this->filterConf($filterArr);
        }
      
        $listFilter = json_decode(base64_decode($this->params()->fromQuery('listFilter'))); 
        
        $condition = $this->params()->fromQuery('cond'); 
        
        if (base64_encode(base64_decode($condition, true)) === $condition) {  
            $allFilter;
            if (!empty($filterCondition)) { 
                $allFilter = $filterCondition[0];
            } else {  
                $allFilter = base64_decode($condition);
            }
           
            $allData = $this->getService()->fetchAllData($trackerId, $formId, $dashboardId, $asId, $allFilter);

        } else { 
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_400);
            return $response;
        }
        if (isset($allData['data'])) {
            foreach ($allData['data'] as $key => $value) {
                $allData['data'][$key]['action'] = $value['id'];
            }
            if (!empty($allData['labels'])) {
                $labels = array_map('trim', explode(",", $allData['labels']));
            }
            if (!empty($allData['names'])) {
                $names = array_map('trim', $allData['names']);
            }
          
            $columnDefs=array();
            if (!empty($labels)) {
                // print_r ($labels); die;
                $columnDefs[0]['headerName']="Action";
                $columnDefs[0]['field']="action";
                if (!empty($bulkActions) && $bActionStatus == 'on') {
                    $columnDefs[0]['headerCheckboxSelection'] = true;
                    $columnDefs[0]['headerCheckboxSelectionFilteredOnly'] = true;
                    $columnDefs[0]['checkboxSelection'] = true;
                }
                $columnDefs[0]['lockPosition']=true; 
                $columnDefs[0]['sortable']=true;
                $columnDefs[0]['suppressMenu']=true;
                $columnDefs[0]['filter']="false";
                //$columnDefs[0]['width']=210;
                //$columnDefs[0]['suppressSizeToFit']=true;
                $columnDefs[0]['cellRenderer'] = "myCellRenderer";
                $columnDefs[0]['suppressColumnVirtualisation'] = false;
                $columnDefs[1]['cellRenderer'] = "anchorTag";
               
                foreach ($labels as $key => $arrvalue) { 
                    $columnDefs[$key+1]['headerName'] = $arrvalue;
                    $columnDefs[$key+1]['headerTooltip'] = $arrvalue;
                    (!empty($names))?$columnDefs[$key+1]['field'] = $names[$key+1]:""; 
                    if ((count($listFilter) != 0) && ($names[$key+1] == $listFilter[0][0])) { 
                        $columnDefs[$key+1]['cellRenderer'] = "applyFilter";
                        $columnDefs[$key+1]['lockPosition'] = true;
                        $columnDefs[$key+1]['filterParams'] = json_encode($listFilter);
                    }
                }
            }
            $allData['labels'] = $columnDefs;
            unset($allData['names']);
            $response->setContent(\Zend\Json\Json::encode($allData)); 
            return $response;
        } else {
            $allData['data']=array();
            $allData['labels']=array();
            $response->setContent(\Zend\Json\Json::encode($allData)); 
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_200);
            return $response;
        }
       
    }

    public function getFilterOptionAction()
    {
        $response = $this->getResponse();
        $dataArr = $this->getRequest()->getPost()->toArray();
        $allOption = $this->getService()->executeQuery($dataArr['query']);
        $response->setContent(\Zend\Json\Json::encode($allOption)); 
        return $response;
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
            } elseif ($dateCondition !='' ) {
                $condition[0]= $dateCondition;
                $condition[1]= $dateConditionV;
            } 
        }
        return $condition;
    }


    public function getQuarterDateRange1()
    {
        $current_month = date('m');
        $current_year = date('Y');
        if ($current_month>=1 && $current_month<=3) {
            $start_date = strtotime('1-January-'.$current_year);  // timestamp or 1-Januray 12:00:00 AM
            $end_date = strtotime('1-April-'.$current_year);  // timestamp or 1-April 12:00:00 AM means end of 31 March
        } elseif ($current_month>=4 && $current_month<=6) {
            $start_date = strtotime('1-April-'.$current_year);  // timestamp or 1-April 12:00:00 AM
            $end_date = strtotime('1-July-'.$current_year);  // timestamp or 1-July 12:00:00 AM means end of 30 June
        } elseif ($current_month>=7 && $current_month<=9) {
            $start_date = strtotime('1-July-'.$current_year);  // timestamp or 1-July 12:00:00 AM
            $end_date = strtotime('1-October-'.$current_year);  // timestamp or 1-October 12:00:00 AM means end of 30 September
        } elseif ($current_month>=10 && $current_month<=12) {
            $start_date = strtotime('1-October-'.$current_year);  // timestamp or 1-October 12:00:00 AM
            $end_date = strtotime('1-January-'.($current_year+1));  // timestamp or 1-January Next year 12:00:00 AM means end of 31 December this year
        }
        return array('StartDate' => $start_date,'endDate' => $end_date);
    }

    public function getQuarterDateRange()
    {
        $start_date = strtotime('3 months ago');
        $start_quarter = ceil(date('m', $start_date) / 3);
        $start_month = ($start_quarter * 3) - 2; 
        $start_year = date('Y', $start_date); 
        $start_timestamp = mktime(0, 0, 0, $start_month, 1, $start_year);
        return array('StartDate' => $start_timestamp,'endDate' => strtotime("now"));
    }

    public function loadDashboardAction()
    {
        set_time_limit(0);
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $productsList = array();
        $resArray = '';
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            //echo $trackerId.', '.$formId; die;
            //$post = json_decode(file_get_contents('php://input'), true);
            $field = array(); 
            if ($trackerId != 0 && $formId != 0) {
                $field = $this->getDbService()->fetch(
                    array('s'=>'quantitative_setting'),
                    array('form_id'=>$formId),
                    array('preferred_term','soc_name','serious_ness')
                );
                
                if (!empty($field)) {
                    $preferredTerm = $field[0]['preferred_term'];
                    $socName = $field[0]['soc_name'];
                    $seriousNess = $field[0]['serious_ness'];
                } else {
                    $preferredTerm = $socName = $seriousNess = 0; 
                }
                $productsList = $this->getService()->getAllProducts($trackerId, $formId);
               
            }
            $dashboard = $this->getService()->getDashboard($trackerId, $formId);
            $dashboard = array();
            
            $resArray .= '
                <!--- Quantitative Analysis --->
                <div class="row">
                    <div class="col-md-12">
                        <p class="alert fade in" id="alert" style="display:none;"></p>
                        <div class="widget box">
                            <div class="widget-header">
                                <h4>
                                    <i class="lnr icon-reorder"></i>
                                    Quantitative Analysis
                                </h4>
                                <div class="toolbar no-padding">
                                    <div class="btn-group">
                                        <span class="btn btn-xs widget-collapse"><i class="lnr icon-chevron-down-circle"></i></span>
                                    </div>
                                </div> 
                            </div>
                            <div class="widget-content">
                                <div class="card-columns">';
            foreach ($productsList as $key => $value) { 
                $resArray .= '
                                        <div class="card btn-primary">
                                            <a href="javascript:void(0);" onclick="window.location.href = \'/quantitative/view/'.$trackerId.'/'.$formId.'/'.$value['product_id'].'\'">
                                                <div class="card-body">
                                                    <h6 class="text-white">'.$value['product_name'].'<span class="float-right">0</span></h6>
                                                </div>
                                            </a>
                                        </div>';
            }
                                    $resArray .= '
                                    <div style="clear:both;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--- Qualitative Analysis --->
                <div class="row">
                    <div class="col-md-12">
                        <div class="widget box">
                            <div class="widget-header">
                                <h4>
                                    <i class="lnr icon-reorder"></i>
                                    Qualitative Analysis
                                </h4>
                                <div class="toolbar no-padding">
                                    <div class="btn-group">
                                        <span class="btn btn-xs widget-collapse"><i class="lnr icon-chevron-down-circle"></i></span>
                                    </div>
                                </div> 
                            </div>
                            <div class="widget-content">
                                <div class="tabbable tabbable-custom">
                                    <ul class="nav nav-tabs">
                                        <li class="nav-item"><a data-toggle="tab" href="#tab_feed_a" class="nav-link active">Qualitative Analysis Form</a></li>
                                        <li class="nav-item"><a data-toggle="tab" href="#tab_feed_b" class="nav-link">Quality Check</a></li>
                                        <li class="nav-item"><a data-toggle="tab" href="#tab_feed_c" class="nav-link">DSL review of qualitative analysis form</a></li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane active" id="tab_feed_a">
                                            <div class="scroller" data-height="300px" data-always-visible="1" data-rail-visible="0">
                                                <div class="widget-content">
                                                    <div class="tabbable tabbable-custom">
                                                        <ul class="nav nav-tabs">
                                                            <li class="nav-item"><a data-toggle="tab" href="#tab_feed_1" class="nav-link active">Current Quarter</a></li>
                                                            <li class="nav-item"><a data-toggle="tab" href="#tab_feed_2" class="nav-link">All</a></li>
                                                        </ul>
                                                        <div class="tab-content">
                                                            <div class="tab-pane active" id="tab_feed_1">
                                                                <div class="scroller card-columns" data-height="300px" data-always-visible="1" data-rail-visible="0">';
            foreach ($dashboard[0] as $product) {
                if ($product['product_name'] != '') {
                    $resArray .= '
                                                                            <div class="card btn-primary">
                                                                                <a href="javascript:void(0);" onclick="window.location.href = \'/qualitative/workflow_current/'.$trackerId.'/'.$formId.'/'.$product['workflow_id'].'/'.$product['product_id'].'\'">
                                                                                    <div class="card-body">
                                                                                        <h6 class="text-white">'.$product['product_name'].'<span class="float-right">0</span></h6>
                                                                                    </div>
                                                                                </a>
                                                                            </div>';
                }
            } 
                                                                    $resArray .= '
                                                                </div> 
                                                            </div> 
                                                            <div class="tab-pane" id="tab_feed_2">
                                                                <div class="scroller card-columns" data-height="300px" data-always-visible="1" data-rail-visible="0">';
            foreach ($dashboard[1] as $product) {
                if ($product['product_name'] != '') {
                    $resArray .= '
                                                                            <div class="card btn-primary">
                                                                                <a href="javascript:void(0);" onclick="window.location.href = \'/qualitative/workflow_all/'.$trackerId.'/'.$formId.'/'.$product['workflow_id'].'/'.$product['product_id'].'\'">
                                                                                    <div class="card-body">
                                                                                        <h6 class="text-white">'.$product['product_name'].'<span class="float-right">0</span></h6>
                                                                                    </div>
                                                                                </a>
                                                                            </div>';
                }
            }
                                                                    $resArray .= '
                                                                </div> 
                                                            </div> 
                                                        </div> 
                                                    </div>
                                                </div>
                                            </div> 
                                        </div> 
                                        <div class="tab-pane" id="tab_feed_b">
                                            <div class="scroller" data-height="300px" data-always-visible="1" data-rail-visible="0">
                                                <div class="widget-content">
                                                    <div class="tabbable tabbable-custom">
                                                        <ul class="nav nav-tabs">
                                                            <li class="nav-item"><a data-toggle="tab" href="#tab_feed_3" class="nav-link active">Current Quarter</a></li>
                                                            <li class="nav-item"><a data-toggle="tab" href="#tab_feed_4" class="nav-link">All</a></li>
                                                        </ul>
                                                        <div class="tab-content">
                                                            <div class="tab-pane active" id="tab_feed_3">
                                                                <div class="scroller card-columns" data-height="300px" data-always-visible="1" data-rail-visible="0">';
            foreach ($dashboard[2] as $product) {
                if ($product['product_name'] != '') {
                    $resArray .= '
                                                                            <div class="card btn-primary">
                                                                                <a href="javascript:void(0);" onclick="window.location.href = \'/qualitative/workflow_current/'.$trackerId.'/'.$formId.'/'.$product['workflow_id'].'/'.$product['product_id'].'\'">
                                                                                    <div class="card-body">
                                                                                        <h6 class="text-white">'.$product['product_name'].'<span class="float-right">0</span></h6>
                                                                                    </div>
                                                                                </a>
                                                                            </div>';
                }
            } 
                                                                    $resArray .= '
                                                                </div> 
                                                            </div> 
                                                            <div class="tab-pane" id="tab_feed_4">
                                                                <div class="scroller card-columns" data-height="300px" data-always-visible="1" data-rail-visible="0">';
            foreach ($dashboard[3] as $product) {
                if ($product['product_name'] != '') {
                    $resArray .= '
                                                                            <div class="card btn-primary">
                                                                                <a href="javascript:void(0);" onclick="window.location.href = \'/qualitative/workflow_all/'.$trackerId.'/'.$formId.'/'.$product['workflow_id'].'/'.$product['product_id'].'\'">
                                                                                    <div class="card-body">
                                                                                        <h6 class="text-white">'.$product['product_name'].'<span class="float-right">0</span></h6>
                                                                                    </div>
                                                                                </a>
                                                                            </div>';
                }
            }
                                                                    $resArray .= '
                                                                </div> 
                                                            </div> 
                                                        </div> 
                                                    </div>
                                                </div>
                                            </div> 
                                        </div> 
                                        <div class="tab-pane" id="tab_feed_c">
                                            <div class="scroller" data-height="300px" data-always-visible="1" data-rail-visible="0">
                                                <div class="widget-content">
                                                    <div class="tabbable tabbable-custom">
                                                        <ul class="nav nav-tabs">
                                                            <li class="nav-item"><a data-toggle="tab" href="#tab_feed_5" class="nav-link active">Current Quarter</a></li>
                                                            <li class="nav-item"><a data-toggle="tab" href="#tab_feed_6" class="nav-link">All</a></li>
                                                        </ul>
                                                        <div class="tab-content">
                                                            <div class="tab-pane active" id="tab_feed_5">
                                                                <div class="scroller card-columns" data-height="300px" data-always-visible="1" data-rail-visible="0">';
                                                                    
            foreach ($dashboard[4] as $product) {
                if ($product['product_name'] != '') {
                    $resArray .= '
                                                                            <div class="card btn-primary">
                                                                                <a href="javascript:void(0);" onclick="window.location.href = \'/qualitative/workflow_current/'.$trackerId.'/'.$formId.'/'.$product['workflow_id'].'/'.$product['product_id'].'\'">
                                                                                    <div class="card-body">
                                                                                        <h6 class="text-white">'.$product['product_name'].'<span class="float-right">0</span></h6>
                                                                                    </div>
                                                                                </a>
                                                                            </div>';
                }
            } 
                                                                    $resArray .= '
                                                                </div> 
                                                            </div> 
                                                            <div class="tab-pane" id="tab_feed_6">
                                                                <div class="scroller card-columns" data-height="300px" data-always-visible="1" data-rail-visible="0">';
                                                                    
            foreach ($dashboard[5] as $product) {
                if ($product['product_name'] != '') {
                    $resArray .= '
                                                                            <div class="card btn-primary">
                                                                                <a href="javascript:void(0);" onclick="window.location.href = \'/qualitative/workflow_all/'.$trackerId.'/'.$formId.'/'.$product['workflow_id'].'/'.$product['product_id'].'\'">
                                                                                    <div class="card-body">
                                                                                        <h6 class="text-white">'.$product['product_name'].'<span class="float-right">0</span></h6>
                                                                                    </div>
                                                                                </a>
                                                                            </div>';
                }
            } 
                                                                    $resArray .= '
                                                                </div> 
                                                            </div> 
                                                        </div> 
                                                    </div>
                                                </div>
                                            </div> 
                                        </div> 
                                    </div> 
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--- Qualitative Analysis Summery --->
                <div class="row">
                    <div class="col-md-12">
                        <div class="widget box">
                            <div class="widget-header"><h4><i class="lnr icon-reorder"></i>Signal Detection Summary</h4>
                                <div class="toolbar no-padding">
                                    <div class="btn-group">
                                        <span class="btn btn-xs widget-collapse"><i class="lnr icon-chevron-down-circle"></i></span>
                                    </div>
                                </div> 
                            </div>
                            <div class="widget-content">
                                <div class="tabbable tabbable-custom">
                                    <ul class="nav nav-tabs">
                                        <li class="nav-item"><a data-toggle="tab" href="#tab_feed_11" class="nav-link active">Current Quarter</a></li>
                                        <li class="nav-item"><a data-toggle="tab" href="#tab_feed_22" class="nav-link">All</a></li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane active" id="tab_feed_11">
                                            <div class="scroller card-columns" data-height="300px" data-always-visible="1" data-rail-visible="0">';
                                                
            foreach ($dashboard[6] as $product) {
                if ($product['product_name'] != '') {
                    $resArray .= '
                                                        <div class="card btn-primary">
                                                            <a href="javascript:void(0);" onclick="window.location.href = \'/validation/validate_view/'.$trackerId.'/'.$formId.'/'.$product['workflow_id'].'/'.base64_encode($product['product_name']).'/'.base64_encode('Current').'\'">
                                                                <div class="card-body">
                                                                    <h6 class="text-white">'.$product['product_name'].'<span class="float-right">0</span></h6>
                                                                </div>
                                                            </a>
                                                        </div>';
                }
            } 
                                                $resArray .= '
                                            </div> 
                                        </div> 
                                        <div class="tab-pane" id="tab_feed_22">
                                            <div class="scroller card-columns" data-height="300px" data-always-visible="1" data-rail-visible="0">';
                                               
            foreach ($dashboard[7] as $product) {
                if ($product['product_name'] != '') {
                    $resArray .= '
                                                        <div class="card btn-primary">
                                                            <a href="javascript:void(0);" onclick="window.location.href = \'/validation/validate_view/'.$trackerId.'/'.$formId.'/'.$product['workflow_id'].'/'.base64_encode($product['product_name']).'/'.base64_encode('All').'\'">
                                                                <div class="card-body">
                                                                    <h6 class="text-white">'.$product['product_name'].'<span class="float-right">0</span></h6>
                                                                </div>
                                                            </a>
                                                        </div>';
                }
            } 
                                                $resArray .= '
                                            </div> 
                                        </div> 
                                    </div> 
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--- Validation --->
                <div class="row">
                    <div class="col-md-12">
                        <div class="widget box">
                            <div class="widget-header"><h4><i class="lnr icon-reorder"></i>Validation</h4>
                                <div class="toolbar no-padding">
                                    <div class="btn-group">
                                        <span class="btn btn-xs widget-collapse"><i class="lnr icon-chevron-down-circle"></i></span>
                                    </div>
                                </div> 
                            </div>
                            <div class="widget-content">
                                <div class="tabbable tabbable-custom">
                                    <ul class="nav nav-tabs">
                                        <li class="nav-item"><a data-toggle="tab" href="#tab_feed_111" class="nav-link active">Current Quarter</a></li>
                                        <li class="nav-item"><a data-toggle="tab" href="#tab_feed_222" class="nav-link">All</a></li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane active" id="tab_feed_111">
                                            <div class="scroller card-columns" data-height="300px" data-always-visible="1" data-rail-visible="0">';
                                                
            foreach ($dashboard[8] as $product) {
                if ($product['product_name'] != '') {
                    $resArray .= '
                                                        <div class="card btn-primary">
                                                            <a href="javascript:void(0);" onclick="window.location.href = \'/validation/validate_view/'.$trackerId.'/'.$formId.'/'.$product['workflow_id'].'/'.base64_encode($product['product_name']).'/'.base64_encode('Current').'\'">
                                                                <div class="card-body">
                                                                    <h6 class="text-white">'.$product['product_name'].'<span class="float-right">0</span></h6>
                                                                </div>
                                                            </a>
                                                        </div>';
                }
            } 
                                                $resArray .= '
                                            </div> 
                                        </div> 
                                        <div class="tab-pane" id="tab_feed_222">
                                            <div class="scroller card-columns" data-height="300px" data-always-visible="1" data-rail-visible="0">';
                                                
            foreach ($dashboard[9] as $product) {
                if ($product['product_name'] != '') {
                    $resArray .= '
                                                        <div class="card btn-primary">
                                                            <a href="javascript:void(0);" onclick="window.location.href = \'/validation/validate_view/'.$trackerId.'/'.$formId.'/'.$product['workflow_id'].'/'.base64_encode($product['product_name']).'/'.base64_encode('All').'\'">
                                                                <div class="card-body">
                                                                    <h6 class="text-white">'.$product['product_name'].'<span class="float-right">0</span></h6>
                                                                </div>
                                                            </a>
                                                        </div>';
                }
            } 
                                                $resArray .= '
                                            </div> 
                                        </div> 
                                    </div> 
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--- Assessment --->
                <div class="row">
                    <div class="col-md-12">
                        <div class="widget box">
                            <div class="widget-header"><h4><i class="lnr icon-reorder"></i> Assessment </h4>
                                <div class="toolbar no-padding">
                                    <div class="btn-group">
                                        <span class="btn btn-xs widget-collapse"><i class="lnr icon-chevron-down-circle"></i></span>
                                    </div>
                                </div> 
                            </div>
                            <div class="widget-content">
                                <div class="tabbable tabbable-custom">
                                    <ul class="nav nav-tabs">
                                        <li class="nav-item"><a data-toggle="tab" href="#tab_feed_33" class="nav-link active">Current Quarter</a></li>
                                        <li class="nav-item"><a data-toggle="tab" href="#tab_feed_44" class="nav-link">All</a></li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane active" id="tab_feed_33">
                                            <div class="scroller card-columns" data-height="300px" data-always-visible="1" data-rail-visible="0">';
                                                
            foreach ($dashboard[10] as $product) {
                if ($product['product_name'] != '') {
                    $resArray .= '
                                                        <div class="card btn-primary">
                                                            <a href="javascript:void(0);" onclick="window.location.href = \'/validation/validate_view/'.$trackerId.'/'.$formId.'/'.$product['workflow_id'].'/'.base64_encode($product['product_name']).'/'.base64_encode('Current').'\'">
                                                                <div class="card-body">
                                                                    <h6 class="text-white">'.$product['product_name'].'<span class="float-right">0</span></h6>
                                                                </div>
                                                            </a>
                                                        </div>';
                }
            } 
                                                $resArray .= '
                                            </div> 
                                        </div> 
                                        <div class="tab-pane" id="tab_feed_44">
                                            <div class="scroller card-columns" data-height="300px" data-always-visible="1" data-rail-visible="0">';
                                                
            foreach ($dashboard[11] as $product) {
                if ($product['product_name'] != '') {
                    $resArray .= '
                                                        <div class="card btn-primary">
                                                            <a href="javascript:void(0);" onclick="window.location.href = \'/validation/validate_view/'.$trackerId.'/'.$formId.'/'.$product['workflow_id'].'/'.base64_encode($product['product_name']).'/'.base64_encode('All').'\'">
                                                                <div class="card-body">
                                                                    <h6 class="text-white">'.$product['product_name'].'<span class="float-right">0</span></h6>
                                                                </div>
                                                            </a>
                                                        </div>';
                }
            } 
                                                $resArray .= '
                                            </div> 
                                        </div> 
                                    </div> 
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';
            
        }
        $response->setContent($resArray); 
        return $response;
    }

    
}
