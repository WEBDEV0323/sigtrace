<?php

namespace Common\Import;

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
    
    public function getServiceConfig()
    {
        return array(
           'factories' => array(
               'db_adapter' => function ($sm) {
                   $config = $sm->get('Configuration');
                   return new \Zend\Db\Adapter\Adapter($config['db']);
               },
               'Import\Model\Import' => function ($sm) {
                   return new Model\Import($sm->get('db_adapter'));
               },
               'Import\Service\ImportService' => function ($sm) {
                   return new Service\ImportService($sm->get('db_adapter'));
               },
               'Role\Model\Role' => function ($sm) {
                   return new \Role\Model\Role($sm->get('db_adapter'));
               },
           ),
        );
    }
}
