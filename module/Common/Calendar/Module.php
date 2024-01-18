<?php

namespace Common\Calendar;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Application\Factory\AppAdapter;

class Module implements AutoloaderProviderInterface, ConfigProviderInterface
{
    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                 __DIR__ . '/autoload_classmap.php',
             ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/'. str_replace("\\", "/", __NAMESPACE__),
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
               'trace' => new AppAdapter('trace'),
               'Common\Calendar\Model\Calendar' => function ($sm) {
                   return new Model\Calendar($sm->get('trace'));
               },
               'Common\Audit\Service' => function ($sm) {
                    return new \Common\Audit\Model\Audit($sm->get('trace'));
               }                       
           ),
        );
    }
}
