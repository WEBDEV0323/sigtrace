<?php

namespace Common\SettingsTest\Controller;

use SettingsTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Settings\Controller\SettingsController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use PHPUnit\Framework\TestCase;
use Zend\Session\Container;
use Session\Container\SessionContainer;


class SettingsControllerTest extends TestCase
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
        $this->controller       =   new SettingsController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Settings'));
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
    
    public function testgetModelService()
    {
        $this->assertInstanceOf('Common\Settings\Model\SettingsModel', $this->controller->getModelService());
    }

    public function testWorkflowRuleSettingsCannotBeAccessedWithOutUserSession() 
    {   
        $this->routeMatch->setParam('action', 'workflowRuleSettings');
        $this->routeMatch->setParam('trackerId', 109);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testdeleteRuleNoPostData()
    {
        $this->routeMatch->setParam('action', 'deleteRule');
        $this->routeMatch->setParam('tracker_id', 109);
        $this->routeMatch->setParam('action_id', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
    public function testdeleteRuleNoUserData()
    {
        $this->routeMatch->setParam('action', 'deleteRule');
        $this->routeMatch->setParam('tracker_id', 109);
        $this->routeMatch->setParam('action_id', 199);
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
        $this->routeMatch->setParam('tracker_id', 109);
        $this->routeMatch->setParam('action_id', 199);
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
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_200) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        }
    }
    
    public function testgetWorkflowsAndFieldsNoUserData()
    {
        $this->routeMatch->setParam('action', 'getWorkflowsAndFields');
        $this->routeMatch->setParam('tracker_id', 109);
        $this->routeMatch->setParam('action_id', 199);
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
        $this->routeMatch->setParam('tracker_id', 109);
        $this->routeMatch->setParam('action_id', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
    public function testgetWorkflowsAndFields()
    {
        $this->routeMatch->setParam('action', 'getWorkflowsAndFields');
        $this->routeMatch->setParam('tracker_id', 109);
        $this->routeMatch->setParam('action_id', 199);
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
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_200) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        }
    }
    
    public function testsaveRuleNoUserData()
    {
        $this->routeMatch->setParam('action', 'saveRule');
        $this->routeMatch->setParam('tracker_id', 109);
        $this->routeMatch->setParam('action_id', 199);
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
        $this->routeMatch->setParam('tracker_id', 109);
        $this->routeMatch->setParam('action_id', 199);
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
    public function testsaveRule()
    {
        $this->routeMatch->setParam('action', 'saveRule');
        $this->routeMatch->setParam('tracker_id', 109);
        $this->routeMatch->setParam('action_id', 199);
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
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_200) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        }
    }
    
    public function testgetRuleInfo()
    {
        $this->routeMatch->setParam('action', 'getRuleInfo');
        $this->routeMatch->setParam('tracker_id', 109);
        $this->routeMatch->setParam('action_id', 199);
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
        $this->routeMatch->setParam('tracker_id', 109);
        $this->routeMatch->setParam('action_id', 199);
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
        $this->routeMatch->setParam('tracker_id', 109);
        $this->routeMatch->setParam('action_id', 199);
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
}

