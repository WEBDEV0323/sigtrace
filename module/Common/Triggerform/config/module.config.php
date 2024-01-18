<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Triggerform\Controller\Triggerform' => 'Common\Triggerform\Controller\TriggerformController',
        ),
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'triggerform' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/triggerform[/:action][/:trackerId][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Triggerform\Controller\Triggerform',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    
    'view_manager' => array(
        'controller_map' => array(
            'Common\Triggerform' => true
        ),
        'template_path_stack' => array(
            'triggerform' => __DIR__ . '/../view',
        ),
    ),
);
