<?php

namespace ASRTRACE\Dashboard\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;

class DashboardController extends AbstractActionController
{
    protected $_modelService;

    public function getModelService()
    {
        if (!$this->_modelService) {
            $sm = $this->getServiceLocator();
            $this->_modelService = $sm->get('aDashboard\Model\aDashboard');
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
    public function indexAction()
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
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            if ($this->isHavingTrackerPermission($trackerId)) {
                $filters = $this->getModelService()->getDashboardFilters($trackerId, $formId);
                $trackerDetails = $trackerSession->tracker_user_groups;
                $grpId = $trackerDetails[$trackerId]['session_group_id'];
                $grpName = $trackerDetails[$trackerId]['session_group']; 
                
                if (isset($grpName) && $grpName != 'SuperAdmin' && $grpName != 'Administrator') {
                    $formIdArray = $this->getModelService()->formIdCheck($trackerId, $grpName, $grpId);
                    $arrFormIds = array_column($formIdArray, 'form_id');
                    $formId=in_array($arrFormIds, $formId) ? $formId :0;
                }
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id' => $formId));
                return new ViewModel(
                    array(
                        'formId' => $formId,
                        'trackerId' => $trackerId,
                        'filters' => $filters
                    )
                ); 
            }
        }
    }
    public function loadDashboardAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            $post = json_decode(file_get_contents('php://input'), true);
            $filter = isset($post['filter'])?$post['filter']:'all';
            
