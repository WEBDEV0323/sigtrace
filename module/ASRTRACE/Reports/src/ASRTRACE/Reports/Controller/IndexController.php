<?php

namespace Reports\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;

class IndexController extends AbstractActionController
{
    protected $_modelService;

    public function getModelService()
    {
        if (!$this->_modelService) {
            $sm = $this->getServiceLocator();
            $this->_modelService = $sm->get('Reports\Model\Reports');
        }
        return $this->_modelService;
    }
    public function isHavingTrackerPermission($trackerId)
    {
        $session = new SessionContainer();
        $trackerSession = $session->getSession("tracker");
        $trackerIds = $trackerSession->tracker_ids;
        if (!in_array($trackerId, $trackerIds)) {
            return $this->redirect()->toRoute('tracker'); 
        }
        return true;
    }
    
    public function hasReportAccess($formId, $reportId, $trackerId) 
    {
        $session = new SessionContainer();
        $trackerContainer = $session->getSession("tracker");
        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        $roleId = isset($trackerUserGroups[$trackerId]['session_group_id'])?$trackerUserGroups[$trackerId]['session_group_id']:0; 
        $roleName = isset($trackerUserGroups[$trackerId]['session_group'])?$trackerUserGroups[$trackerId]['session_group']:'';
        $aReportAccess = array();

        if ($roleId != 1 && $roleName != 'Administrator') {
            $aReportAccess = $this->getModelService()->getReportAccessSettings($formId, $reportId, $roleId);
            if (count($aReportAccess) <= 0) {
                return false;
            }
        }

        return true;
    }
    
    public function indexAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            $cond = $headBreadcrumb = '';
            if (isset($post['data'])) {
                foreach ($post['data'] as $filter) {
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
                            $cond .= " AND DATE_FORMAT(".$filter['name'].", '%Y-%m-%d') BETWEEN '".$this->dateConvertion(trim($daterange[0]))."' AND '".$this->dateConvertion(trim($daterange[1]))."'";
                            $headBreadcrumb .= " AND ".$filter['label']." From ".$daterange[0]." To ".$daterange[1];
                        }
                        break;
                    case 'monthrange':
                        if (isset($filter['value']) && $filter['value']!= '') {
                            $monthrange = explode("-", $filter['value']);
                            $cond .= " AND DATE_FORMAT(".$filter['name'].", '%m-%Y') BETWEEN '".$this->dateConvertion(trim($monthrange[0]), 'monthrange')."' AND '".$this->dateConvertion(trim($monthrange[1]), 'monthrange')."'";
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
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            $reportId = (int)$this->getEvent()->getRouteMatch()->getParam('report_id', 0);
            if ($this->isHavingTrackerPermission($trackerId)) {
                if ($this->hasReportAccess($formId, $reportId, $trackerId)) {
                    $reportDetails = $this->getModelService()->getReportDetails($formId, $reportId);
                    $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id' => $formId));
                    return new ViewModel(
                        array(
                            'formId' => $formId,
                            'trackerId' => $trackerId,
                            'reportId'  =>$reportId,
                            'reportDetails' => $reportDetails,
                            'condition' => $cond,
                            'headBreadcrumb'=>$headBreadcrumb,
                        )
                    ); 
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                    $response->setContent('Access Denied!!');
                    return $response; 
                }
            }
        }
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
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            $reportId = (int)$this->getEvent()->getRouteMatch()->getParam('report_id', 0);
            if ($this->isHavingTrackerPermission($trackerId)) {
                if ($this->hasReportAccess($formId, $reportId, $trackerId)) {
                    $reportDetails = $this->getModelService()->getReportDetails($formId, $reportId);
                    $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id' => $formId));
                    return new ViewModel(
                        array(
                            'formId' => $formId,
                            'trackerId' => $trackerId,
                            'reportId'  =>$reportId,
                            'reportDetails' => $reportDetails
                        )
                    ); 
                } else {
                    $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                    $response->setContent('Access Denied!!');
                    return $response; 
                }
            }
        } 
    }
    public function fetchReportDataAction()
    {
        $response = $this->getResponse();
        $columnDefs=array();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
        } else {
            $post = $this->getRequest()->getPost()->toArray();
            $condition = isset($post['condition'])?$post['condition']:"";
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            $reportId = (int)$this->getEvent()->getRouteMatch()->getParam('report_id', 0);
            
            if (!$this->hasReportAccess($formId, $reportId, $trackerId)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('Access Denied!!');
                return $response;
            }
            
            $allData = $this->getModelService()->getReportData($trackerId, $formId, $reportId, $condition);
            $names = array_map('trim', $allData['names']);
            $labels = (!empty($allData['labels']))?array_map('trim', explode(",", $allData['labels'])):array();
            foreach ($labels as $key => $arrvalue) {
                $columnDefs[$key]['headerName'] = $columnDefs[$key]['headerTooltip'] = $arrvalue;
                (!empty($names))?$columnDefs[$key]['field'] = $names[$key]:"";
                (!empty($names))?$columnDefs[$key]['tooltipField'] = $names[$key]:"";
            }
        }
        $allData['labels'] = $columnDefs;
        unset($allData['names']);
        $response->setContent(\Zend\Json\Json::encode($allData)); 
        return $response;
    }
    
    public function downloadCSVAction()
    {
        set_time_limit(0);
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            $reportId = (int)$this->getEvent()->getRouteMatch()->getParam('report_id', 0);
            
            if (!$this->hasReportAccess($formId, $reportId, $trackerId)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('Access Denied!!');
                return $response;
            }
            
            $post = $this->getRequest()->getPost()->toArray();
            $condition = isset($post['condition'])?$post['condition']:"";
            $headBreadcrumb = isset($post['headBreadcrumb'])?$post['headBreadcrumb']:"";
            $this->getModelService()->downloadCSV($trackerId, $formId, $reportId, $condition, $headBreadcrumb);
        }
        return $response;
    }
    public function downloadEXCELAction()
    {
        set_time_limit(0);
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            $reportId = (int)$this->getEvent()->getRouteMatch()->getParam('report_id', 0);
            
            if (!$this->hasReportAccess($formId, $reportId, $trackerId)) {
                $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
                $response->setContent('Access Denied!!');
                return $response;
            }
            
            $post = $this->getRequest()->getPost()->toArray();
            $condition = isset($post['condition'])?$post['condition']:"";
            $headBreadcrumb = isset($post['headBreadcrumb'])?$post['headBreadcrumb']:"";
            $this->getModelService()->downloadEXCEL($trackerId, $formId, $reportId, $condition, $headBreadcrumb);
        }
        return $response;    
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
                $newDate = date("m-Y", strtotime($origDate));
                return $newDate;
            }
            break;
        }
        return $origDate;
    }
}
