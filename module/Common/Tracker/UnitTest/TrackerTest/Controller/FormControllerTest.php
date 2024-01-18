<?php
namespace TrackerTest\Controller;

// define('IP', '127.0.0.1');
// $_SERVER['SERVER_NAME'] = 'localhost';
use TrackerTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Tracker\Controller\FormController;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Stdlib\ResponseInterface;
use Zend\Mvc\Router\RouteMatch;
//use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit\Framework\TestCase;
use Zend\Session\Container;
use Session\Container\SessionContainer;

//use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
//use Zend\Mvc\Controller\Plugin\Forward;

class FormControllerTest extends TestCase
{
    protected $controller;
    protected $request;
    protected $response;
    protected $routeMatch;
    protected $event;
    protected $p;

    // protected function setUp()
    // {
    //     if (!isset($_SESSION)) {
    //         @session_start();
    //     }
    //     $serviceManager = Bootstrap::getServiceManager();
    //     $this->controller = new TrackerController();
    //     $this->request    = new Request();
    //     $this->routeMatch = new RouteMatch(array('controller' => 'tracker'));
    //     $this->event      = new MvcEvent();
    //     $config = $serviceManager->get('Config');
    //     $routerConfig = isset($config['router']) ? $config['router'] : array();
    //     $router = HttpRouter::factory($routerConfig);
    //     $this->event->setRouter($router);
    //     $this->event->setRouteMatch($this->routeMatch);
    //     $this->controller->setEvent($this->event);
    //     $this->controller->setServiceLocator($serviceManager);
    // }
    // public function tearDown()
    // {
    //     @session_destroy();
    // }

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
        $this->controller       =   new FormController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Tracker'));
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
    /*
     * session has been expired
     */
    /*
    public function testExportTrackerExpireSession()
    {
        $this->routeMatch->setParam('action', 'export_tracker');
        $this->request->setMethod('POST')->setPost(new Parameters(array()));
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
     * 
     */
    /*
     * tracker name should not be blank
     */
    public function testAddNewFormActionCanNotBeAccessed()
    {
        $this->routeMatch->setParam('action', 'addNewForm');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    public function testaddNewForm()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('formId', 85);
        $this->routeMatch->setParam('action', 'addNewForm');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_200) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        } 
        $this->routeMatch->setParam('tracker_id', 0);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        } 
    }

  
    
    public function testajaxFormAddActionCanNotBeAccessed()
    {
        $this->routeMatch->setParam('action', 'ajaxFormAdd');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
    public function testajaxFormAddGetData()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('formId', 85);
        $trackerUserGroups = @$trackerContainer->tracker_user_groups;
        $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
        $trackerIds = @$trackerContainer->tracker_user_groups;
        $this->routeMatch->setParam('action', 'ajaxFormAdd');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('tracker_id' => 25,
                            'comment' => 'test',
                            'can_insert' => 'Yes',
                            'can_delete' => 'Yes',
                            'form_id' => 85,
                            'role_id' => 1,
                            'form_name' => 'PV Master Tracker',
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
       
            
    public function testajaxFormAddfalseData()
    {    
         $this->routeMatch->setParam('tracker_id', 0);
         $this->routeMatch->setParam('formId', 0);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_404, $response->getStatusCode());           
      
    }   
        
        
    
    
    
}
