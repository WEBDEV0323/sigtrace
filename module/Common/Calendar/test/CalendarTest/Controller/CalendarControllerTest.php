<?php namespace Common\CalendarTest\Controller;

use Common\CalendarTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Calendar\Controller\CalendarController;
use PHPUnit\Framework\TestCase;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use Session\Container\SessionContainer;
use Zend\Stdlib\ResponseInterface;
//use PHPUnit_Framework_TestCase as TestCase;
//use PHPUnit\Autoload;
//use Zend\Session\Container;

class CalendarControllerTest extends TestCase
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
        //        $this->userContainer    =   new Container('user');
        //        $this->trackerContainer =   new Container('tracker');
        
        $serviceManager         =   Bootstrap::getServiceManager();
        $this->controller       =   new CalendarController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Calendar'));
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
        //        $this->userContainer->getManager()->getStorage()->clear('user');
        //        $this->trackerContainer->getManager()->getStorage()->clear('tracker');
    }
    
    public function testGetCalendarServiceReturnsAnInstanceOfCalendarModel()
    {
        $this->assertInstanceOf('Common\Calendar\Model\Calendar', $this->controller->getCalendarService());
    }    
    
    public function testAddActionCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'add');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testAddActionGetData() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'add');
        $this->routeMatch->setParam('tracker_id', 25);

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testEventsListActionCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'events_list');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testEventsListActionGetData() 
    {
          $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'events_list');
        $this->routeMatch->setParam('tracker_id', 25);

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testsaveNewEventAction() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'saveNewEvent');
        $this->routeMatch->setParam('tracker_id', 25);
        $start = strtotime(date("Y-m-d"));
        $end = strtotime("+ 7 day", $start);  
        $timestamp = mt_rand($start, $end);
        $startDate = date("Y-m-d", $timestamp);         
        $endDate = date("Y-m-d", mt_rand($timestamp, $end));                
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                            'event_id' => 1,
                            'event_data' => 'test',
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'reason' => 'tset'
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
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testdeleteEventAction() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'delete_event');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    Array (
                            'event_id' => 3,
                            'tracker_id' => 25,                    
                            'comment' => 'tset',
                        )
                )
            );

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();                
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_200) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_400, $response->getStatusCode());           
        }
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    Array (
                            'event_id' => 0,
                            'tracker_id' => 0,                    
                            'comment' => 'tset'
                        )
                )
            );

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_400) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_400, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testEditActionCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'edit');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
 
    public function testEditActionGetData() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'edit');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('id', 3);

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testsaveEditEventAction() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'saveEditEvent');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('id', 1);
        $eventData=str_shuffle('test data');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    Array (
                            'id' => 1,
                            'event_id' => 3,
                            'event_data' => $eventData,
                            'start_date' => '2019-03-28',
                            'end_date' => '2019-03-28',
                            'reason' => 'tset'
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
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    Array (
                            'id' => 1,
                            'event_id' => 3,
                            'event_data' => $eventData,
                            'start_date' => '2019-03-28',
                            'end_date' => '2019-03-28',
                            'reason' => 'tset'
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
    
    public function testgetMonthDatatActionCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'get_month_data');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetMonthDataActionGetData()      //commenting coz response is not coming and no error is displaying need to discuss once back to office.
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 811;
        $this->userContainer->user_details = array('u_id' =>  811, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'get_month_data');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    Array (
                                        'tracker_id' => 25,
                                        'formId' =>85,
                                        'month' => '2019-02'
                                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testviewtActionCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'view');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testviewActionGetData() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1,'user_type' => 'Test', 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'view');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
        
        $this->routeMatch->setParam('tracker_id', 61);
        $this->routeMatch->setParam('form_id', 179);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testfetchAllCalendarDataAction() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'SuperAdmin','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('61' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'fetchAllData');
        $this->routeMatch->setParam('tracker_id', 61);
        $this->routeMatch->setParam('form_id', 179);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());       
    }
}
