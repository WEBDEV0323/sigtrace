<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Reports\Controller\Index' => 'Reports\Controller\IndexController',
        ),
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'report' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/report[/:action][/:tracker_id][/:form_id][/:report_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+',
                        'form_id' => '[0-9]+',
                        'report_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Reports\Controller\Index',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    
    'view_manager' => array(
        'template_path_stack' => array(
            'reports' => __DIR__ . '/../view',
        ),
    ),
);
