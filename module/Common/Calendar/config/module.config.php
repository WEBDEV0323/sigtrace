<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Calendar\Controller\Calendar' => 'Common\Calendar\Controller\CalendarController',
        ),
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'calendar' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/calendar[/:action][/:trackerId][/:form_id][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'Calendar\Controller\Calendar',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    
    'view_manager' => array(
        'controller_map' => array(
            'Common\Calendar' => true
        ),
        'template_path_stack' => array(
            'calendar' => __DIR__ . '/../view',
        ),
    ),
);
