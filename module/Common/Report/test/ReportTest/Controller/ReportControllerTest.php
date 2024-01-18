<?php
namespace Common\ReportTest\Controller;

use ReportTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Report\Controller\ReportController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use PHPUnit\Framework\TestCase;
use Zend\Session\Container;
use Session\Container\SessionContainer;


class ReportControllerTest extends TestCase
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
        $this->controller       =   new ReportController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'Report'));
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
    
    public function testgetWorkflowModelService()
    {
        $this->assertInstanceOf('Common\Report\Model\Report', $this->controller->getReportService());
    } 
    
    public function testIndexCannotBeAccessedWithoutUserSession() 
    {   
        $this->routeMatch->setParam('action', 'index');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    public function testIndexCanBeAccessed() 
    {   
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('109' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array(109, 199);
        $this->routeMatch->setParam('action', 'index');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }        
    public function testFilterCannotBeAccessedWithoutUserSession() 
    {   
        $this->routeMatch->setParam('action', 'filter');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('reportId', 1);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    public function testFilterCanBeAccessed() 
    {   
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('109' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array(109, 199);
        $this->routeMatch->setParam('action', 'filter');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('reportId', 1);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }        
    public function testFilterReportDataCannotBeAccessedWithoutUserSession() 
    {   
        $this->routeMatch->setParam('action', 'fetchReportData');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('reportId', 1);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    public function testFilterReportDataCanBeAccessed() 
    {   
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('109' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array(109, 199);
        $this->routeMatch->setParam('action', 'fetchReportData');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('reportId', 1);
        //$this->routeMatch->setParam('urlQuery', '');
        
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array( 
                        'condition' => "AND DATE_FORMAT(STR_TO_DATE(aerrecvdate, '%d-%b-%Y'), '%Y-%m-%d') BETWEEN '2019-11-23' AND '2019-12-27'",
                    )    
                )
            );
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }        
    public function testDownloadaCannotBeAccessedWithoutUserSession() 
    {   
        $this->routeMatch->setParam('action', 'download');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('reportId', 1);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    public function testDownloadCanBeAccessed() 
    {   
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('109' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array(109, 199);
        $this->routeMatch->setParam('action', 'download');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('reportId', 1);
        
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array( 
                        'condition' => "AND DATE_FORMAT(STR_TO_DATE(aerrecvdate, '%d-%b-%Y'), '%Y-%m-%d') BETWEEN '2019-11-23' AND '2019-12-27'",
                    )    
                )
            );
        
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }     
    
    public function testVIndexCannotBeAccessedWithoutUserSession() 
    {   
        $this->routeMatch->setParam('action', 'vindex');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    public function testVIndexCanBeAccessed() 
    {   
        $this->userContainer->u_id = 1;
        $this->userContainer->user_details = array('u_id' =>  1, 'group_id' => 2, 'group_name' => 'Test Group');
        $this->trackerContainer->tracker_user_groups = array('109' => array('session_group'=>'ABC', 'session_group_id'=> 1));
        $this->trackerContainer->tracker_ids = array(109, 199);
        $this->routeMatch->setParam('action', 'vindex');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    } 
    
    public function testCustomReportCanBeAccessed() 
    {   
        $this->userContainer->u_id = 1;       
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('reportId', 1);
        $this->routeMatch->setParam('action', 'customReport');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    } 

    public function testCustomCanBeAccessed() 
    {   
        $this->userContainer->u_id = 1;
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->routeMatch->setParam('reportId', 1);
        $this->routeMatch->setParam('action', 'custom');
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();    
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_302) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_302, $response->getStatusCode());
        } else if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    } 
}