            $resArray = $this->createDashboardCount($trackerId, $formId, $filter);
            $response->setContent(\Zend\Json\Json::encode($resArray)); 
        }
        return $response;
    }
    
    public function createDashboardCount($trackerId, $formId, $filter)
    {
        $footerTypes = array();
        try {
            $configs = $this->getModelService()->getConfigs();
            if (!empty($configs) && $filter == 'all') {
                if (isset($configs['number_count_reports']) && strtolower($configs['number_count_reports']) == 'on') { 
                    $countReportQueries = $this->getModelService()->getDashboardCountReports($trackerId, $formId);
                    $footerTypes = $this->getModelService()->getDashboardReports(array(), $trackerId, $formId, $countReportQueries, $filter); 
                } 
            }
            $dashboardQueries = $this->getModelService()->getDashboardQueries($trackerId, $formId);
            $reportTypes = $this->getModelService()->getDashboardReports($dashboardQueries, $trackerId, $formId, array(), $filter);
        } catch(\Exception $e) {
            $reportTypes = "";
        } catch(\PDOException $e) {
            $reportTypes = "";
        } 
        return array("data"=>$reportTypes, "footer"=>$footerTypes, "filter"=>$filter);
    }
    
    public function loadFiltersAction()
    {
        $response = $this->getResponse();
        $resArray = array();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            $resArray = $this->getModelService()->getDashboardFilters($trackerId, $formId);
        }
        $response->setContent(\Zend\Json\Json::encode($resArray)); 
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
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
            $queryArray = array();
            if ($this->isHavingTrackerPermission($trackerId)) {
                //$queryString = filter_input(INPUT_SERVER, 'QUERY_STRING', FILTER_SANITIZE_URL);
                //var_dump($queryString);
                //parse_str($queryString, $queryArray);
                //var_dump($queryArray);die;
                //$type = isset($queryArray['type'])?trim($queryArray['type']):"all";
                $type = $this->getEvent()->getRouteMatch()->getParam('type', 'all');
                $filter = $this->getEvent()->getRouteMatch()->getParam('filter', 'all');
                
                $filterData = $this->getModelService()->getDashboardQueries($trackerId, $formId, $type);
                $filerLabel = (!empty($filterData))?array_column($filterData, 'label')[0]:'All';
                $checkTableArray = $this->getModelService()->trackerCheckForms($trackerId, $formId);
                $formDetails = $checkTableArray['form_details'];
                $responseCode = $checkTableArray['responseCode'];
                if ($responseCode == 0) {
                    return $this->redirect()->toRoute('tracker');
                }
                $userDetails = $userSession->user_details;
                $roleId = $userDetails['group_id'];
                $roleName = $userDetails['group_name']; 
                if (isset($trackerSession->tracker_user_groups) && $roleId != 1) {
                    $trackerUserGroups = $trackerSession->tracker_user_groups;
                    $roleName = $trackerUserGroups[$trackerId]['session_group'];
                    $roleId = $trackerUserGroups[$trackerId]['session_group_id'];
                }
                $canUpdateArray = $this->getModelService()->getCanReadAndCanUpdateAccessAllWorkflow($formId, $roleId, $roleName);  
                $canDelete='No';
                $canEdit='No';
                $canRead='No';
                foreach ($canUpdateArray as $canUpdateData) {
                    if (isset($canUpdateData) && $canUpdateData['can_delete']=='Yes') {
                        $canDelete='Yes';
                    } 
                    if (isset($canUpdateData) && $canUpdateData['can_update']=='Yes') {
                        $canEdit='Yes';
                    } 
                    if (isset($canUpdateData) && $canUpdateData['can_read']=='Yes') {
                        $canRead='Yes';
                    } 
                } 
                $showWorkflowLinkArray = $this->getModelService()->showWorkflowLink($formId, $roleId, $roleName); 
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id' => $formId));
                // if ($queryString =='') {
                //     $queryString = '?type=all&filter=all';
                // } else {
                //     $queryString = '?'.$queryString;
                // }
                return new ViewModel(
                    array(
                        'formId' => $formId,
                        'trackerId' => $trackerId,
                        'formDetails' => $formDetails,
                        'type' =>$type,
                        'filter' =>$filter,
                        'label' => $filerLabel,
                        'canDelete' => $canDelete,
                        'canEdit' => $canEdit,
                        'canRead' => $canRead,
                        'showWorkflowLink' => $showWorkflowLinkArray
                    )
                );
            } 
        }
    }
    
    public function fetchAllDataAction()
    {
        $response = $this->getResponse();
        $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('tracker_id', 0);
        $formId = (int)$this->getEvent()->getRouteMatch()->getParam('form_id', 0);
        $subaction_id = (int)$this->getEvent()->getRouteMatch()->getParam('subaction_id', 0);

        $type = $this->getEvent()->getRouteMatch()->getParam('type', 'all');
        $filter = $this->getEvent()->getRouteMatch()->getParam('filter', 'all');

        // $queryArray = array();
        // parse_str(filter_input(INPUT_SERVER, 'QUERY_STRING'), $queryArray);
        $allData = $this->getModelService()->fetchAllData($trackerId, $formId, $type, $filter);
        foreach ($allData['data'] as $key => $value) {
            $allData['data'][$key]['action'] = $value['id'];
        }
        $labels = array_map('trim', explode(",", $allData['labels']));
        $names = array_map('trim', $allData['names']);
        $columnDefs=array();
        if ($subaction_id == 0) {
            if (!empty($labels)) {
                $columnDefs[0]['headerName']="Action";
                $columnDefs[0]['field']="action";
                $columnDefs[0]['lockPosition']=true; 
                $columnDefs[0]['suppressSorting']=true;
                $columnDefs[0]['suppressMenu']=true;
                $columnDefs[0]['filter']="false";
                $columnDefs[0]['width']=210;
                //$columnDefs[0]['suppressSizeToFit']=true;
                $columnDefs[0]['cellRenderer'] = "myCellRenderer";
                $columnDefs[0]['suppressColumnVirtualisation'] = false;
                foreach ($labels as $key => $arrvalue) {
                    $columnDefs[$key+1]['headerName'] = $columnDefs[$key+1]['headerTooltip'] = $arrvalue;
                    (!empty($names))?$columnDefs[$key+1]['field'] = $names[$key+1]:"";
                }
            }
        } else {
            foreach ($labels as $key => $arrvalue) {
                $columnDefs[$key]['headerTooltip'] = $columnDefs[$key]['headerName'] = $arrvalue;
                (!empty($names))?$columnDefs[$key+1]['field'] = $names[$key+1]:"";
            }
        }
        $allData['labels'] = $columnDefs;
        unset($allData['names']);
        $response->setContent(\Zend\Json\Json::encode($allData)); 
        return $response;
    }
}
