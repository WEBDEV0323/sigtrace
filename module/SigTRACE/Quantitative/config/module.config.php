<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Quantitative\Controller\Quantitative' => 'SigTRACE\Quantitative\Controller\QuantitativeController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'quantitative' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/quantitative[/:action][/:trackerId][/:formId][/:dashboardId][/:productId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId'=> '[0-9]+',
                        'formId'=> '[0-9]+',
                        'productId'=> '[0-9]+',
                        'dashboardId'=> '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'Quantitative\Controller\Quantitative',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'SigTRACE\Quantitative' => true
        ),
        'template_path_stack' => array(
            'quantitative' => __DIR__ . '/../view',
        ),
    ),

// Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
                'quantitative_update' => array(
                    'options' => array(
                        'route'    => 'updatequantitative',
                        'defaults' => array(
                            'controller' => 'Quantitative\Controller\Quantitative',
                            'action'     => 'updateQuantitative',
                        ),
                    ),
                ),
            ),
        ),
    ),);
