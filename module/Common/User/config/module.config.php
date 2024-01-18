<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'User\Controller\User' => 'Common\User\Controller\UserController',
        ),
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'user' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/user[/:action][/:trackerId][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'User\Controller\User',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    
    'view_manager' => array(
        'controller_map' => array(
            'Common\User' => true
        ),
        'template_path_stack' => array(
            'user' => __DIR__ . '/../view',
        ),
    ),
);
