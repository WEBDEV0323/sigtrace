<?php
namespace Application\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Session\Container\SessionContainer;

class IndexController extends AbstractActionController
{
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
            return new ViewModel();
        }
    }
    
    public function healthcheckAction()
    {
        $response = $this->getResponse();
        $response->setStatusCode(\Zend\Http\Response::STATUS_CODE_200);
        return $response;
    }
}
