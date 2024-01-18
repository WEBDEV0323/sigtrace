<?php

namespace TrackerTest\Controller;

use TrackerTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Tracker\Controller\WorkflowMgmController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
//use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit\Framework\TestCase;
use Zend\Session\Container;
use Session\Container\SessionContainer;


class WorkflowMgmControllerTest extends TestCase
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
        $this->controller       =   new WorkflowMgmController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'WorkflowMgm'));
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
    
    public function testUpdateAndSaveWorkflowDataActionCanBeAccessed() 
    {   
        $this->routeMatch->setParam('action', 'updateAndSaveWorkflowData');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    public function testGetWorkflowMgmServiceReturnsAnInstanceOfWorkflowMgmModel()
    {
        $this->assertInstanceOf('Tracker\Model\WorkflowMgmModule', $this->controller->getWorkflowMgmService());
    } 

    public function testGetFieldsByWorkflowIdActionCanBeAccessed() 
    {   
        $this->routeMatch->setParam('action', 'getFieldsByWorkflowId');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    public function testGetFieldsByWorkflowIdAction() 
    {   
        $this->routeMatch->setParam('action', 'getFieldsByWorkflowId');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->routeMatch->setParam('workflow_id', 1);
        $this->routeMatch->setParam('record_id', 20);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    public function testUpdateAndSaveWorkflowDataAction() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'updateAndSaveWorkflowData');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'import_csv_id' => 1,
                        'product_name' => 'TestProduct',
                        'report_type' => 'PSUR',
                        'report_cycle' => '70 Days',
                        'country' => 'India'
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
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

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_404, $response->getStatusCode());
    }

    public function testviewrecordActionCanBeAccessed()
    {
        $this->routeMatch->setParam('action', 'viewrecord');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_404, $response->getStatusCode());
    }
    public function testviewrecord()
    {
        $this->routeMatch->setParam('action', 'viewrecord');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertTrue(empty($response->getContent()));
    }

    public function testWorkflowRuleActionCanBeAccessed()
    {
        $this->routeMatch->setParam('action', 'workflowRule');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_404, $response->getStatusCode());
    }
    public function testWorkflowRule()
    {
        $this->routeMatch->setParam('action', 'workflowRule');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertTrue(empty($response->getContent()));
    }
    
}

