<?php
namespace TrackerTest\Controller;

// define('IP', '127.0.0.1');
// $_SERVER['SERVER_NAME'] = 'localhost';
use TrackerTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Tracker\Controller\FieldController;
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

class FieldControllerTest extends TestCase
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
        $this->controller       =   new FieldController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Field'));
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
    
    public function testGetFieldModelService()
    {
        $this->assertInstanceOf('Common\Tracker\Model\Field', $this->controller->getModelService());
    }
    
    public function testfieldManagementNoUserData()
    {
        $this->routeMatch->setParam('action', 'field_management');
        $this->trackerContainer->tracker_ids = array('25','61');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testfieldManagement()
    {
        $this->routeMatch->setParam('action', 'field_management');
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->trackerContainer->tracker_ids = array('25','61');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        } 
    }

    /*
     * Copy tracker with data OR Copy Tracker without data
     */
    
    public function testaddEditFieldNoUserData()
    {
        //$_SESSION['u_id'] = 1;
        $this->routeMatch->setParam('action', 'add_edit_field');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('tracker_id' => 25,
                    'comment' => 'ffgdf',
                    'f_id'=>1,
                    'code_list_id'=>'dfsfds',
                    'role_id' => 'ffgdf',
                    'fieldName'=>'test',
                    'edit_field_name_hidden'=>'dfsfds',
                    'kpi' => 'ffgdf',
                    'fieldType'=>'Text',
                    'workflowId'=>'dfsfds', 
                    'validation_req'=>'dfsfds',    
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    
    public function testaddEditField()
    {
        //$_SESSION['u_id'] = 1;
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('action', 'add_edit_field');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('tracker_id' => 25,
                    'comment' => 'ffgdf',
                    'f_id'=>59,
                    'code_list_id'=>'dfsfds',
                    'role_id' => 'ffgdf',
                    'fieldName'=>'destatus',
                    'edit_field_name_hidden'=>'dfsfds',
                    'kpi' => 'ffgdf',
                    'fieldType'=>'Combo Box',
                    'workflowId'=>'dfsfds', 
                    'validation_req'=>1,    
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        } 
    }
    
    public function testaddEditFieldSave()
    {
        //$_SESSION['u_id'] = 1;
        $_SERVER['SERVER_NAME']='localhost';
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('action', 'add_edit_field');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('tracker_id' => 25,
                        'form_id' => 85,
                        'comment' => 'ffgdf',
                        'f_id'=>0,
                        'code_list_id'=>'dfsfds',
                        'role_id' => 'ffgdf',
                        'fieldName'=>'destatus',
                        'edit_field_name_hidden'=>'dfsfds',
                        'kpi' => 'ffgdf',
                        'fieldType'=>'Combo Box',
                        'workflowId'=>3, 
                        'validation_req'=>1,    
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        } 
    }

    /*
     * Copy tracker with user role or  without user role
     */
    
    public function testdeleteField()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('action', 'delete_field');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->routeMatch->setParam('field_id', 1);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('comment' => 'test',
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        } 
    }
    
    public function testdeleteFieldNoUserData()
    {
        $this->routeMatch->setParam('action', 'delete_field');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->routeMatch->setParam('field_id', 1);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('comment' => 'test',
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());           
        } 
    }
    
    public function testdeleteFieldNoPostData()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('action', 'delete_field');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->routeMatch->setParam('field_id', 1);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
    public function testdeleteFieldInvalidData()
    {
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('action', 'delete_field');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 'test');
        $this->routeMatch->setParam('field_id', 'test');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('comment' => 'test',
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_501, $response->getStatusCode());           
        } 
    }
    
    public function testaddEditFieldNoPostData()
    {
        //$_SESSION['u_id'] = 1;
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('action', 'add_edit_field');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }

    /*
     * Copy tracker if data is in post
     */
    public function testgetValidationRuleAction()
    {
        $_SESSION['u_id'] = 1;
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('action', 'get_validation_rule');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('fieldtype' => 'Text',
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetValidationRuleActionNoUserData()
    {
        $this->routeMatch->setParam('action', 'get_validation_rule');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('fieldtype' => 'Text',
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('false', $response->getContent());
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    /*
     *  if tracker is copied
     */
    public function testformulaFields()
    {
        $_SESSION['u_id'] = 1;
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('action', 'formula_fields');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('t_name' => 'ffgdf',
                    'datacopy'=>'withdata',
                    'userrole'=>'withuserrole',
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('"success"', $response->getContent());
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_405) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        } 
    }
    
    public function testformulaFieldsNoUserData()
    {
        $this->routeMatch->setParam('action', 'formula_fields');
        $this->routeMatch->setParam('tracker_id', 25);
        $this->routeMatch->setParam('form_id', 85);
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('t_name' => 'ffgdf',
                    'datacopy'=>'withdata',
                    'userrole'=>'withuserrole',
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('"success"', $response->getContent());
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }

    /*
     *  if tracker is not copied
     */
    public function testsaveFormula()
    {
        $_SESSION['u_id'] = 1;
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('action', 'save_formula');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('reason' => 'ffgdf',
                    'formula'=>'withdata',
                    'fieldId'=>'withuserrole',
                    'trackerId'=>25,
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('"failure"', $response->getContent());
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }
    
    public function testsaveFormulaNoUserData()
    {
        $this->routeMatch->setParam('action', 'save_formula');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array('reason' => 'ffgdf',
                    'formula'=>'withdata',
                    'fieldId'=>'withuserrole',
                    'trackerId'=>25,
                    )
                )
            );
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('"failure"', $response->getContent());
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testsaveFormulaNoPostData()
    {
        $_SESSION['u_id'] = 1;
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 1, 'group_name' => 'Test Group','email'=>'unittest@test.com');
        $this->routeMatch->setParam('action', 'save_formula');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        //$this->assertSame('"failure"', $response->getContent());
        $this->assertEquals(\Zend\Http\Response::STATUS_CODE_405, $response->getStatusCode());
    }
    
}
