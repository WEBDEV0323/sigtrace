<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Role\Controller\Role' => 'Common\Role\Controller\RoleController',
        ),
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'role' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/role[/:action][/:trackerId][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Role\Controller\Role',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    
    'view_manager' => array(
        'controller_map' => array(
            'Common\Role' => true
        ),
        'template_path_stack' => array(
            'role' => __DIR__ . '/../view',
        ),
    ),
);
