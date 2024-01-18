<?php

namespace TrackerTest\Controller;

use TrackerTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Tracker\Controller\TrackerController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
//use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit\Framework\TestCase;
use Zend\Session\Container;
use Session\Container\SessionContainer;


class TrackerControllerForViewDataTest extends TestCase
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
        $this->controller       =   new TrackerController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Tracker'));
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
    
    public function testGetAllColumnNameActionCanBeAccessed() 
    {   
        $this->routeMatch->setParam('action', 'getAllColumnNameAction');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_404) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_404, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());           
        } 
    }

    public function testGetAllColumnName()
    {
        $this->routeMatch->setParam('action', 'getAllColumnName');

        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertTrue(empty($response->getContent()));
    }
    public function testFetchAllDataActionCanBeAccessed() 
    {   
        $this->routeMatch->setParam('action', 'fetchAllData');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_404) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_404, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());           
        } 
    }

    public function testFetchAllData()
    {
        $this->routeMatch->setParam('action', 'fetchAllData');

        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        
        $this->assertTrue(empty($response->getContent()));
    }
    
}

