<?php

namespace TrackerTest\Controller;

use TrackerTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Tracker\Controller\WorkflowController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
//use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit\Framework\TestCase;
use Zend\Session\Container;
use Session\Container\SessionContainer;


class WorkflowControllerTest extends TestCase
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
        $this->controller       =   new WorkflowController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Workflow'));
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
    
    public function testgetWorkflowModelService()
    {
        $this->assertInstanceOf('Common\Tracker\Model\Workflow', $this->controller->getModelService());
    } 
    
    public function testworkflowManagement() 
    {   
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('action', 'workflowManagement');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testworkflowManagementWithoutUserData() 
    {   
        $this->routeMatch->setParam('action', 'workflowManagement');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    public function testAdd() 
    {   
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_ids = array(25, 35);
        $this->routeMatch->setParam('action', 'add');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'workflow_id' => 25
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testAddWithoutUserData() 
    {   
        $this->trackerContainer->tracker_ids = array(25, 35);
        $this->routeMatch->setParam('action', 'add');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'workflow_id' => 25
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    public function testaddUpdateWorkflowAction() 
    {   
        $this->routeMatch->setParam('action', 'addUpdateWorkflow');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->routeMatch->setParam('workflow_id', 1);
        $this->routeMatch->setParam('record_id', 4);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'wfNames' => array('Email Intake', 'Triage', 'Data Entry'),
                        'reason' => 'Test',
                        'wfSortOrder' => 1,
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testaddUpdateNewWorkflowAction() 
    {   
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'addUpdateWorkflow');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->routeMatch->setParam('workflow_id', 0);
        $this->routeMatch->setParam('record_id', 4);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'wfNames' => array('Email Intake Test', 'Triage Test', 'Data Entry Test'),
                        'reason' => 'Test',
                        'wfSortOrder' => 1,
                        'formId' => 1,
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    /* public function testaddUpdateNewErrorWorkflowAction() 
    {   
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'addUpdateWorkflow');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->routeMatch->setParam('workflow_id', 0);
        $this->routeMatch->setParam('record_id', 4);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'wfNames' => array('Email Intake Test', 'Triage Test', 'Data Entry Test'),
                        'wfSortOrder' => 'cde',
                        'formId' => 'abc',
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    } */
    
    public function testaddUpdateWorkflowActionWithoutUserData() 
    {   
        $this->routeMatch->setParam('action', 'addUpdateWorkflow');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->routeMatch->setParam('workflow_id', 1);
        $this->routeMatch->setParam('record_id', 4);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'wfNames' => array('Email Intake', 'Triage', 'Data Entry'),
                        'reason' => 'Test',
                        'wfSortOrder' => 1,
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        }
    }
    
    public function testaddUpdateWorkflowActionWithoutPostData() 
    {   
        $this->routeMatch->setParam('action', 'addUpdateWorkflow');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->routeMatch->setParam('workflow_id', 1);
        $this->routeMatch->setParam('record_id', 4);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
    public function testDeleteAction() 
    {   
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'delete');
        //$this->userContainer->u_id = 1;
        //$this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->routeMatch->setParam('workflow_id', 1);
        $this->routeMatch->setParam('record_id', 4);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'workflowID' => 2,
                        'workflow_name' => 'Email Intake',
                        'trackerId' => 25,
                        'formId' => 85,
                        'reason' => 'Test',
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        }
    }
    
    public function testDeleteInvalidDataAction() 
    {   
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'delete');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->routeMatch->setParam('workflow_id', 1);
        $this->routeMatch->setParam('record_id', 4);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'workflowID' => 'abc',
                        'workflow_name' => 'Email Intake',
                        'trackerId' => 25,
                        'formId' => 85,
                        'reason' => 'Test',
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

       
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_501, $response->getStatusCode());           
        } 
    }
    
    public function testDeleteNoPostDataAction() 
    {   
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'delete');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->routeMatch->setParam('workflow_id', 1);
        $this->routeMatch->setParam('record_id', 4);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
    public function testchangeOrderNoUserData()
    {
        $this->routeMatch->setParam('action', 'changeOrder');
        //$this->userContainer->u_id = 1;
        //$this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'wf_id_for_sort' => array(2, 3),
                        'reason' => 'Test',
                        'wf_sort_order' => array(1, 2),
                        'formId' => 1,
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        } 
    }
    
    public function testchangeOrder()
    {
        $this->routeMatch->setParam('action', 'changeOrder');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'wf_id_for_sort' => array(2, 3),
                        'reason' => 'Test',
                        'wf_sort_order' => array(1, 2),
                        'formId' => 1,
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        } 
    }
    
    /* public function testchangeOrderNoPostData()
    {
        $this->routeMatch->setParam('action', 'changeOrder');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    } */
    
    public function testgetFieldsByWorkflowId() 
    {   
        $this->routeMatch->setParam('action', 'getFieldsByWorkflowId');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'workflow_id' => 2,
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();

        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }

    /* public function testgetmaxfield()
    {
        $this->routeMatch->setParam('action', 'getmaxfield');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'workflowId' => 2,
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode()); 
    } */
    public function testsettings()
    {
        $this->routeMatch->setParam('action', 'settings');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Administrator','email'=>'unittest@test.com');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode()); 
    }
    
    public function testsettingsNoUserData()
    {
        $this->routeMatch->setParam('action', 'settings');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode()); 
    }

    public function testdeleteRuleNoPostData()
    {
        $this->routeMatch->setParam('action', 'deleteRule');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
    public function testdeleteRuleNoUserData()
    {
        $this->routeMatch->setParam('action', 'deleteRule');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'ruleId' => 2,
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testdeleteRule()
    {
        $this->routeMatch->setParam('action', 'deleteRule');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'ruleId' => 2,
                        'formId' => 2,
                        'reason' => 'Test',
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testgetWorkflowsAndFieldsNoUserData()
    {
        $this->routeMatch->setParam('action', 'getWorkflowsAndFields');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'form_id' => 2,
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetWorkflowsAndFieldsNoPostData()
    {
        $this->routeMatch->setParam('action', 'getWorkflowsAndFields');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
    public function testgetWorkflowsAndFields()
    {
        $this->routeMatch->setParam('action', 'getWorkflowsAndFields');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                       'form_id' => 2,
                       )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testsaveRuleNoUserData()
    {
        $this->routeMatch->setParam('action', 'saveRule');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'reason' => 'Test',
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testsaveRuleNoPostData()
    {
        $this->routeMatch->setParam('action', 'saveRule');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
    public function testsaveRule()
    {
        $this->routeMatch->setParam('action', 'saveRule');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'reason' => 'Test',
                        'ruleId' => 1,
                        'formId' => 2,
                        'condition_on_field' => 0,
                        'condition_operand' => '',
                        'action_value' => array(1, 2, 3),
                        'action_name'=> array('Test1', 'Test2', 'Test3'),
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testgetRuleInfo()
    {
        $this->routeMatch->setParam('action', 'getRuleInfo');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'rule_id' => 1,
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testgetRuleInfoNoUserData()
    {
        $this->routeMatch->setParam('action', 'getRuleInfo');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'rule_id' => 1,
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetRuleInfoNoPostData()
    {
        $this->routeMatch->setParam('action', 'getRuleInfo');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
    public function testsavefields()
    {
        $this->routeMatch->setParam('action', 'savefields');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                       'reason' => 'Test',
                       )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testsavefieldsNoUserData()
    {
        $this->routeMatch->setParam('action', 'savefields');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'reason' => 'Test',
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testsavefieldsNoPostData()
    {
        $this->routeMatch->setParam('action', 'savefields');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action_id', 85);
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
}

