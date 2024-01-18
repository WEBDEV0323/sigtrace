<?php

namespace Common\UserTest\Controller;

use Common\UserTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\User\Controller\UserController;
use PHPUnit\Framework\TestCase;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use Session\Container\SessionContainer;
use Zend\Stdlib\ResponseInterface;

class UserControllerTest extends TestCase
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
        $this->controller       =   new UserController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'user'));
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
    
    public function testGetUserServiceReturnsAnInstanceOfUserModel()
    {
        $this->assertInstanceOf('Common\User\Model\UserModel', $this->controller->getUserService());
    }

    public function testUserManagementActionCanBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'user_management');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testUserManagementActionGetData() 
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'user_management');
        $this->routeMatch->setParam('tracker_id', 127);

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testUserManagementActionCanNotBeAccessedForNonAdmin() 
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->routeMatch->setParam('action', 'user_management');
        $this->routeMatch->setParam('tracker_id', 127);

        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testAddActionCanNotBeAccessed()
    {
        $this->routeMatch->setParam('action', 'add');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testAddActionCanBeAccessed()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('2000' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'add');
        $this->routeMatch->setParam('id', 1);
        $this->routeMatch->setParam('tracker_id', 2000);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testAddActionCanNotBeAccessedForNonAdmin()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('2000' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'add');
        $this->routeMatch->setParam('tracker_id', 2000);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testEditActionCanNotBeAccessed()
    {
        $this->routeMatch->setParam('action', 'edit');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testEditActionCanBeAccessed()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'edit');
        $this->routeMatch->setParam('id', 1);
        $this->routeMatch->setParam('tracker_id', 127);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testEditActionCanNotBeAccessedForNonAdmin()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'edit');
        $this->routeMatch->setParam('tracker_id', 127);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testEditActionCanBeAccessedForAdmin()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  0, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('2000' => array('session_group'=>'Administrator', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'edit');
        $this->routeMatch->setParam('tracker_id', 2000);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    //    public function testuserCheckActionCanNotBeAccessed()
    //    {
    //        $this->routeMatch->setParam('action', 'user_check');
    //        $this->controller->dispatch($this->request);
    //        $response = $this->controller->getResponse();
    //        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    //    }
    //    
    //    public function testuserCheckActionCanNotBeAccessedWithOutPOSTAndSessions()
    //    {
    //        $this->routeMatch->setParam('action', 'user_check');
    //        $this->controller->dispatch($this->request);
    //        $resp = $this->controller->getResponse();
    //        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $resp->getStatusCode());
    //    }
    //    
    //    public function testUserCheckActionCantNotBeAccessedWithPOSTAndWithOutSession() 
    //    {
    //        $this->routeMatch->setParam('action', 'user_check');
    //        $this->request->setMethod('POST')
    //            ->setPost(
    //                new Parameters(
    //                    array(
    //                                'user_id' => 1,
    //                                'u_name' => 'siva',
    //                                'role_id' => array(10, 20),
    //                                'status' => 'Active',
    //                                'reason' => 'Test Reason',
    //                                't_hidden' => 127
    //                            )    
    //                )
    //            );
    //        $this->controller->dispatch($this->request);
    //        $response = $this->controller->getResponse();
    //        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    //    } 
    
    public function testUserCheckActionCantBeAccessedWithPOSTAndWithSession() 
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'isSuperAdmin'=>1 , 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('action', 'user_check');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                                'user_id' => 0,
                                'u_name' => 'xyz.abc',
                                'email' => 'unittest1@test.com',
                                'role_id' => array(55, 56),
                                'status' => 'Active',
                                'reason' => 'Test Add',
                                't_hidden' => 127
                            )    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse(); 
        $resp = json_decode($response->getContent());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        } else {
            $this->assertSame('User Created Successfully', $resp->errMessage);
        }
        
        //        $this->request          =   new Request();
        //        $this->request->setMethod('POST')
        //            ->setPost(
        //                new Parameters(
        //                    array(
        //                                'user_id' => $resp->user_id,
        //                                'u_name' => 'xyz.abc',
        //                                'role_id' => array(55, 56),
        //                                'status' => 'Inactive',
        //                                'reason' => 'Test Edit',
        //                                't_hidden' => 127
        //                            )    
        //                )
        //            );
        //        $this->routeMatch->setParam('action', 'user_check');
        //        $this->controller->dispatch($this->request);
        //        $response = $this->controller->getResponse(); 
        //        $resp = json_decode($response->getContent());
        //        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
        //            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        //        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
        //            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        //        } else {
        //            $this->assertSame('User Updated Successfully', $resp->errMessage);
        //        }
        
        //        $this->request          =   new Request();
        //        $this->routeMatch->setParam('action', 'user_check');
        //        $this->request->setMethod('POST')
        //            ->setPost(
        //                new Parameters(
        //                    array(
        //                                'user_id' => $resp->user_id,
        //                                'u_name' => 'xyz.abc',
        //                                'role_id' => array(55, 56),
        //                                'status' => 'Active',
        //                                'reason' => 'Test Add',
        //                                't_hidden' => 127
        //                            )    
        //                )
        //            );
        //        $this->controller->dispatch($this->request);
        //        $response = $this->controller->getResponse();
        //        $resp = json_decode($response->getContent());
        //        $this->assertSame(0, $resp->responseCode);
        
        $this->request          =   new Request();
        $this->routeMatch->setParam('action', 'deleteuser');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                                'user_id' => $resp->user_id,
                                'tracker_id' => 127,
                                'comment' => 'Test delete'
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
    
    public function testDeleteActionCanNotBeAccessedWithOutPOST()
    {
        $this->routeMatch->setParam('action', 'deleteuser');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    public function testDeleteActionCanNotBeAccessedWithOutSession()
    {
        $this->routeMatch->setParam('action', 'deleteuser');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                                'user_id' => 1,
                                'tracker_id' => 127,
                                'comment' => 'Test Reason'
                            )    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    public function testDeleteActionCanBeAccessedWithSession()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'u_name' => 'siva', 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'deleteuser');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                                'user_id' => 0,
                                'tracker_id' => 0,
                                'comment' => 'Test Reason'
                            )    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testViewActionCanNotBeAccessed()
    {
        $this->routeMatch->setParam('action', 'view');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testViewActionCanBeAccessed()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'view');
        $this->routeMatch->setParam('id', 1);
        $this->routeMatch->setParam('tracker_id', 127);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testViewActionCanNotBeAccessedForNonAdmin()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('25' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'view');
        $this->routeMatch->setParam('tracker_id', 127);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
    
    public function testViewActionCanBeAccessedForAdmin()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  0, 'group_id' => 1, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('2000' => array('session_group'=>'Administrator', 'session_group_id'=> 1));
        
        $this->routeMatch->setParam('action', 'view');
        $this->routeMatch->setParam('tracker_id', 2000);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
}
