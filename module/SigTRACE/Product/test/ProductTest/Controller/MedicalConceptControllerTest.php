<?php

namespace SigTRACE\ProductTest\Controller;

use ProductTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use SigTRACE\Product\Controller\MedicalConceptController;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Mvc\Router\RouteMatch;
use PHPUnit\Framework\TestCase;
use Zend\Session\Container;
use Session\Container\SessionContainer;
use Common\Role\Controller\RoleController;

class MedicalConceptControllerTest extends TestCase
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
        $this->controller       =   new MedicalConceptController();
        $this->request          =   new Request();
        $this->routeMatch       =   new RouteMatch(array('controller' => 'MedicalConcept'));
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

    public function testgetMedicalConceptManagementCanBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'medicalConceptManagement');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetAddCanBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'add');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetEditCanBeAccessed() 
    {
        $this->routeMatch->setParam('action', 'edit');
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetMedicalConceptCheckCheckForEmptyPost() 
    {
        $this->routeMatch->setParam('action', 'medicalConceptCheck');
        $this->request->setMethod('POST')->setPost(new Parameters(array()));
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetMedicalConceptCheckCheckForPostData() 
    {
        $this->routeMatch->setParam('action', 'medicalConceptCheck');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                        'medicalConceptName' => 'Test Medical Concept',
                        'preferredTermIds' => array(0 => 1),
                        'actSubId' => 1,
                        'archived' => 0,
                        'trackerId' => 109,
                        'formId' => 199,
                        'reason' => 'test'
                    )
                )
            );
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }    
    
    public function testgetSaveEditMedicalConceptCheckForEmptyPost() 
    {
        $this->routeMatch->setParam('action', 'saveEditMedicalConcept');
        $this->request->setMethod('POST')->setPost(new Parameters(array()));
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetSaveEditMedicalConceptCheckForPostData() 
    {
        $this->routeMatch->setParam('action', 'saveEditMedicalConcept');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                        'medicalConceptName' => 'Test Edit Medical Concept',
                        'type' => 'Medical Concept',
                        'medicalConceptId' => 1,
                        'preferredTermIds' => array(0 => 1),
                        'trackerId' => 109,
                        'reason' => 'test'
                    )
                )
            );
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());
    }    
    
    public function testgetDeleteMedicalConceptCheckForEmptyPost() 
    {
        $this->routeMatch->setParam('action', 'deleteMedicalConcept');
        $this->request->setMethod('POST')->setPost(new Parameters(array()));
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
    }
    
    public function testgetDeleteMedicalConceptCheckForPostData() 
    {
        $this->routeMatch->setParam('action', 'deleteMedicalConcept');
        $this->request->setMethod('POST')
            ->setPost(
                new Parameters(
                    array(
                        'pt_id' => 1,
                        'tracker_id' => 109,
                        'form_id' => 199,
                        'pt_name' => 'test',
                        'comment' => 'test'
                    )
                )
            );
        $this->routeMatch->setParam('trackerId', 109);
        $this->routeMatch->setParam('formId', 199);
        $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        if ($response->getStatusCode() == \Zend\Http\Response::STATUS_CODE_401) {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_401, $response->getStatusCode());
        } else {
            $this->assertSame(\Zend\Http\Response::STATUS_CODE_200, $response->getStatusCode());           
        }
    }
}
