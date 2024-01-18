<?php

namespace ASRTRACE\Tracker;

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
                'Tracker\Model\Tracker' => function ($sm) {
                    return new Model\Tracker($sm->get('trace'));
                },
                'Tracker\Model\Form' => function ($sm) {
                    return new Model\Form($sm->get('trace'));
                },
                'Tracker\Model\Workflow' => function ($sm) {
                    return new Model\Workflow($sm->get('trace'));
                },
                'Tracker\Model\Field' => function ($sm) {
                    return new Model\Field($sm->get('trace'));
                },
                'Tracker\Model\Codelist' => function ($sm) {
                    return new Model\Codelist($sm->get('trace'));
                },
                'Tracker\Model\TrackerModule' => function ($sm) {
                    return new Model\TrackerModule($sm->get('trace'));
                },
                'Tracker\Model\UserModule' => function ($sm) {
                    return new Model\UserModule($sm->get('trace'));
                },
                'Tracker\Model\WorkflowMgmModule' => function ($sm) {
                    return new Model\WorkflowMgmModule($sm->get('trace'));
                },
                'Tracker\Model\AccessModule' => function ($sm) {
                    return new Model\AccessModule($sm->get('trace'));
                },
                'Role\Model\Role' => function ($sm) {
                    return new \Role\Model\Role($sm->get('trace'));
                },
            ),
        );
    }
}
