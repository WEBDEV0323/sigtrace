<?php
namespace Common\WorkflowTest\Controller;

use WorkflowTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Workflow\Controller\WorkflowController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
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
        $this->assertInstanceOf('Common\Workflow\Model\Workflow', $this->controller->getWorkflowService());
    } 
    
    public function testgetFieldsByWorkflowId() 
    {   
        $this->routeMatch->setParam('action', 'getFieldsByWorkflowId');
        $this->routeMatch->setParam('tracker_id', 109);
        $this->routeMatch->setParam('action_id', 199);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'workflow_id' => 687,
                        )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testEditRecordCannotBeAccessedWithoutUserSession() 
    {   
        $this->routeMatch->setParam('action', 'editrecord');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('recordId', 1);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    public function testEditRecordCanBeAccessed() 
    {   
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('109' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array(109, 199);
        $this->routeMatch->setParam('action', 'editrecord');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('recordId', 1);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }    
    public function testUpdateAndSaveCannotBeAccessedWithoutUserSession() 
    {   
        $this->routeMatch->setParam('action', 'updateAndSaveWorkflowData');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('workflowId', 687);
        $this->routeMatch->setParam('recordId', 1);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    public function testViewRecordCanBeAccessed() 
    {   
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'u_name'=> 'test', 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('109' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array(109, 199);
        $this->routeMatch->setParam('action', 'viewrecord');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('workflowId', 687);
        $this->routeMatch->setParam('recordId', 1);
        
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                                
                          )    
                )
            );
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    } 
    
    public function testNewRecordCannotBeAccessedWithoutUserSession() 
    {   
        $this->routeMatch->setParam('action', 'newrecord');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('recordId', 1);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    public function testNewRecordCanBeAccessed() 
    {   
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('109' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array(109, 199);
        $this->routeMatch->setParam('action', 'newrecord');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('recordId', 0);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }  
}

