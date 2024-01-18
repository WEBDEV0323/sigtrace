<?php 
return array(
    'controllers' => array(
        'invokables' => array(
            'SignalCalendar\Controller\Index' => 'SigTRACE\SignalCalendar\Controller\IndexController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'SignalCalendar' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/signalcalendar[/:action][/:trackerId][/:formId][/:workflowId][/:productId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId'=> '[0-9]+',
                        'formId'=> '[0-9]+',
                        'workflowId'=> '[0-9]+',
                        'productId'=> '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'SignalCalendar\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'SigTRACE\SignalCalendar' => true
        ),
        'template_path_stack' => array(
            'SignalCalendar' => __DIR__ . '/../view',
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
                            'controller' => 'SignalCalendar\Controller\Index',
                            'action'     => 'migration',
                        ),
                    ),
                ),
            ),
        ),
    ),
);
