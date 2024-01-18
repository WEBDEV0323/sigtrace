<?php
namespace SigTRACE\FrequencyAnalysis\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;

class FrequencyAnalysisController extends AbstractActionController
{
    protected $_serviceMapper;
    protected $_frequencyAnalysisServiceMapper;
    public function getServiceModel()
    {
        if (!$this->_serviceMapper) {
            $sm = $this->getServiceLocator();
            $this->_serviceMapper = $sm->get('FrequencyAnalysis\Model\Db');
        }
        return $this->_serviceMapper;
    }
    
    public function getFrequencyAnalysisModel()
    {
        if (!$this->_frequencyAnalysisServiceMapper) {
            $sm = $this->getServiceLocator();
            $this->_frequencyAnalysisServiceMapper = $sm->get('FrequencyAnalysis\Model\FrequencyAnalysis');
        }
        return $this->_frequencyAnalysisServiceMapper;
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
    public function analysisAction()
    { 
        set_time_limit(0);
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            if ($this->isHavingTrackerAccess($trackerId) && $formId > 0) {
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
                return new ViewModel(
                    array(
                        'trackerId'=>$trackerId,'formId'=>$formId
                    )
                );
            } else {
                $response->setContent('You do not have permission to access this tracker');
                return $response;
            }  
        }
    }
    
    public function fetchAllDataAction()
    { 
        set_time_limit(0);
        $response = $this->getResponse();
        $session = new SessionContainer();
        $userSession = $session->getSession("user");
        if (!isset($userSession->u_id)) {
            $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_401);
            $response->setContent('Your session has been expired. Please <a href="/">login</a> again');
            return $response;
        } else {
            $trackerId = (int)$this->getEvent()->getRouteMatch()->getParam('trackerId', 0);
            $formId = (int)$this->getEvent()->getRouteMatch()->getParam('formId', 0);
            $exp1 = (int)$this->getEvent()->getRouteMatch()->getParam('exp1', 0);
            $exp2 = (int)$this->getEvent()->getRouteMatch()->getParam('exp2', 0);
            $data['data']  = array();
            if ($this->isHavingTrackerAccess($trackerId) && $formId > 0) {
                $data['data'] = $this->getFrequencyAnalysisModel()->getFrequencyAnalysisData($trackerId, $formId, $exp1, $exp2);
                $response->setContent(\Zend\Json\Json::encode($data)); 
                return $response;
            } else {
                $response->setContent('You do not have permission to access this tracker');
                return $response;
            }  
        }
    }
}

