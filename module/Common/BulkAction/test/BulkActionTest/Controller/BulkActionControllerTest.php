<?php

namespace BulkActionTest\Controller;

use BulkActionTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\BulkAction\Controller\BulkActionController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use PHPUnit\Framework\TestCase;
use Session\Container\SessionContainer;


class BulkActionControllerTest extends TestCase
{
    protected $controller;
    protected $request;
    protected $response;
    protected $routeMatch;
    protected $event;
    protected $userContainer;
    protected $trackerContainer;
    protected $session;
    
    protected $traceError = true;
    
    public function setUp()
    { 

        @session_start();
        parent::setUp();
        $session = new SessionContainer();
        $this->userContainer    =   $session->getSession('user');
        $this->trackerContainer =   $session->getSession('tracker');
        
        $serviceManager         =   Bootstrap::getServiceManager();
        $this->controller       =   new BulkActionController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'BulkAction'));
        $this->event            =   new MvcEvent();
        $config                 =   $serviceManager->get('Config');
        $routerConfig           =   isset($config['router']) ? $config['router'] : array();
        $router                 =   HttpRouter::factory($routerConfig);
        
        $this->event->setRouter($router);
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);
        $this->controller->setServiceLocator($serviceManager);
    }
    public function tearDown()
    {
        $session = new SessionContainer();
        $session->clearSession('user');
        $session->clearSession('tracker');
    }
    
    public function testgetAuditService()
    {
        $this->assertInstanceOf('Common\Audit\Model\Audit', $this->controller->getAuditService());
    } 
    
    public function testgetBulkActionService()
    {
        $this->assertInstanceOf('Common\BulkAction\Model\BulkAction', $this->controller->getBulkActionService());
    }
    
    public function testBulkActionCannotBeAccessedWithoutUserSession() 
    {   
        $this->routeMatch->setParam('action', 'index');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
}

