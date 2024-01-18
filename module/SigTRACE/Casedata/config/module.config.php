<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Casedata\Controller\Index' => 'SigTRACE\Casedata\Controller\IndexController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'casedata' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/data[/:action][/:trackerId][/:formId][/:workflowId][/:productId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId'=> '[0-9]+',
                        'formId'=> '[0-9]+',
                        'workflowId'=> '[0-9]+',
                        'productId'=> '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'Casedata\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'SigTRACE\Casedata' => true
        ),
        'template_path_stack' => array(
            'casedata' => __DIR__ . '/../view',
        )
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
               'migration' => array(
                    'options' => array(
                        'route'    => 'migration',
                        'defaults' => array(
                            'controller' => 'Casedata\Controller\Index',
                            'action'     => 'migration',
                        ),
                    ),
                ),
            ),
        ),
    ),
);
