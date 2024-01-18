<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Dashboard\Controller\Index' => 'SigTRACE\Dashboard\Controller\IndexController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'dashboard' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/dashboard[/:action][/:trackerId][/:formId][/:dashboardId][/:asId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId'=> '[0-9]+',
                        'formId'=> '[0-9]+',
                        'asId'=> '[0-9]+',
                        'dashboard'=> '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'Dashboard\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'SigTRACE\Dashboard' => true
        ),
        'template_path_stack' => array(
            'dashboard' => __DIR__ . '/../view',
        ),
    ),
);
