<?php

namespace Common\Trigger\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Session\Container\SessionContainer;

class TriggerController extends AbstractActionController
{
    protected $_auditService;
    protected $_triggerService;
    
    public function getAuditService()
    {
        if (!$this->_auditService) {
            $sm = $this->getServiceLocator();
            $this->_auditService = $sm->get('Audit\Service');
        }
        return $this->_auditService;
    }
    
    public function getTriggerService()
    {
        if (!$this->_triggerService) {
            $sm = $this->getServiceLocator();
            $this->_triggerService = $sm->get('Trigger\Service');
        }
        return $this->_triggerService;
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
            $this->getTriggerService()->checkTrigger();
        }
    }
}

