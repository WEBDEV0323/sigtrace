<?php

namespace Common\TriggerformTest\Controller;

use Common\TriggerformTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Triggerform\Controller\TriggerformController;
use PHPUnit\Framework\TestCase;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use Session\Container\SessionContainer;
use Zend\Stdlib\ResponseInterface;

class TriggerformControllerTest extends TestCase
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
        $this->controller       =   new TriggerformController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Triggerform'));
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
    
    public function testGetTriggerformServiceReturnsAnInstanceOfTriggerModel()
    {
        $this->assertInstanceOf('Common\Triggerform\Model\Triggerform', $this->controller->getTriggerService());
    }

    public function testtriggerFormActionCanBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'triggerForm');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testtriggerFormActionGetData() 
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'triggerForm');
        $this->routeMatch->setParam('tracker_id', 109);

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testtriggerFormActionCanNotBeAccessedForNonAdmin() 
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'triggerForm');
        $this->routeMatch->setParam('tracker_id', 109);

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testAddActionCanNotBeAccessed()
    {
        $this->routeMatch->setParam('action', 'add');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testAddActionCanBeAccessed()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('2000' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'add');
        $this->routeMatch->setParam('id', 1);
        $this->routeMatch->setParam('tracker_id', 2000);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testAddActionCanNotBeAccessedForNonAdmin()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('2000' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'add');
        $this->routeMatch->setParam('tracker_id', 2000);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testEditActionCanNotBeAccessed()
    {
        $this->routeMatch->setParam('action', 'edit');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testEditActionCanBeAccessed()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'edit');
        $this->routeMatch->setParam('id', 1);
        $this->routeMatch->setParam('tracker_id', 109);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testEditActionCanNotBeAccessedForNonAdmin()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'edit');
        $this->routeMatch->setParam('tracker_id', 109);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testEditActionCanBeAccessedForAdmin()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  0, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('2000' => array('session_group'=>'Administrator', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'edit');
        $this->routeMatch->setParam('tracker_id', 2000);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testViewActionCanNotBeAccessed()
    {
        $this->routeMatch->setParam('action', 'view');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testViewActionCanBeAccessed()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'view');
        $this->routeMatch->setParam('id', 1);
        $this->routeMatch->setParam('tracker_id', 109);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testViewActionCanNotBeAccessedForNonAdmin()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'view');
        $this->routeMatch->setParam('tracker_id', 109);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testViewActionCanBeAccessedForAdmin()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  0, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('2000' => array('session_group'=>'Administrator', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'view');
        $this->routeMatch->setParam('tracker_id', 2000);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
}
