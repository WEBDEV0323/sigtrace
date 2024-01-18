<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Settings\Controller\Settings' => 'Common\Settings\Controller\SettingsController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'settings' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/settings[/:action][/:trackerId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'Settings\Controller\Settings',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'Common\Settings' => true
        ),
        'template_path_stack' => array(
            'settings' => __DIR__ . '/../view',
        ),
    )
);
