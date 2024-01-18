<?php
namespace ApplicationTest\Controller;

use ApplicationTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Application\Controller\IndexController;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Stdlib\Parameters;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Session\Container;
use PHPUnit\Framework\TestCase as TestCase;

class IndexControllerTest extends TestCase
{
    protected $controller;
    protected $request;
    protected $response;
    protected $routeMatch;
    protected $event;
    protected $userContainer;
    
    protected $traceError = true;
    
    protected function setUp()
    {
        $this->userContainer    =   new Container('user');
        $serviceManager = Bootstrap::getServiceManager();
        $this->controller = new IndexController();
        $this->request    = new Request();
        $this->routeMatch = new RouteMatch(array('controller' => 'Index'));
        $this->event      = new MvcEvent();
        $config = $serviceManager->get('Config');
        $routerConfig = isset($config['router']) ? $config['router'] : array();
        $router = HttpRouter::factory($routerConfig);

        $this->event->setRouter($router);
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);
        $this->controller->setServiceLocator($serviceManager);
    }

    /*
     * check  new and last value should not be same
     */
    public function tearDown()
    {
        $this->userContainer->getManager()->getStorage()->clear('user');        
    }
    
    public function testgetAdminServiceReturnsAnInstanceOfAdminMapper()
    {
        echo 'ferwf';
        $this->assertInstanceOf('Application\Model\AdminMapper', $this->controller->getAdminService());
    }
    public function testIndexAction() 
    {
        $this->userContainer->u_id = 1;
        $this->routeMatch->setParam('action', 'index');
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testsaveToLogFile() 
    {
        //$this->routeMatch->setParam('action', 'saveToLogFile');
        //$this->controller->dispatch($this->request);
        //$response = $this->controller->getResponse();
        //$this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
        $this->assertTrue(method_exists($this->controller, 'saveToLogFile'), 'Class does not have method myFunction');
    }
     
}


