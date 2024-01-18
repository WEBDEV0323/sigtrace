<?php
namespace SigTRACE\FrequencyAnalysis;

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
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace("\\", "/", __NAMESPACE__),
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
                'FrequencyAnalysis\Model\Db' => function ($sm) {
                    return new Model\Db($sm->get('trace'));
                },
                'FrequencyAnalysis\Model\FrequencyAnalysis' => function ($sm) {
                    return new Model\FrequencyAnalysis($sm->get('trace'));
                },
                'Audit\Model\Audit' => function ($sm) {
                    return new \Audit\Model\Audit($sm->get('trace'));
                }
            ),
        );
    }

}

