<?php
namespace Common\Notification;
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
               
                'Notification\Model\Email' => function ($sm) {
                    return new \Common\Notification\Model\Email($sm->get('trace'));
                },
                'Notification\Model\Swiftmailer' => function ($sm) {
                    return new \Common\Notification\Model\Swiftmailer($sm->get('trace'));
                },
                'Notification\Model\EmailMigration' => function ($sm) {
                    return new \Common\Notification\Model\EmailMigration($sm->get('trace'));
                },
                // 'Tracker\Model\Tracker' => function ($sm) {
                //     return new \Common\Tracker\Model\Tracker($sm->get('trace'));
                // },
                'Audit\Service' => function ($sm) {
                    return new \Common\Audit\Model\Audit($sm->get('trace'));
                },
            ),
        );
    }
}
