<?php 
namespace Common\NotificationTest\Controller;

use Common\NotificationTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Notification\Controller\EmailController;
use PHPUnit\Framework\TestCase;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use Session\Container\SessionContainer;
use Zend\Stdlib\ResponseInterface;

require '../../../../library/Swiftmailer/lib/swift_required.php';

class NotificationControllerTest extends TestCase
{
    protected $controller;
    protected $request;
    protected $response;
    protected $routeMatch;
    protected $event;
    protected $userContainer;
    protected $trackerContainer;
    protected $messageContainer;
    protected $session;
    protected $traceError = true;
    
    public function setUp()
    {
        @session_start();
        parent::setUp();
        $session = new SessionContainer();
        $this->userContainer    =   $session->getSession('user');
        $this->trackerContainer =   $session->getSession('tracker');
        $this->messageContainer =   $session->getSession('message');
 
        $serviceManager         =   Bootstrap::getServiceManager();
        $this->controller       =   new EmailController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Notification'));
        $this->event            =   new MvcEvent();
        $config                 =   $serviceManager->get('Config');
        $routerConfig           =   isset($config['router']) ? $config['router'] : array();
        $router                 =   HttpRouter::factory($routerConfig);
        
        $this->event->setRouter($router);
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);
        $this->controller->setServiceLocator($serviceManager);
        $this->_unitTest = new EmailController();
    } 
    
    public function tearDown()
    {
        $session = new SessionContainer();
        $session->clearSession('user');
        $session->clearSession('tracker');
        $session->clearSession('message');
    }
    
    public function testAddReminderCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'addReminder');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testAddReminderGetData() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'addReminder');
        $this->routeMatch->setParam('tracker_id', 25);

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testAddSubscriptionCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'addSubscription');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testAddSubscriptionGetData() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'addSubscription');
        $this->routeMatch->setParam('tracker_id', 109);

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testAddTemplaterCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'addtemplate');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testAddTemplateGetData() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'addtemplate');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('template_id', 66);

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testIndexCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'index');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testIndexGetData() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'index');
        $this->routeMatch->setParam('tracker_id', 25);

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    function testgetWorkflowList()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'getWorkflowname');                
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                        'form_id' => 85
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
    /**
     * DLR NO: 4967 
     */ 
    function testgetformfieldsList()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'getformfields');                
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                          'tracker_id' => 25,
                          'form_id' => 85
                      )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'getformfields');                
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                          'tracker_id' => 109,
                          'form_id' => 199
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
    function testgetReportsList()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'getReports');                
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                          'tracker_id' => 109,
                          'form_id' => 199
                      )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('109' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'getReports');                
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                          'tracker_id' => 109,
                          'form_id' => 199
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
    
    /**
     * DLR NO: 4967 
     */ 
    // public function testsavetemplateActionGetData() 
    // {
    //     $_SERVER['SERVER_NAME']='localhost';
    //     $this->routeMatch->setParam('action', 'savetemplate');
    //     $this->routeMatch->setParam('tracker_id', 25);
    //     $this->controller->dispatch($this->request);
    //     $response = $this->controller->getResponse();
    //     $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        
    //     $this->userContainer->u_id = 1;
    //     $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
    //     $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
    //     $this->messageContainer->message=array('');
    //     $this->routeMatch->setParam('action', 'savetemplate');
    //     $this->routeMatch->setParam('tracker_id', 25);
    //     $start = strtotime(date("Y-m-d"));
    //     $end = strtotime("+ 7 day", $start);  
    //     $timestamp = mt_rand($start, $end);
    //     $startDate = date("Y-m-d", $timestamp);         
    //     $endDate = date("Y-m-d", mt_rand($timestamp, $end));                
    //     $this->request->setMethod('POST')
    //         ->setPost(
    //             new Parameters( 
    //                 Array (
    //                         'status' => 1,                            
    //                         'template_name' => 'Notification Unit Test1',
    //                         'subject' => 'Notification Unit Test Subject',
    //                         'msg' => 'Notification Unit Test Content',
    //                         'n_cond' => 'AND',
    //                         'form_id' => 85,
    //                         'ccmail' => 'manjushree.rp@bioclinica.com',
    //                         'field_id' => array(10, 12, 15),
    //                         'notification_template_status' => 'Active',
    //                         'mtype' => 'Notification',
    //                         'comment' => 'Comment Description',
    //                         't_name' => 'Tracker test',
    //                         'template_id' => 0,
    //                         'tracker_id' => 25,
    //                         'condition_on_field' => array('capaid'),
    //                         'condition_operand' => array('>'),
    //                         'value' => array(1)
    //                     )
    //             )
    //         );
    //     $this->controller->dispatch($this->request);
    //     $response = $this->controller->getResponse();
    //     if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
    //         $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    //     } else {
    //         $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
    //     }
    //     $this->controller->dispatch($this->request);
    //     $response = $this->controller->getResponse();
    //     if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
    //         $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    //     } else {
    //         $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
    //     }
    //     $this->request->setMethod('POST')
    //         ->setPost(
    //             new Parameters( 
    //                 Array (
    //                         'status' => 1,                            
    //                         'template_name' => 'Notification Unit Test3',
    //                         'subject' => 'Notification Unit Test Subject',
    //                         'msg' => 'Notification Unit Test Content',
    //                         'n_cond' => 'AND',
    //                         'form_id' => 85,
    //                         'ccmail' => 'manjushree.rp@bioclinica.com',
    //                         'field_id' => array(10, 12, 15),
    //                         'notification_template_status' => 'Active',
    //                         'mtype' => 'Notification',
    //                         'comment' => 'Comment Description',
    //                         't_name' => 'Tracker test',
    //                         'template_id' => 66,
    //                         'tracker_id' => 25,
    //                         'condition_on_field' => array('capaid'),
    //                         'condition_operand' => array('>'),
    //                         'value' => array(1)
    //                     )
    //             ) 
    //         );
    //     $this->controller->dispatch($this->request);
    //     $response = $this->controller->getResponse();

    //     if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
    //         $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    //     } else {
    //         $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
    //     }
    //     $this->controller->dispatch($this->request);
    //     $response = $this->controller->getResponse();
    //     if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
    //         $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    //     } else {
    //         $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
    //     }
    // }
    
    public function testsavesubscriptionAction() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->messageContainer=array('');
        $this->routeMatch->setParam('action', 'savesubscription');
        $this->routeMatch->setParam('tracker_id', 25);             
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (     
                            'template_id' => 0,
                            'tracker_id' => 25,
                            'template_name' => 'Subscription Unit Test',
                            'form_id' => 199,
                            'subject' => 'Subscription Unit Test Subject',
                            'msg' => 'Subscription Unit Test Content',
                            'report_name' => 'List_All',
                            'frequency' => 'Daily',
                            'frequency_value' => 'daily',
                            'field_id' => array(10, 12, 15),
                            'ccmail' => ''
                         
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
    
    /**
     * DLR NO: 4967 
     */ 
    // public function testsavereminderAction() 
    // {
    //     $_SERVER['SERVER_NAME']='localhost';
    //     $this->userContainer->u_id = 1;
    //     $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
    //     $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
    //     $this->messageContainer=array('');
    //     $this->routeMatch->setParam('action', 'savereminder');
    //     $this->routeMatch->setParam('tracker_id', 25);
    //     $start = strtotime(date("Y-m-d"));
    //     $end = strtotime("+ 7 day", $start);  
    //     $timestamp = mt_rand($start, $end);
    //     $startDate = date("Y-m-d", $timestamp);         
    //     $endDate = date("Y-m-d", mt_rand($timestamp, $end));                
    //     $this->request->setMethod('POST')
    //         ->setPost(
    //             new Parameters( 
    //                 Array (     
    //                         'template_id' => 0,
    //                         'tracker_id' => 25,                       
    //                         'template_name' => 'Reminder Unit Test',
    //                         'form_id' => 85,                            
    //                         'subject' => 'Reminder Unit Test Subject',
    //                         'msg' => 'Reminder Unit Test Content',
    //                         'field_id' => array(10, 12, 15),
    //                         'days' => 1,
    //                         'beforeAfter' => 'before',
    //                         'dateFields' => 'inputdeadlinedate',
    //                         'n_cond' => 'AND',                            
    //                         'condition_on_field' => array('capaid'),
    //                         'condition_operand' => array('>'),
    //                         'value' => array(1),
    //                         'ccmail' =>''
    //                     )
    //             )
    //         );
    //     $this->controller->dispatch($this->request);
    //     $response = $this->controller->getResponse();
        
    //     if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
    //         $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    //     } else {
    //         $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
    //     }
    //             $this->controller->dispatch($this->request);
    //             $response = $this->controller->getResponse();
    //     if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
    //         $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
    //     } else {
    //         $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
    //     }
    // }
    
    /**
     * DLR NO: 4967 
     */ 
    function testsavelog()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('param1', 66);    
        $this->routeMatch->setParam('param2', 'form_' . 25 . '_' . 85);
        $this->routeMatch->setParam('param3', 4);
        $this->routeMatch->setParam('action', 'sendemail');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                            'form_id' => 85
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
   
    function testdeleteCondition()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'deleteCondition');  
        $this->routeMatch->setParam('tracker_id', 25);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (     
                            'id'=>847
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
    
    function testpopulatefields()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'populatefields');                
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                          'tracker_id' => 25,
                          'form_id' => 85
                      )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'populatefields');    
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (     
                            'tracker_id' => 25,                       
                            'form_id' => 85,
                            'template_id' => 66
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
    
    function testdeletetemplate()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'deletetemplate');   
        $this->routeMatch->setParam('template_id', 131);
        $this->routeMatch->setParam('tracker_id', 25);             
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (
                          'template_id' => 131,
                          'comment' => 'unit test delete'
                      )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'deletetemplate');   
        $this->routeMatch->setParam('template_id', 131);
        $this->routeMatch->setParam('tracker_id', 25);             
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (     
                            'template_id' => 131,
                           'comment' => 'unit test delete'
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
    
    function testgetWorkflowandfields()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'getWorkflowandfields');                
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (                          
                          'form_id' => 85
                      )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'getWorkflowandfields');    
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (     
                            'form_id' => 85
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
    
    function testgetWorkflowFields()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'getWorkflowFields');                
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (                          
                          'form_id' => 85,
                          'workflowName' =>'Triage' 
                      )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'getWorkflowFields');    
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters( 
                    Array (     
                            'form_id' => 85,
                            'workflowName' =>'Triage' 
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

    public function testSendMailCronJobActionCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'sendMailCronJob');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }

    public function testSendMailCronJob1ActionCantBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'sendMailCronJob1');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    //    public function testCheckSubscriptionActionCanBeAccessed()
    //    {
    //        $this->routeMatch->setParam('action', 'checkSubscription');
    //        $this->controller->dispatch($this->request);
    //        $response = $this->controller->getResponse();
    //        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    //    }
}
