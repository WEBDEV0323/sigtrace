<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Trigger\Controller\Trigger' => 'Common\Trigger\Controller\TriggerController',
        ),
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'trigger' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/trigger[/:action][/:trackerId][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Trigger\Controller\Trigger',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    
    'view_manager' => array(
        'controller_map' => array(
            'Common\Trigger' => true
        ),
        'template_path_stack' => array(
            'trigger' => __DIR__ . '/../view',
        ),
    ),
);
