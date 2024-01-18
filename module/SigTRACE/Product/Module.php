<?php

namespace SigTRACE\Product;

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
                'Product\Model\Product' => function ($sm) {
                    return new Model\Product($sm->get('trace'));
                },
                'Product\Model\Log' => function ($sm) {
                    return new Model\Log($sm->get('trace'));
                },
                'Audit\Model\Audit' => function ($sm) {
                    return new \SigTRACE\Audit\Model\Audit($sm->get('trace'));
                },
                'Settings\Model\Import' => function ($sm) {
                    return new \SigTRACE\Settings\Model\Import($sm->get('trace'));
                },
                'Casedata\Model\Casedata' => function ($sm) {
                     return new \SigTRACE\Casedata\Model\Casedata($sm->get('trace'));
                },
                'Product\Model\ActiveSubstance' => function ($sm) {
                    return new Model\ActiveSubstance($sm->get('trace'));
                },
                'Product\Model\MedicalConcept' => function ($sm) {
                    return new Model\MedicalConcept($sm->get('trace'));
                },
                'Common\Role\Model\Role' => function ($sm) {
                    return new \Common\Role\Model\Role($sm->get('trace'));
                },                        
            ),
        );
    }

}

