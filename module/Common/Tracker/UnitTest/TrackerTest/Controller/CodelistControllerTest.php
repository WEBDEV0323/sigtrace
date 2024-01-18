<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace TrackerTest\Controller;

use TrackerTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Tracker\Controller\CodelistController;
use PHPUnit\Framework\TestCase;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use Session\Container\SessionContainer;
use Zend\Stdlib\ResponseInterface;

class CodelistControllerTest extends TestCase
{
    protected $controller;
    protected $request;
    protected $response;
    protected $routeMatch;
    protected $event;
    protected $userContainer;
    protected $trackerContainer;
    protected $traceError = true;
    
    public function setUp()
    {
        @session_start();
        parent::setUp();
        $session = new SessionContainer();
        $this->userContainer    =   $session->getSession('user');
        $this->trackerContainer =   $session->getSession('tracker');
        
        $serviceManager         =   Bootstrap::getServiceManager();
        $this->controller       =   new CodelistController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Codelist'));
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
    public function testGetCodelistServiceReturnsAnInstanceOfCodelistModel()
    {
        $this->assertInstanceOf('Common\Tracker\Model\Codelist', $this->controller->getModelService());
    }
    //    public function testGetAccessServiceReturnsAnInstanceOfAccessModel()
    //    {
    //        $this->assertInstanceOf('Common\Tracker\Model\AccessModule', $this->controller->getAccessModelService());
    //    }
    public function testCodelistManagementActionCanBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'codelist_management');
        $this->routeMatch->setParam('tracker_id', 25);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testCodelistManagementActionGetData() 
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'SuperAdmin');
        $this->routeMatch->setParam('action', 'codelist_management');
        $this->routeMatch->setParam('tracker_id', 25);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }    
    
    public function testCodelistManagementActionCanNotBeAccessedForNonAdmin() 
    {
        $this->userContainer->u_id = 5;
        $this->userContainer->user_details = array('u_id' =>  5, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->routeMatch->setParam('action', 'codelist_management');
        $this->routeMatch->setParam('tracker_id', 25);
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());           
        }
    }
    public function testDeleteCodelistActionCanNotBeAccessedWithOutPOST()
    {
        $this->routeMatch->setParam('action', 'delete_codelist');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $content = json_decode($response->getContent());
        $this->assertSame(0, $content->responseCode);
    }
    public function testDeleteCodelistActionCannotBeAccessed() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'delete_codelist');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('codeListID' => 1, 'reason' => 'Test Reason')    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    public function testDeleteActionCustomerWithPOST()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'delete_codelist');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'email' => 'test@test.com', 'u_name' => 'siva', 'group_id' => 1, 'group_name' => 'SuperAdmin');
        
        $this->routeMatch->setParam('tracker_id', 25);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('codeListID' => 0, 'reason' => 'Test Reason')    
                )
            );
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $content = json_decode($response->getContent());
        if ($content->responseCode == 0) {
            $this->assertSame(0, $content->responseCode);
        } else {
            $this->assertSame(1, $content->responseCode);    
        }        
    }
    public function testEditCodelistActionCanNotBeAccessedWithOutPOST()
    {
        $this->routeMatch->setParam('action', 'edit_codelist');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $content = json_decode($response->getContent());
        $this->assertSame(0, $content->responseCode);
    }
    public function testEditCodelistActionCannotBeAccessed() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'edit_codelist');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('editCodeListId' => 1, 'reason' => 'Test Reason')    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        }
    }
    public function testEditCodelistActionCustomerWithPOST()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'edit_codelist');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'email' => 'test@test.com', 'u_name' => 'siva', 'group_id' => 1, 'group_name' => 'SuperAdmin');
        
        $this->routeMatch->setParam('tracker_id', 25);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('editCodeListId' => 1, 'editCodeList' => 'yes_no', 'trackerId' => 25, 'reason' => 'Test Reason')    
                )
            );
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $content = json_decode($response->getContent());
        if ($content->responseCode == 0) {
            $this->assertSame(0, $content->responseCode);
        } else {
            $this->assertSame(1, $content->responseCode);    
        }        
    }
    public function testAddNewCodelistActionCanNotBeAccessedWithOutPOST()
    {
        $this->routeMatch->setParam('action', 'add_newCodelist');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $content = json_decode($response->getContent());
        $this->assertSame(0, $content->responseCode);
    }
    public function testAddNewCodelistActionCannotBeAccessed() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'add_newCodelist');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('editCodeListId' => 1, 'reason' => 'Test Reason')    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        }
    }
    public function testAddNewCodelistActionWithPOST() 
    {
        $this->request = new Request();
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'email' => 'test@test.com', 'u_name'=> 'siva.krishna', 'group_id' => 1); 
        $this->routeMatch->setParam('action', 'add_newCodelist');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                                'trackerId' => 25,
                                'newCodeList' => '',
                                'reason' => 'Test Add'
                            )    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse(); 
        $resp = json_decode($response->getContent());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        }
                
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                                'trackerId' => 25,
                                'newCodeList' => 'test codelist' . rand(1, 9),
                                'reason' => 'Test Add'
                            )    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $respCreate = json_decode($response->getContent());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }         
        
        $this->request = new Request();
        $this->routeMatch->setParam('action', 'delete_codelist');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                                'codeListID' => $respCreate->cId,
                                'reason' => 'Test delete'
                            )    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $content = json_decode($response->getContent());
        if ($content->responseCode == 0) {
            $this->assertSame(0, $content->responseCode);
        } else {
            $this->assertSame(1, $content->responseCode);    
        }        
    }
    public function testDeleteCodelistOptionActionCanNotBeAccessedWithOutPOST()
    {
        $this->routeMatch->setParam('action', 'delete_codelist_option');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $content = json_decode($response->getContent());
        $this->assertSame(0, $content->responseCode);
    }
    public function testDeleteCodelistOptionActionCannotBeAccessed() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'delete_codelist_option');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('codeListID' => 1, 'reason' => 'Test Reason')    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    public function testDeleteCodelistOptionActionCustomerWithPOST()
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'delete_codelist_option');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'email' => 'test@test.com', 'u_name' => 'siva', 'group_id' => 1, 'group_name' => 'SuperAdmin');
        
        $this->routeMatch->setParam('tracker_id', 25);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('codeListID' => 0, 'reason' => 'Test Reason')    
                )
            );
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $content = json_decode($response->getContent());
        if ($content->responseCode == 0) {
            $this->assertSame(0, $content->responseCode);
        } else {
            $this->assertSame(1, $content->responseCode);    
        }        
    }  
    public function testAddCodelistOptionsActionCanNotBeAccessedWithOutPOST()
    {
        $this->routeMatch->setParam('action', 'add_codelist_options');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $content = json_decode($response->getContent());
        $this->assertSame(0, $content->responseCode);
    }
    public function testAddCodelistOptionsActionCannotBeAccessed() 
    {
        $_SERVER['SERVER_NAME']='localhost';
        $this->routeMatch->setParam('action', 'add_codelist_options');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('code_list_id' => 1, 'reason' => 'Test Reason')    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    public function testAddCodelistOptionsActionWithPOST() 
    {
        $this->request = new Request();
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'email' => 'test@test.com', 'u_name'=> 'siva.krishna', 'group_id' => 1); 
        $this->routeMatch->setParam('action', 'add_codelist_options');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                                'tracker_id' => 25,
                                'code_list_id' => 1,
                                'names' => array(),
                                'kpi' =>array('0' => 0), 
                                'reason' => 'Test Add'
                            )    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse(); 
        $resp = json_decode($response->getContent());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        }       
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                                'tracker_id' => 25,
                                'code_list_id' => 1,
                                'names' => array('test code list option' . rand(11, 19)),
                                'kpi' => array(1), 
                                'reason' => 'Test Add'
                            )    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $respCreate = json_decode($response->getContent());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }        
        
        $this->request = new Request();
        $this->routeMatch->setParam('action', 'edit_codelist_options');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                                'tracker_id' => 25,
                                'code_list_id' => 1,
                                'option_ids' => array($respCreate->optionIds), 
                                'names' => array('test code list optionEdited' . rand(11, 19)),
                                'kpi' => array(1), 
                                'reason' => 'Test Add'
                            )    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $resp = json_decode($response->getContent());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }         
        
        $this->request = new Request();
        $this->routeMatch->setParam('action', 'delete_codelist_option');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                                'codeListID' => $respCreate->optionIds,
                                'reason' => 'Test delete'
                            )    
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $content = json_decode($response->getContent());
        if ($content->responseCode == 0) {
            $this->assertSame(0, $content->responseCode);
        } else {
            $this->assertSame(1, $content->responseCode);    
        }        
    }    
}
