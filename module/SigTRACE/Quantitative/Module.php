<?php
namespace SigTRACE\Quantitative;

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
                'Quantitative\Model\Quantitative' => function ($sm) {
                    return new Model\Quantitative($sm->get('trace'));
                },
                'Common\Audit\Service' => function ($sm) {
                    return new \Common\Audit\Model\Audit($sm->get('trace'));
                },
                'Quantitative\Model\Db' => function ($sm) {
                    return new Model\Db($sm->get('trace'));
                },
               'Common\Role\Model\Role' => function ($sm) {
                   return new \Common\Role\Model\Role($sm->get('trace'));
               },  
                'SigTRACE\Dashboard\Model\Dashboard' => function ($sm) {
                    return new \SigTRACE\Dashboard\Model\Dashboard($sm->get('trace'));
                },               
            ),
        );
    }
}
