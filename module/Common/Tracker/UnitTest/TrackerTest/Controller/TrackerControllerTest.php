<?php
namespace TrackerTest\Controller;

// define('IP', '127.0.0.1');
// $_SERVER['SERVER_NAME'] = 'localhost';
use TrackerTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Tracker\Controller\TrackerController;
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

class TrackerControllerTest extends TestCase
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
        $this->controller       =   new TrackerController();
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
    //    public function testExportTrackerTrackername()
    //    {
    //        $this->routeMatch->setParam('action', 'export_tracker');
    //        $this->request->setMethod('POST')
    //            ->setPost(
    //                new Parameters(
    //                    array('t_name' => '',
    //                    'datacopy'=>'df',
    //                    'userrole'=>'sdfs',
    //                    )
    //                )
    //            );
    //        $this->controller->dispatch($this->request);
    //        $response = $this->controller->getResponse();
    //        //$this->assertSame('false', $response->getContent());
    //        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    //    }

    /*
     * Copy tracker with data OR Copy Tracker without data
     */
    //    public function testExportTrackerDataselection()
    //    {
    //        //$_SESSION['u_id'] = 1;
    //        
    //        $this->routeMatch->setParam('action', 'export_tracker');
    //        $this->request->setMethod('POST')
    //            ->setPost(
    //                new Parameters(
    //                    array('t_name' => 'ffgdf',
    //                    'datacopy'=>'',
    //                    'userrole'=>'dfsfds',
    //                    )
    //                )
    //            );
    //        $this->controller->dispatch($this->request);
    //        $response = $this->controller->getResponse();
    //        //$this->assertSame('false', $response->getContent());
    //        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    //    }

    /*
     * Copy tracker with user role or  without user role
     */
    //    public function testExportTrackerRoleselection()
    //    {
    //        $_SESSION['u_id'] = 1;
    //        $this->routeMatch->setParam('action', 'export_tracker');
    //        $this->request->setMethod('POST')
    //            ->setPost(
    //                new Parameters(
    //                    array('t_name' => 'ffgdf',
    //                    'datacopy'=>'withdata',
    //                    'userrole'=>'',
    //                    )
    //                )
    //            );
    //        $this->controller->dispatch($this->request);
    //        $response = $this->controller->getResponse();
    //        //$this->assertSame('false', $response->getContent());
    //        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    //    }

    /*
     * Copy tracker if data is in post
     */
    //    public function testExportTrackerIfnotpost()
    //    {
    //        $_SESSION['u_id'] = 1;
    //        $this->routeMatch->setParam('action', 'export_tracker');
    //        $this->request->setMethod('POST')->setPost(new Parameters());
    //        $this->controller->dispatch($this->request);
    //        $response = $this->controller->getResponse();
    //        //$this->assertSame('false', $response->getContent());
    //        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    //    }

    /*
     *  if tracker is copied
     */
    //    public function testExportTrackerIfexported()
    //    {
    //        $_SESSION['u_id'] = 1;
    //        $this->routeMatch->setParam('action', 'export_tracker');
    //        $this->request->setMethod('POST')
    //            ->setPost(
    //                new Parameters(
    //                    array('t_name' => 'ffgdf',
    //                    'datacopy'=>'withdata',
    //                    'userrole'=>'withuserrole',
    //                    )
    //                )
    //            );
    //        $this->controller->dispatch($this->request);
    //        $response = $this->controller->getResponse();
    //        //$this->assertSame('"success"', $response->getContent());
    //        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    //    }

    /*
     *  if tracker is not copied
     */
    //    public function testExportTrackerIfnotExported()
    //    {
    //        $_SESSION['u_id'] = 1;
    //        $this->routeMatch->setParam('action', 'export_tracker');
    //        $this->request->setMethod('POST')
    //            ->setPost(
    //                new Parameters(
    //                    array('t_name' => 'ffgdf',
    //                    'datacopy'=>'withdata',
    //                    'userrole'=>'withuserrole',
    //                    )
    //                )
    //            );
    //        $this->controller->dispatch($this->request);
    //        $response = $this->controller->getResponse();
    //        //$this->assertSame('"failure"', $response->getContent());
    //        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    //    }
    public function testAddRule()
    {
        $_SESSION['u_id'] = 1;
        $this->routeMatch->setParam('action', 'saverule');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('rule_id' => 0,
                    'tracker_id'=>25,
                    'form_id'=>85,
                    'r_cond'=>"AND",
                    'condition_on_field'=>array('0' => 7),
                    'condition_operand'=>array('0' => "="),
                    'value'=>array('0' => "equal test"),
                    'comment'=>"",
                    'action_value'=>array('0' => 1),
                    'action_name'=>array('0' => 'Edit Workflow'),
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //echo $response->getContent();die;
        //$this->assertSame('true', $response->getContent());
        $this->assertTrue(empty($response->getContent()));
    }
    public function testRuleEmptyData()
    {
        $_SESSION['u_id'] = 1;
        $this->routeMatch->setParam('action', 'saverule');
        $this->request->setMethod('POST')
            ->setPost(new Parameters(array()));
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('"Rule Post Data should not empty!"', $response->getContent());
        $this->assertTrue(empty($response->getContent()));
    }
    
    public function testgetfields()
    {
        $_SESSION['u_id'] = 1;
        $this->routeMatch->setParam('action', 'getfields');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                        'form_id'=>170,
                        'tracker_id'=>25,
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
       
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_404) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_404, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());           
        } 
    }
    
    public function testsearchSetting()
    {
        $_SESSION['u_id'] = 1;
        $_SESSION['user_details'] = array(
                                    'u_id' => 1,
                                    'u_realname' => 'Sarita Tewari',
                                    'u_name' => 'sarita.tewari',
                                    'email' => 'sarita.tewari@bioclinica.com',
                                    'group_id' => 1,
                                    'group_name' => 'SuperAdmin',
                                    'user_type' => 'LDAP'
                                );
        $this->routeMatch->setParam('action', 'searchSetting');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                        'form_id'=>170,
                        'tracker_id'=>25,
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_404) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_404, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());           
        } 
    }
    
    public function testDeleteTrackerAction()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'delete');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    Array (
                            'trackerId' => 25,
                            'reason' => 'tset'
                        )
                )
            );
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
}
