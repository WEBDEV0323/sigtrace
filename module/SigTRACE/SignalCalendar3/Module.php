<?php
namespace SigTRACE\SignalCalendar;

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
                'SignalCalendar\Model\Casedata' => function ($sm) {
                    return new Model\Casedata($sm->get('trace'));
                },
                'Common\Audit\Service' => function ($sm) {
                    return new \Common\Audit\Model\Audit($sm->get('trace'));
                },
                'SignalCalendar\Model\Db' => function ($sm) {
                    return new Model\Db($sm->get('trace'));
                },

               'SignalCalendar\Helper\SignalCalendarHelper' => function ($sm) {
                   return new Helper\CasedataHelper($sm->get('trace'));
               },
               'SignalCalendar\Service\SignalCalendarService' => function ($sm) {
                   return new Service\CasedataService($sm->get('trace'));

               },
               'Common\Role\Model\Role' => function ($sm) {
                   return new \Common\Role\Model\Role($sm->get('trace'));
               },
               'Common\Notification\Controller\Email' => function ($sm) {
                   return new \Common\Notification\Controller\EmailController($sm->get('trace'));
               },
            ),
        );
    }

}

