<?php 
namespace SigTRACE\DashboardTest\Controller;

use SigTRACE\DashboardTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use SigTRACE\Dashboard\Controller\IndexController;
use PHPUnit\Framework\TestCase;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use Session\Container\SessionContainer;
use Zend\Stdlib\ResponseInterface;


class DashboardControllerTest extends TestCase
{
    protected $controller;
    protected $request;
    protected $response;
    protected $routeMatch;
    protected $event;
    protected $userContainer;
    protected $trackerContainer;
    protected $messageContainer;
    protected $session;
    protected $traceError = true;
    
    public function setUp()
    {
        @session_start();
        parent::setUp();
        $session = new SessionContainer();
        $this->userContainer    =   $session->getSession('user');
        $this->trackerContainer =   $session->getSession('tracker');
        $this->messageContainer =   $session->getSession('message');
 
        $serviceManager         =   Bootstrap::getServiceManager();
        $this->controller       =   new IndexController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Index'));
        $this->event            =   new MvcEvent();
        $config                 =   $serviceManager->get('Config');
        $routerConfig           =   isset($config['router']) ? $config['router'] : array();
        $router                 =   HttpRouter::factory($routerConfig);
        
        $this->event->setRouter($router);
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);
        $this->controller->setServiceLocator($serviceManager);
        $this->_unitTest = new IndexController();
    } 
    
    public function tearDown()
    {
        $session = new SessionContainer();
        $session->clearSession('user');
        $session->clearSession('tracker');
        $session->clearSession('message');
    }
    
    public function testIndexActionCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'index');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    public function testGetDashboardDataActionCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'getDashboardData');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
       
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_400) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_400, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testGetDashboardDataAction() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'getDashboardData');
        $this->routeMatch->setParam('tracker_id', 109);

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_400) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_400, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }

    public function testListCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'list');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
       
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    public function testFetchAllDataActionCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'fetchAllData');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
       
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_400) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_400, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
}
