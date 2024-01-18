<?php
                            
namespace Common\Report\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;

class ReportController extends AbstractActionController
{
    protected $_reportService;
    protected $_auditService;
    
    public function getReportService()
    {
        if (!$this->_reportService) {
            $sm = $this->getServiceLocator();
            $this->_reportService = $sm->get('Report\Service');
        }
        return $this->_reportService;
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
        $isSuperAdmin = isset($userDetails['isSuperAdmin']) ? $userDetails['isSuperAdmin'] : 0;
        if ($isSuperAdmin == 1 || ($userContainer->trackerRoles != '' && in_array($trackerId, array_keys($userContainer->trackerRoles)))) {
            return true;
        }
        return false;
    }
    
    public function isHavingReportAccess($formId, $reportId, $trackerId) 
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $userDetails = $userContainer->user_details;
        $isSuperAdmin = isset($userDetails['isSuperAdmin']) ? $userDetails['isSuperAdmin'] : 0;
        $trackerRoles = ($userContainer->trackerRoles != '' && !empty($userContainer->trackerRoles))?$userContainer->trackerRoles:array();
        $roleId = ((!empty($trackerRoles)) && isset($trackerRoles[$trackerId]['sessionRoleId']))?$trackerRoles[$trackerId]['sessionRoleId']: 0;
        $userRole = ((!empty($trackerRoles)) && isset($trackerRoles[$trackerId]['sessionRoleName']))?$trackerRoles[$trackerId]['sessionRoleName']: $userContainer->offsetGet('roleName');
        $aReportAccess = $this->getReportService()->getReportAccessSettings($formId, $reportId, $roleId);
        if ($isSuperAdmin == 1 || ($aReportAccess > 0 || strtolower($userRole) == 'administrator')) {
            return true;
        }
        return false; 
    }
    
    public function accessDenied()
    {
        $response = $this->getResponse();
        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
        $response->setContent('Access Denied!!');
        return $response;
    } 
    
    public function indexAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $userDetails = $userSession->user_details;
        $isSuperAdmin = isset($userDetails['isSuperAdmin']) ? $userDetails['isSuperAdmin'] : 0;
        $trackerContainer = $session->getSession('tracker'); 
        $baseEncode = '';
        $filterArr = $this->params()->fromQuery('filter');
        $filters = array();
        
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $reportId = (int)$this->getEvent()->getRouteMatch()->getParam('reportId', 0);
            $urlQuery = $this->params()->fromQuery('url'); 
          
            $trackerRoles = ($userSession->trackerRoles != '' && !empty($userSession->trackerRoles))?$userSession->trackerRoles:array();
            $roleId = ((!empty($trackerRoles)) && isset($trackerRoles[$trackerId]['sessionRoleId']))?$trackerRoles[$trackerId]['sessionRoleId']: 0;
            $userRole = ((!empty($trackerRoles)) && isset($trackerRoles[$trackerId]['sessionRoleName']))?$trackerRoles[$trackerId]['sessionRoleName']: $userSession->offsetGet('roleName');                   
            if ($this->isHavingTrackerAccess($trackerId)) {
                if ($this->isHavingReportAccess($formId, $reportId, $trackerId)) {
                    if ($isSuperAdmin == 1) {
                        $download = true;
                        $csv = true;
                        $xls = true;
                        $pdf = true; 
                        $canEdit = 'Yes';
                        $canView = 'Yes';                                                                        
                    } else {
                        $roleId = $userDetails['group_id'];
                        if (isset($trackerContainer->tracker_user_groups) && $role_name != 'SuperAdmin') {
                            $tracker_user_groups = $trackerContainer->tracker_user_groups;
                            $roleId = isset($tracker_user_groups[$trackerId]['session_group_id']) ? $tracker_user_groups[$trackerId]['session_group_id'] : 0;
                        }
                        $aReportSettings = $this->getReportService()->getReportSettingsConfigs($formId);                        
                        $aDownloadAccess = $this->getReportService()->getReportDownloadSettings($trackerId, $roleId, $aReportSettings);
                        $arrayAccess = array();
                        foreach ($aDownloadAccess as $aDownload) {
                            $arrayAccess[] = $aDownload['action_name'];
                        }
                        $download = (in_array('download', $arrayAccess)) ? true : false;
                        $csv = (in_array('csv', $arrayAccess)) ? true : false;
                        $xls = (in_array('xls', $arrayAccess)) ? true : false;
                        $pdf = (in_array('pdf', $arrayAccess)) ? true : false;  
                        
                        $aEditworkflowAccess = $this->getReportService()->getEditWorkflowAccessSettings($trackerId, $roleId, $aReportSettings);                        
                        if (count($aEditworkflowAccess) > 0) {
                            $canEdit = 'Yes';
                        } else {
                            $canEdit = 'No';
                        }
                        $aViewworkflowAccess = $this->getReportService()->getViewWorkflowAccessSettings($trackerId, $roleId, $aReportSettings);                        
                        if (count($aViewworkflowAccess) > 0) {
                            $canView = 'Yes';
                        } else {
                            $canView = 'No';
                        }                        
                    }
                    
                    $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id' => $formId,'reportDetails' => $reportDetails));
                    $post = $this->getRequest()->getPost()->toArray();
                    $cond = $headBreadcrumb = '';
                    if (isset($post['data']) || !empty($filterArr) ) {
                        if (!empty($filterArr)) {
                            $baseEncode = $filterArr;
                            $filters = json_decode(base64_decode($filterArr), true);
                        } else {
                            $baseEncode = base64_encode(json_encode($post['data']));
                            $filters = $post['data'];
                        }
                        foreach ($filters as $filter) {
                            switch ($filter['type']) {
                            case 'text':
                                if ($filter['value'] != '') {
                                    $cond .= " AND ".$filter['name']." LIKE '%".addslashes($filter['value'])."%'";
                                    $headBreadcrumb .= " AND ".$filter['label']." LIKE '".addslashes($filter['value'])."'";
                                }
                                break;
                            case 'date':
                                if (isset($filter['value']) && $filter['value']!= '') {
                                    $daterange = explode("to", $filter['value']);
                                    $filter['name'] = isset($filter['format'])?stripslashes($filter['format']):$filter['name'];
                                    $cond .= " AND ".$filter['name']." BETWEEN '".$this->dateConvertion(trim($daterange[0]))."' AND '".$this->dateConvertion(trim($daterange[1]))."'";
                                    $headBreadcrumb .= " AND ".$filter['label']." From ".$daterange[0]." To ".$daterange[1];
                                }
                                break;
                            case 'monthrange':
                                if (isset($filter['value']) && $filter['value']!= '') {
                                    $monthrange = explode("-", $filter['value']);
                                    $cond .= " AND DATE_FORMAT(".$filter['name'].", '%Y-%m') BETWEEN '".$this->dateConvertion(trim($monthrange[0]), 'monthrange')."' AND '".$this->dateConvertion(trim($monthrange[1]), 'monthrange')."'";
                                    $headBreadcrumb .= " AND ".$filter['label']." From ".trim($monthrange[0])." To ".trim($monthrange[1]);
                                }
                                break;
                            case 'select':
                                if (isset($filter['value']) && $filter['value'] != '') {
                                    $cond .= " AND (";
                                    $headBreadcrumb .= " AND ";
                                    $i = 0;
                                    foreach ($filter['value'] as $k=>$v) {
                                        if ($i == 0) {
                                            $cond .= $filter['name']." = '".addslashes($v)."'";
                                            $headBreadcrumb .= addslashes($v);
                                        } else {
                                            $cond .= " OR ".$filter['name']." = '".addslashes($v)."'";
                                            $headBreadcrumb .= ", ".addslashes($v);
                                        }
                                        $i++;
                                    }
                                    $cond .= ")";
                                }
                                break;
                            case 'checkbox':
                                if (isset($filter['value']) && $filter['value'] != '') {
                                    $cond .= " AND (";
                                    $headBreadcrumb .= " AND ";
                                    $i = 0;
                                    foreach ($filter['value'] as $k=>$v) {
                                        if ($i == 0) {
                                            $cond .= $filter['name']." LIKE '%".addslashes($v)."%'";
                                            $headBreadcrumb .= $filter['label']." LIKE '".addslashes($v)."'";
                                        } else {
                                            $cond .= " OR ".$filter['name']." LIKE '%".addslashes($v)."%'";
                                            $headBreadcrumb .= ", '".addslashes($v)."'";
                                        }
                                        $i++;
                                    }
                                    $cond .= ")";
                                }
                                break;
                            default:
                                break;
                            }
                        }
                    }
                    $reportDetails = $this->getReportService()->getReportDetails($formId, $reportId);
                    //echo"<pre>";print_r($reportDetails); die;
                    $this->layout()->setVariables(array('reportDetails' => $reportDetails));
                    return new ViewModel(
                        array(
                            'formId' => $formId,
                            'trackerId' => $trackerId,
                            'reportId'  =>$reportId,
                            'reportDetails' => $reportDetails,
                            'condition' => base64_encode($cond),
                            'headBreadcrumb'=>$headBreadcrumb,
                            'download' => $download,
                            'csv' => $csv,
                            'xls' => $xls,
                            'pdf' => $pdf,
                            'canEdit' => $canEdit,
                            'canRead' => $canView, 
                            'baseEncode' => $baseEncode,
                            'filteredData' => isset($post['data'])?$post['data']:array(),
                            'urlQuery' => $urlQuery
                        )
                    ); 
                } else { 
                    return $this->accessDenied(); 
                }
            } else {
                return $this->accessDenied(); 
            }
        }  
    }
    
    public function dateConvertion($origDate, $type='date')
    {
        switch ($type) {
        case 'date':
            $date = explode("-", $origDate);
            if (count($date) == 3) { 
                $newDate = date("Y-m-d", strtotime($origDate));
                return $newDate;
            }
            break;
        case 'monthrange':
            $date = explode(" ", $origDate);
            if (count($date) == 2) { 
                $newDate = date("Y-m", strtotime($origDate));
                return $newDate;
            }
            break;
        }
        return $origDate;
    }
    
    public function filterAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $userDetails = $userSession->user_details;
       
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $reportId = (int)$this->getEvent()->getRouteMatch()->getParam('reportId', 0);
            if ($this->isHavingTrackerAccess($trackerId)) {
                if ($this->isHavingReportAccess($formId, $reportId, $trackerId)) {
                    $reportDetails = $this->getReportService()->getReportDetails($formId, $reportId);
                    $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id' => $formId, 'reportDetails' => $reportDetails));
                    return new ViewModel(
                        array(
                            'formId' => $formId,
                            'trackerId' => $trackerId,
                            'reportId'  =>$reportId,
                            'reportDetails' => $reportDetails
                        )
                    ); 
                } else {
                    return $this->accessDenied(); 
                }
            } else {
                return $this->accessDenied(); 
            }
        }  
    }
    
    public function fetchReportDataAction()
    {
        set_time_limit(0);
        $response = $this->getResponse();
        $columnDefs=array();
        $session = new SessionContainer();
        $configContainer = $session->getSession('config');
        $reportSettings = $configContainer->config['report_settings'];
        $max_rows = isset($configContainer->config['max_row_count']) ? $configContainer->config['max_row_count'] : 10000;
        $userSession = $session->getSession("user");
        $userDetails = $userSession->user_details;
        $isSuperAdmin = isset($userDetails['isSuperAdmin']) ? $userDetails['isSuperAdmin'] : 0;
        
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
        } else {
            $post = $this->getRequest()->getPost()->toArray();
           
            $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
            $condition = isset($post['condition'])?$post['condition']:"";
            $condition = rawurldecode($condition);
            
            $urlQuery = isset($post['urlQuery'])?json_decode(base64_decode($post['urlQuery'])):"";
            
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $reportId = (int)$this->getEvent()->getRouteMatch()->getParam('reportId', 0);
            
            if ((!is_numeric($trackerId) || $trackerId == 0 || !is_numeric($formId) || $formId == 0) || !$this->isHavingReportAccess($formId, $reportId, $trackerId)) {
                return $this->accessDenied();
            }
            $condition = base64_decode($condition);
            
            $allData = $this->getReportService()->getReportData($formId, $reportId, $condition, $max_rows);
            //var_dump($urlQuery);
            if (empty($allData['names']) && !empty($urlQuery)) {
                $allData['names']= explode(',', $urlQuery->names);
                $allData['labels']= $urlQuery->labels;
                $allData['data'] =  $this->getReportService()->getReportQueriedData($urlQuery->query);
            } 
           
            /*foreach ($allData['data'] as $key => $value) {
                foreach ($value as $k => $v) {
                    if ($v == '0000-00-00') {
                        $allData['data'][$key][$k]='';
                    } else if (Date('Y-m-d', strtotime($v)) == $v) {
                        $allData['data'][$key][$k]=Date('d-M-Y', strtotime($allData['data'][$key][$k])); 
                    }
                }
            }*/
            $names = array_map('trim', $allData['names']);
            $labels = (!empty($allData['labels']))?array_map('trim', explode(",", $allData['labels'])):array();
            $columnDefs=array();
            if (!empty($labels)) {
                if ($allData['report_type'] == 'qwl') {
                    $columnDefs[0]['headerName']="";
                    $columnDefs[0]['field']="id";
                    $columnDefs[0]['lockPosition']=true; 
                    $columnDefs[0]['suppressSorting']=true;
                    $columnDefs[0]['suppressMenu']=true;
                    $columnDefs[0]['filter']="false";
                    $columnDefs[0]['cellRenderer'] = "myCellRenderer";
                    $columnDefs[0]['suppressColumnVirtualisation'] = false;
                    foreach ($labels as $key => $arrvalue) { 
                        $columnDefs[$key+1]['headerName'] = $arrvalue;
                        $columnDefs[$key+1]['headerTooltip'] = $arrvalue;
                        (!empty($names))?$columnDefs[$key+1]['field'] = $names[$key+1]:"";
                    }                    
                } else {
                    foreach ($labels as $key => $arrvalue) { 
                        $columnDefs[$key]['headerName'] = $arrvalue;
                        $columnDefs[$key]['headerTooltip'] = $arrvalue;
                        (!empty($names))?$columnDefs[$key]['field'] = $names[$key]:"";
                    }                    
                }
            }
        }
        $allData['labels'] = $columnDefs;
        unset($allData['names']);
        $response->setContent(\Zend\Json\Json::encode($allData)); 
        return $response;
    }

    public function downloadAction()
    {
        set_time_limit(0);
        $response = $this->getResponse();
        $session = new SessionContainer();
        $configContainer = $session->getSession('config');
        $max_rows = isset($configContainer->config['max_row_count']) ? $configContainer->config['max_row_count'] : 10000;
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
        } else {
            $trackerId  = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId     = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $reportId   = (int)$this->getEvent()->getRouteMatch()->getParam('reportId', 0);
            $type       = $this->getEvent()->getRouteMatch()->getParam('type', "");
            $post = $this->getRequest()->getPost()->toArray();
            $post  = filter_var_array($post, FILTER_SANITIZE_STRING);
            $condition = isset($post['condition'])?$post['condition']:"";
            $condition = rawurldecode($condition);
            $headBreadcrumb = isset($post['headBreadcrumb'])?$post['headBreadcrumb']:"";
            $headBreadcrumb = rawurldecode($headBreadcrumb);
            $urlQuery = isset($post['urlQuery'])?$post['urlQuery']:"";
            if (!$this->isHavingReportAccess($formId, $reportId, $trackerId) || $condition == 'NaN' || $headBreadcrumb == 'NaN') {
                return $this->accessDenied();
            }
            $condition = base64_decode($condition);
            $this->getReportService()->downloadReport($trackerId, $formId, $reportId, $type, $condition, $headBreadcrumb, $max_rows, isset($post['filteredData'])?unserialize(base64_decode($post['filteredData'])):'', $urlQuery);
        }
        return $response; 
    }
    
    public function vindexAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $userDetails = $userSession->user_details;
        $configData = $session->getSession('config');        
       
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $reportId = (int)$this->getEvent()->getRouteMatch()->getParam('reportId', 0);
            if ($this->isHavingTrackerAccess($trackerId)) {
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id' => $formId,'reportId' => $reportId));
           
                if ($this->isHavingReportAccess($formId, $reportId, $trackerId)) {         
                    $vrData = $this->getReportService()->getReportFilters($reportId);
                    $reportF= $vrData[report_filters];
                    $kibanaUrl = base64_encode($reportF);
                    $popUpTime = $configData->config['popup_time'];
                    return new ViewModel(array('kibana_url' => $kibanaUrl,'popUp_time'=>$popUpTime));
                } else {
                    return $this->accessDenied(); 
                }
            } else {
                return $this->accessDenied(); 
            }
        }  
    }

    public function customAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $userDetails = $userSession->user_details;
    
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $reportId = (int)$this->getEvent()->getRouteMatch()->getParam('reportId', 0);
            if ($this->isHavingTrackerAccess($trackerId)) {

                $workflowAndFields = $this->getReportService()->getWorkflowAndFields($trackerId, $formId);
                $fieldNames = array();
                foreach ($workflowAndFields as $key => $value) {
                    $fieldNames[$value['workflow_name']][$value['field_name']] =  $value['label'];
                }
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id' => $formId,'reportId' => $reportId));
                return new ViewModel(
                    array(
                    'trackerId' => $trackerId,
                    'formId' => $formId,
                    'reportId' => $reportId,
                    'field_names' => $fieldNames
                    )
                );
            } else {
                return $this->accessDenied(); 
            }
        } 
    }

    public function customReportAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        $userDetails = $userSession->user_details;
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $reportId = (int)$this->getEvent()->getRouteMatch()->getParam('reportId', 0);
            $postData = $this->getRequest()->getPost()->toArray();
            if ($this->isHavingTrackerAccess($trackerId)) {
                if (!empty($postData)) {                 
                    if (isset($postData['custom_report_filter']) && !empty($postData['custom_report_filter'])) {
                        $reportName = $postData['custom_report_name'];
                        $make_query = "";
                        $whr_filter = "";
                        $first = true;                    
                        $report_duplicate=$this->getReportService()->checkIfReportNameExist($reportName, $formId);
                       
                        if (!empty($report_duplicate)) {
                            $response->setContent("<label class='col-md-12 control-label' style = 'color: red;'>Report name is already exist.Please enete some other name.</label>");
                            return $response;
                        }
                        foreach ($postData['custom_report_filter'] as $workflow) {
                            foreach ($workflow as $k => $v) {
                                if ($first == true) {
                                    //$whr_filter.= " WHERE ";
                                    $first = false;
                                } else {
                                    $whr_filter.= " and ";
                                }
                                if ($v[1] == "LIKE %...%") {
                                    $whr_filter.= $v[3] . " LIKE '%" . $v[2] . "%'";
                                } elseif ($v[1] == "IN") {
                                    $in_data = str_replace('"', "'", $v[2]);
                                    $whr_filter.= $v[3] . " IN(" . $in_data . ")";
                                } elseif ($v[1] == "DATE BETWEEN") {
                                    $dates = explode(" - ", $v[2]);
                                    $sDate = date("Y-m-d", strtotime($dates[0]));
                                    $eDate = date("Y-m-d", strtotime($dates[1]));
                                    $whr_filter.= "date(" .$v[3] . ") BETWEEN '".$sDate."' and '".$eDate."'";
                                } else {
                                    $whr_filter.= $v[3] . " " . $v[1] . " '" . $v[2] . "'";
                                }
                            }
                        }
                        $whr_filter = $whr_filter." AND is_deleted='No'";
                       
                        $selectedFields=array();
                        foreach ($postData['select_field_names'] as $key => $value) {
                            $data = explode('$', $value);
                            $selectedFields[$data[0]] = $data[1];
                        }
                        $fieldsName = implode(", ", array_keys($selectedFields));
                        $fieldsValue = implode(", ", $selectedFields);
                        $fieldHeading = "ID, ".$fieldsValue;
                        $editable = "Yes";
                        $typeOfReport = "Custom";
                        $select = "id, ".$fieldsName;
                        $make_query.="SELECT ".$select." FROM form_" . $trackerId . "_" . $formId;
                        if ($postData['is_save'] == 'Yes') {
                            $savedReportId = $this->getReportService()->saveNewCustomReport($formId, $reportName, $make_query, $select, $fieldHeading, $whr_filter);
                            $response->setContent(\Zend\Json\Json::encode($savedReportId));
                            return $response;
                        } else {
                            $make_query = $make_query . " WHERE " . $whr_filter;
                            $data = array();
                            $data['query'] = $make_query;
                            $data['labels'] = $fieldHeading;
                            $data['names'] = $select;
                            $response->setContent(\Zend\Json\Json::encode($data));
                            return $response;
                        } 
                    }
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_204);
                    $response->setContent('Post data is missed.');
                    return $response;
                }
            } else {
                return $this->accessDenied(); 
            }
        } 
    }
}
