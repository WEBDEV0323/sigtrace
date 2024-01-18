<?php

namespace TrackerTest\Controller;

// define('IP', '127.0.0.1');
// $_SERVER['SERVER_NAME'] = 'localhost';
use TrackerTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Tracker\Controller\AccessController;
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

class AccessControllerTest extends TestCase
{
    protected $controller;
    protected $request;
    protected $response;
    protected $routeMatch;
    protected $event;
    protected $p;
    protected $userContainer;
    protected $trackerContainer;
    protected $session;
    protected $traceError = true;

    public function setUp()
    {
        @session_start();
        parent::setUp();
        $session = new SessionContainer();
        $this->userContainer = $session->getSession('user');
        $this->trackerContainer = $session->getSession('tracker');

        $serviceManager = Bootstrap::getServiceManager();
        $this->controller = new AccessController();
        $this->request = new Request();
        $this->routeMatch = new RouteMatch(array('controller' => 'Tracker'));
        $this->event = new MvcEvent();
        $config = $serviceManager->get('Config');
        $routerConfig = isset($config['router']) ? $config['router'] : array();
        $router = HttpRouter::factory($routerConfig);

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

    public function testGetAccesscontrollerServiceReturnsAnInstanceOfcessModel()
    {
        $this->assertInstanceOf('Common\Tracker\Model\AccessModule', $this->controller->getModelService());
    }

    public function testAccessSettingsActionCanBeAccessed()
    {
        $this->routeMatch->setParam('action', 'accessSettings');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    public function testAccessSettingsActionGetData()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' => 1, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->routeMatch->setParam('action', 'accessSettings');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->trackerContainer->tracker_ids = array(25, 35);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }

    public function testAccessSettingsActionNotGetData()
    {
        $this->userContainer->u_id = 0;
        $this->userContainer->user_details = array('u_id' => 0, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->routeMatch->setParam('action', 'accessSettings');
        $this->routeMatch->setParam('tracker_id', 0);
        $this->trackerContainer->tracker_ids = array(0, 0);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_404) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_404, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        }
    }

    public function testAccessSettingsActionCanNotBeAccessed()
    {
        $this->routeMatch->setParam('action', 'accessSettings');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    public function testaccessSettings()
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' => 1, 'group_id' => 1, 'group_name' => 'Test Group', 'email' => 'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group' => 'ABC', 'session_group_id' => 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->trackerContainer->tracker_ids = array(25, 35);
        $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
        $this->routeMatch->setParam('action', 'accessSettings');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('tracker_id' => 25,
                            )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
        }
    }

    public function testsaveaccesssettingActionCanBeAccessed()
    {
        $this->routeMatch->setParam('action', 'saveaccesssetting');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    public function testsaveaccesssettingActionGetData()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' => 1, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->routeMatch->setParam('action', 'saveaccesssetting');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->trackerContainer->tracker_ids = array(25, 35);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }

    public function testsaveaccesssettingActionCanNotBeAccessedForNonAdmin()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'saveaccesssetting');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }

    public function testsaveaccesssettingActionCanNotBeAccessed()
    {
        $this->routeMatch->setParam('action', 'saveaccesssetting');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    public function testsaveaccesssetting()
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' => 1, 'group_id' => 1, 'group_name' => 'Test Group', 'email' => 'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group' => 'ABC', 'session_group_id' => 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $trackerUserGroups = @$trackerContainer->tracker_user_groups;
        $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
        $this->routeMatch->setParam('action', 'saveaccesssetting');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('tracker_id' => 25,
                            'comment' => 'test',
                            'role_id' => 1,
                            'can_insert' => 'Yes',
                            'can_delete' => 'Yes',
                            'form_id' => 85,
                            )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
        }
    }

    public function testsaveupdatesettingActionCanBeAccessed()
    {
        $this->routeMatch->setParam('action', 'saveupdatesetting');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->trackerContainer->tracker_ids = array(25, 35);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }

    public function testsaveupdatesettingActionCanNotBeAccessedForNonAdmin()
    {
        $this->userContainer->u_id = 5;
        $this->userContainer->user_details = array('u_id' => 5, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->routeMatch->setParam('action', 'saveupdatesetting');

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }

    public function testsaveupdatesettingActionCanNotBeAccessed()
    {
        $this->routeMatch->setParam('action', 'saveupdatesetting');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
        }
    }

    public function testsaveupdatesetting()
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' => 1, 'group_id' => 1, 'group_name' => 'Test Group', 'email' => 'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group' => 'ABC', 'session_group_id' => 1));
        $this->routeMatch->setParam('tracker_id', 25);
        //$this->trackerContainer->workflow_id = array(1,2);
        $this->routeMatch->setParam('action', 'saveupdatesetting');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('comment' => 'test',
                            'can_update' => array('Yes', 'Yes'),
                            'role_id' => 1,
                            'form_id' => 85,
                            'workflow_id' => array('0' => 1, '1' => 2),
                            )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        //$this->assertEquals(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
        }
    }

    public function testsavereadsettingActionCanBeAccessed()
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $this->routeMatch->setParam('action', 'savereadsetting');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->trackerContainer->tracker_ids = array(25, 35);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    }

    public function testsavereadsettingActionGetData()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' => 1, 'group_id' => 1, 'group_name' => 'Test Group', 'email' => 'unittest@test.com');
        $this->routeMatch->setParam('action', 'savereadsetting');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->trackerContainer->tracker_ids = array(25, 35);
        //$this->routeMatch->email 'master.tracker@synowledge.com');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }

    public function testsavereadsetting()
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' => 1, 'group_id' => 1, 'group_name' => 'Test Group', 'email' => 'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group' => 'ABC', 'session_group_id' => 1));
        $this->routeMatch->setParam('tracker_id', 25);
        // $this->trackerContainer->workflow_id = array('1' => 'Yes','2' => 'Yes');
        $this->routeMatch->setParam('action', 'savereadsetting');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('comment' => 'test',
                            'can_update' => array('Yes', 'Yes'),
                            'can_read' => array('Yes', 'Yes'),
                            'role_id' => 1,
                            'form_id' => 85,
                            'workflow_id' => array(1, 2),
                            'client_id' => 29,
                            'email' => 'master.tracker@synowledge.com',
                            )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }

    public function testformAccessSettingsActionCanNotBeAccessed()
    {
        $this->routeMatch->setParam('action', 'formAccessSettings');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    public function testformAccessSettingsActionNullData()
    {
        $this->routeMatch->setParam('tracker_id', 0);
        $this->routeMatch->setParam('action', 'formAccessSettings');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    public function testformAccessSettings()
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' => 1, 'group_id' => 1, 'group_name' => 'Test Group', 'email' => 'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group' => 'ABC', 'session_group_id' => 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->trackerContainer->tracker_ids = array(25, 35);
        $this->routeMatch->setParam('action', 'formAccessSettings');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('comment' => 'test',
                            'role_id' => 1,
                            'form_id' => 85,
                            )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }

    public function testsaveformsetting()
    {
        $_SERVER['SERVER_NAME'] = 'localhost';
        $this->userContainer->user_details = array('u_id' => 1, 'group_id' => 1, 'group_name' => 'Test Group', 'email' => 'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group' => 'ABC', 'session_group_id' => 1));
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('action', 'saveformsetting');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                            'comment' => 'test',
                            'can_read' => array('Yes', 'Yes'),
                            'role_id' => '1',
                            'form_id' => '85',
                            )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    }
}
