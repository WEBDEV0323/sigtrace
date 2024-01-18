<?php

namespace ASRTRACE\Notification;

use Zend\Db\ResultSet\ResultSet;
use Zend\Db\TableGateway\TableGateway;

class Module
{
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

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /*public function onBootstrap(\Zend\Mvc\MvcEvent $event) {
        $sharedEventManager = $event->getApplication()
                        ->getEventManager()->getSharedManager();
        $sharedEventManager->attach(
                'Zend\Mvc\Controller\AbstractController', \Zend\Mvc\MvcEvent::EVENT_DISPATCH, function(\Zend\Mvc\MvcEvent $event) {
                    $request = $event->getRequest();
                    if ($request->isXMLHttpRequest()) {
                        $dispatchResult = $event->getResult();
                        if ($dispatchResult instanceof ViewModel) {
                            $dispatchResult->setTerminal(true);
                        }
                    }
                }, -99);
    }*/
    
    
    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'db_adapter' => function ($sm) {
                    $config = $sm->get('Configuration');
                    return new \Zend\Db\Adapter\Adapter($config['db']);
                },
                'Notification\Model\Email' => function ($sm) {
                    return new \Notification\Model\Email($sm->get('db_adapter'));
                },
                'Notification\Model\Swiftmailer' => function ($sm) {
                    return new \Notification\Model\Swiftmailer($sm->get('db_adapter'));
                },
                'Notification\Model\EmailMigration' => function ($sm) {
                    return new \Notification\Model\EmailMigration($sm->get('db_adapter'));
                },
            ),
        );
    }
}
