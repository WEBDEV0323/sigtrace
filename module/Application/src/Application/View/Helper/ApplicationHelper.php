<?php
namespace Application\View\Helper;
use Zend\View\Helper\AbstractHelper;
use Session\Container\SessionContainer;

class ApplicationHelper extends AbstractHelper
{
    protected $_request;
    public function __construct($request)
    {
        $this->_request = $request;
    }
    
    public function __invoke($data)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        
        $trackerId = isset($data['tracker_id']) ? $data['tracker_id'] : 0;
        $formId = isset($data['form_id']) ? $data['form_id'] : 0;
        $codelist_id = isset($data['codelist_id'])?intval($data['codelist_id']):0;
        $dataQuery = isset($data['dataQuery'])?$data['dataQuery']:"";
        $userRoleType = $userContainer->offsetGet('roleNameType');
        
        $userDetails = isset($userContainer->user_details)?$userContainer->user_details:array();
        $roleId = isset($userDetails['group_id'])?$userDetails['group_id']:0;
        $roleName = isset($userDetails['group_name'])?$userDetails['group_name']:"";
        $helper = array();
        if ($trackerId != 0) {
            if (isset($trackerContainer->tracker_user_groups) && $roleId != 1) {
                $trackerUserGroups = $trackerContainer->tracker_user_groups;
                $roleName = isset($trackerUserGroups[$trackerId]['session_group'])?$trackerUserGroups[$trackerId]['session_group']:'';
                $roleId = isset($trackerUserGroups[$trackerId]['session_group_id'])?$trackerUserGroups[$trackerId]['session_group_id']:0;
            }
            $helper[0] = $this->_request->getWorkflows($trackerId, $formId, $roleName, $roleId);
            $helper[1] = $this->_request->getAllReports($formId, $roleId, $roleName);
            $helper[2] = $this->_request->getCalendarData($trackerId, $formId);
        }
        if ($codelist_id == 0 && $dataQuery == "") {
            $helper[3] = $this->_request->dashboardResults();
        } 
        if ($codelist_id > 0) {
            $helper[4] = $this->_request->getCodelistData($codelist_id);
        }
        
        if ($dataQuery != "") {
            $helper[5] = $this->_request->getData($dataQuery);
        }
        $helper['menu'] = $this->_request->getNavigations($roleId, $trackerId, $userRoleType);
        $helper[6] = $this->_request->getFormsInfo($formId);
        $helper[7] = $this->_request->getValidationWorkflows($formId);
        $helper[8] = $this->_request->getAppConfigs();
        return $helper;
    }
}


 
