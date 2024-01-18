<?php
namespace Common\Report;

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
                    __NAMESPACE__ => __DIR__ . '/src/' . str_replace('\\', '/', __NAMESPACE__),
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
                'Report\Service' => function ($sm) {
                    return new Model\Report($sm->get('trace'));
                },
                'Report\Pdfcreater' => function ($sm) {
                    return new Model\Pdfcreater($sm->get('trace'));
                },
                'Audit\Service' => function ($sm) {
                    return new \Common\Audit\Model\Audit($sm->get('trace'));
                }
            )
        );


    }
}
