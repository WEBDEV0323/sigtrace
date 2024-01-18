<?php

namespace SigTRACE\ProductTest\Controller;

use ProductTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use SigTRACE\Product\Controller\ActiveSubstanceController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use PHPUnit\Framework\TestCase;
use Zend\Session\Container;
use Session\Container\SessionContainer;
use Common\Role\Controller\RoleController;

class ActiveSubstanceControllerTest extends TestCase
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
        $this->controller       =   new ActiveSubstanceController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'ActiveSubstance'));
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
    
    public function testGetServiceReturnsAnInstanceOfActiveSubstanceModel()
    {
        $this->assertInstanceOf('SigTRACE\Product\Model\ActiveSubstance', $this->controller->getActiveSubstanceService());
    }

    public function testgetActivesubstanceManagementCanBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'activesubstanceManagement');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetAddCanBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'add');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetEditCanBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'edit');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetActiveSubstanceCheckCheckForEmptyPost() 
    {
        $this->routeMatch->setParam('action', 'activeSubstanceCheck');
        $this->request->setMethod('POST')->setPost(new Parameters(array()));
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetActiveSubstanceCheckCheckForPostData() 
    {
        $this->routeMatch->setParam('action', 'activeSubstanceCheck');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                        'actSubName' => 'Test Active Substance',
                        'productIds' => array(0 => 1),
                        'archived' => 0,
                        'trackerId' => 109,
                        'reason' => 'test'
                    )
                )
            );
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }    
    
    public function testgetSaveEditActiveSubstanceCheckForEmptyPost() 
    {
        $this->routeMatch->setParam('action', 'saveEditActiveSubstance');
        $this->request->setMethod('POST')->setPost(new Parameters(array()));
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetSaveEditActiveSubstanceCheckForPostData() 
    {
        $this->routeMatch->setParam('action', 'saveEditActiveSubstance');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                        'actSubName' => 'Test Edit Active Substance',
                        'activeSubstancesId' => 1,
                        'productIds' => array(0 => 1),
                        'archived' => 0,
                        'trackerId' => 109,
                        'reason' => 'test'
                    )
                )
            );
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }    
    
    public function testgetDeleteActiveSubstanceCheckForEmptyPost() 
    {
        $this->routeMatch->setParam('action', 'deleteActiveSubstance');
        $this->request->setMethod('POST')->setPost(new Parameters(array()));
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetDeleteActiveSubstanceCheckForPostData() 
    {
        $this->routeMatch->setParam('action', 'deleteActiveSubstance');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                        'substance_id' => 1,
                        'tracker_id' => 109,
                        'form_id' => 199,
                        'substance_name' => 'test',
                        'comment' => 'test'
                    )
                )
            );
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
}
