<?php
namespace Common\Audit\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;

class AuditController extends AbstractActionController
{
    protected $_auditMapper;
    
    public function getAuditService()
    {
        if (!$this->_auditMapper) {
            $sm = $this->getServiceLocator();
            $this->_auditMapper = $sm->get('Audit\Service');
        }
        return $this->_auditMapper;
    }
    public function getTrackerService()
    {
        if (!$this->_trackerService) {
            $sm = $this->getServiceLocator();
            $this->_trackerService = $sm->get('Tracker\Model');
        }
        return $this->_trackerService;
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
    
    
    public function indexAction()
    {
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $configData = $session->getSession('config');        
        if (!isset($userContainer->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $clientInfo = 0; 
            $userDetails = $userContainer->user_details;
            if ($trackerId > 0) {
                if ($this->isHavingTrackerAccess($trackerId)) {
                    $this->layout()->setVariables(array('tracker_id' => $trackerId)); 
                    $trackerInfo = $this->getTrackerService()->getTrackerInformation($trackerId);
                    $clientInfo = isset($trackerInfo['client_id'])?$trackerInfo['client_id']:0;
                } else {
                    $response->setContent('You do not have permission to access this tracker');
                    return $response;
                }
            }
            $this->saveToLog(0, isset($userDetails['email'])?$userDetails['email']:'', "Audit Trail View", '', '', "Audit Trail View", "Success", 0);
            $config = $this->getServiceLocator()->get('Config');
            $kibanaUrl = $config['kibana']['url']['kibana_url'];
            $popUpTime = $configData->config['popup_time'];
            return new ViewModel(array('kibana_url' => $kibanaUrl, 'clientData' => $clientInfo,'trackerId'=>$trackerId,'popUp_time'=>$popUpTime));
        }
    }
    
    
    
    /*
     * function to save audit logs
     */
    public function saveToLog($id = "", $username= "", $action= "", $originalValue= "", $newValue= "", $reasonForChange= "", $status= "",$customer=0) 
    {
        $this->getAuditService()->saveToLog($id, $username, $action, $originalValue, $newValue, $reasonForChange, $status, $customer);
    }
}
