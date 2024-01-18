<?php

namespace Common\ImportTest\Controller;

use Common\ImportTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Import\Controller\ImportController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
//use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit\Framework\TestCase;
use Zend\Session\Container;
use Session\Container\SessionContainer;
use Common\Role\Controller\RoleController;

class ImportControllerTest extends TestCase
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
        $this->controller       =   new ImportController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Import'));
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
    
    public function testGetImportServiceReturnsAnInstanceOfImportModel()
    {
        $this->assertInstanceOf('Import\Model\Import', $this->controller->getImportService());
    }

    public function testGetServiceReturnsAnInstanceOfImportService()
    {
        $this->assertInstanceOf('Import\Service\ImportService', $this->controller->getService());
    }
    
    public function testgetFileFromClientCanBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'getFileFromClient');

        $this->routeMatch->setParam('tracker_id', 25);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
    //    public function testgetFileFromClientCanRestrictDuplicates() 
    //    {
    //        $this->routeMatch->setParam('action', 'getFileFromClient');
    //        $this->request->setMethod('POST')
    //            ->setPost(
    //                new Parameters(
    //                    array()    
    //                )
    //            );
    //        $this->routeMatch->setParam('tracker_id', 25);
    //        $this->controller->dispatch($this->request);
    //        $response = $this->controller->getResponse();
    //        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    //    }
    
    
}
