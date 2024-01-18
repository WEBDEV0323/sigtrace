<?php

namespace DashboardTest\Controller;

use DashboardTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Dashboard\Controller\DashboardController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use PHPUnit\Framework\TestCase;
use Session\Container\SessionContainer;


class DashboardControllerTest extends TestCase
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
        $this->controller       =   new DashboardController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'dashboard'));
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

    public function testGetModelService()
    {
        $this->assertInstanceOf('Dashboard\Model\Dashboard', $this->controller->getModelService());
    }
    
    public function testIndexAction() 
    {   
        $this->routeMatch->setParam('action', 'index');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array('25','61');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    public function testLoadDashboardAction()
    {
        $this->routeMatch->setParam('action', 'load_dashboard');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array('25','61');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    public function testLoadFiltersAction()
    {
        $this->routeMatch->setParam('action', 'load_filters');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array('25','61');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    public function testListAction()
    {
        $this->routeMatch->setParam('action', 'list');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array('25','61');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    public function testFetchAllDataAction()
    {
        $this->routeMatch->setParam('action', 'fetch_all_data');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array('25','61');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
}
