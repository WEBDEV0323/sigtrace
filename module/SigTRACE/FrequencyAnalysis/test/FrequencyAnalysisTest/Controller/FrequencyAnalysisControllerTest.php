<?php

namespace SigTRACE\FrequencyAnalysisTest\Controller;

use SigTRACE\FrequencyAnalysisTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use SigTRACE\FrequencyAnalysis\Controller\FrequencyAnalysisController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use PHPUnit\Framework\TestCase;
use Session\Container\SessionContainer;


class TriggerControllerTest extends TestCase
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
        $this->controller       =   new FrequencyAnalysisController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'frquency'));
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
    
    public function testgetFrequencyAnalysisModel()
    {
        $this->assertInstanceOf('SigTRACE\FrequencyAnalysis\Model\FrequencyAnalysis', $this->controller->getFrequencyAnalysisModel());
    }
    
    public function testAnalysisActionCannotBeAccessedWithoutUserSession() 
    {   
        $this->routeMatch->setParam('action', 'analysis');
        $this->routeMatch->setParam('trackerId', 109);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    public function testAnalysisActionCanBeAccessedWithUserSession()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('2000' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'analysis');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
}

