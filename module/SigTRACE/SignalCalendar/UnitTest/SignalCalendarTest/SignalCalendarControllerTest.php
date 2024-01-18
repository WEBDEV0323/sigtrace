<?php

namespace SigTRACE\SignalCalendarTest\Controller;

use SigTRACE\SignalCalendarTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use SigTRACE\SignalCalendar\Controller\SignalCalendarController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use PHPUnit\Framework\TestCase;
use Session\Container\SessionContainer;

class SignalCalendarControllerTest extends TestCase
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
        $this->controller       =   new SignalCalendarController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'SignalCalendar'));
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
    public function testIndexAction() 
    {   
        $this->routeMatch->setParam('action', 'list');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('26' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array('26','86');
        $this->routeMatch->setParam('trackerId', 26);
        $this->routeMatch->setParam('form_id', 86);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
}
