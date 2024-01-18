<?php

namespace AuthenticationTest\Controller;

use AuthenticationTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Authentication\Controller\AuthenticationController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use PHPUnit\Framework\TestCase;
use Session\Container\SessionContainer;


class AuthenticationControllerTest extends TestCase
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
        $this->controller       =   new AuthenticationController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Authentication'));
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
    
    public function testgetAuthService()
    {
        $this->assertInstanceOf('Common\Authentication\Model\Authentication', $this->controller->getAuthService());
    }
    
    public function testErrorpageAction()
    {
        $this->routeMatch->setParam('action', 'errorpage');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testLogoutAction()
    {
        $this->userContainer->u_id = 1;
        $this->routeMatch->setParam('action', 'logout');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    }
    public function testAuthenticationCannotBeAccessedWithUserSession() 
    {   
        $this->routeMatch->setParam('action', 'index');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    }
}

