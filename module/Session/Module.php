<?php

namespace Session;

use Zend\Mvc\MvcEvent;
use Zend\Db\Adapter\Adapter;

class Module
{
    

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(\Zend\Mvc\MvcEvent $event)
    {
        $sharedEventManager = $event->getApplication()->getEventManager()->getSharedManager();
        $sharedEventManager->attach(
            'Zend\Mvc\Controller\AbstractController', MvcEvent::EVENT_DISPATCH,
            function (MvcEvent $event) {
                $request = $event->getRequest();
                if ($request->isXMLHttpRequest()) {
                    $dispatchResult = $event->getResult();
                    if ($dispatchResult instanceof ViewModel) {
                        $dispatchResult->setTerminal(true);
                    }
                }
            },
            -99
        );
    }
    
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
    public function getServiceConfig()
    {
        return array(
           'factories' => array(
               'db_adapter' => function ($sm) {
                   $config = $sm->get('Configuration');
                   return new Adapter($config['db']);
               }
           ),
        );
    }
}
