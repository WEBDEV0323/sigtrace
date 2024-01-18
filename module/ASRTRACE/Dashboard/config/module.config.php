<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Dashboard\Controller\Dashboard' => 'ASRTRACE\Dashboard\Controller\DashboardController',
        ),
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'dashboard' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/aDashboard[/:action][/:tracker_id][/:form_id][/:subaction_id][/:type][/:filter]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+',
                        'form_id' => '[0-9]+',
                        'subaction_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Dashboard\Controller\Dashboard',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    
    'view_manager' => array(
        'controller_map' => array(
            'ASRTRACE\Dashboard' => true
        ),
        'template_path_stack' => array(
            'aDashboard' => __DIR__ . '/../view',
        ),
    ),
);
