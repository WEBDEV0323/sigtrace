<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'SignalCalendar\Controller\SignalCalendar' => 'SigTRACE\SignalCalendar\Controller\SignalCalendarController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'SignalCalendar' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/SignalCalendar[/:action][/:trackerId][/:formId][/:dashboardId][/:productId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId'=> '[0-9]+',
                        'formId'=> '[0-9]+',
                        'productId'=> '[0-9]+',
                        'dashboardId'=> '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'SignalCalendar\Controller\SignalCalendar',
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
                            'controller' => 'SignalCalendar\Controller\SignalCalendar',
                            'action'     => 'updateQuantitative',
                        ),
                    ),
                ),
            ),
        ),
    ),);
