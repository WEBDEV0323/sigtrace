<?php
namespace SigTRACE\FatalCumulativeAnalysis\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Session\Container\SessionContainer;

class FatalCumulativeAnalysisController extends AbstractActionController
{
    protected $_serviceMapper;
    protected $_auditMapper;
    protected $_fatalCumulativeAnalysisServiceMapper;
    
    public function getAuditService()
    {
        if (!$this->_auditMapper) {
            $sm = $this->getServiceLocator();
            $this->_auditMapper = $sm->get('Audit\Model\Audit');
        }
        return $this->_auditMapper;
    }
    public function getServiceModel()
    {
        if (!$this->_serviceMapper) {
            $sm = $this->getServiceLocator();
            $this->_serviceMapper = $sm->get('FatalCumulativeAnalysis\Model\FatalCumulativeAnalysis');
        }
        return $this->_serviceMapper;
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
            if ($this->isHavingTrackerAccess($trackerId) && $formId > 0) {
                $this->layout()->setVariables(array('tracker_id' => $trackerId,'form_id'=>$formId));
                $list = $this->getServiceModel()->getFatalData($trackerId, $formId);
                $header = $this->getServiceModel()->getHeaderList($formId);
                $view = new ViewModel();
                return $view->setVariables(array('trackerId'=>$trackerId,'formId'=>$formId,'lists'=>$list,'headers'=>$header));
            } else {
                $response->setContent('You do not have permission to access this tracker');
                return $response;
            }
        }
    }
}

