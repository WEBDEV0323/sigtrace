<?php
namespace TrackerTest\Controller;

use TrackerTest\Bootstrap;
use Zend\Mvc\Router\Http\TreeRouteStack as HttpRouter;
use Common\Tracker\Controller\AwsController;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\Stdlib\Parameters;
use Zend\Stdlib\ResponseInterface;
use Zend\Mvc\Router\RouteMatch;
//use PHPUnit_Framework_TestCase as TestCase;
use PHPUnit\Framework\TestCase;
use Zend\Test\PHPUnit\Controller\AbstractHttpControllerTestCase;
use Zend\Mvc\Controller\Plugin\Forward;

class AwsControllerTest extends TestCase
{
    protected $controller;
    protected $request;
    protected $response;
    protected $routeMatch;
    protected $event;
    protected $p;

    protected function setUp()
    {
        @session_start();
        $serviceManager = Bootstrap::getServiceManager();
        $this->controller = new AwsController();
        $this->request    = new Request();
        $this->routeMatch = new RouteMatch(array('controller' => 'Aws'));
        $this->event      = new MvcEvent();
        $config = $serviceManager->get('Config');
        $routerConfig = isset($config['router']) ? $config['router'] : array();
        $router = HttpRouter::factory($routerConfig);
        $this->event->setRouter($router);
        $this->event->setRouteMatch($this->routeMatch);
        $this->controller->setEvent($this->event);
        $this->controller->setServiceLocator($serviceManager);
    }

    public function testConnectToAwsS3()
    {
        $this->routeMatch->setParam('action', 'connectToAws');
        $result = $this->controller->dispatch($this->request);
        $response = $this->controller->getResponse();
        $this->assertNotSame('PutObject', $result);
    }
    // public function test_uploadFilesToAws()
    // {
    //     $this->routeMatch->setParam('action', 'uploadFilesToAws');
    //     $this->routeMatch->setParam('keyname', 'attachment/attach_124_287/test.txt');
    //     $this->routeMatch->setParam('filename', 'C:\Users\chandrakesh.p\Desktop\test.txt');
    //     $result = $this->controller->dispatch($this->request);
    //     $response = $this->controller->getResponse();
    //     $this->assertNotSame('data', $result);
    // }
    //    public function testDownloadFilesFromAwsAction()
    //    {
    //        $this->routeMatch->setParam('action', 'downloadFilesFromAws');
    //        $this->routeMatch->setParam('keyname', 'attachment/attach_124_287/test_1523277071.txt');
    //        $this->routeMatch->setParam('filename', 'test_1523277071.txt');
    //        $this->assertContains('Content-type: text/html; charset=UTF-8', xdebug_get_headers());
    //
    //        $response = $this->controller->getResponse();
    //        //$this->assertSame('true', $response->getContent());
    //        $this->assertTrue(empty($response->getContent()));
    //      
    //    }
    // public function testUploadFileToAWS()
    // {
    //     $this->routeMatch->setParam('action', 'uploadFilesToAws');
    //     $this->routeMatch->setParam('keyname', 'attachment/attach_124_287/test_1523277071.txt');
    //     $this->routeMatch->setParam('filename', 'test_1523277071.txt');
    //     // Arrange
    //     $bucket = "bucketName";
    //     $keyName = "test_upload.txt";
    //     $filepath = '';
    //    // $uploadFile = GenerateStreamFromString(StringToTestWith);

    //     // Act
    //     //$aws = new AmazonWebServicesUtility(bucket);
    //     $awsUpload = aws.UploadFile(keyName, uploadFile);
    //     Assert.AreEqual(true, awsUpload);
    // }
}
