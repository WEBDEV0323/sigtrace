<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Authentication\Controller\Authentication' => 'Common\Authentication\Controller\AuthenticationController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'auth' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/auth[/:action]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*'
                    ),
                    'defaults' => array(
                        'controller' => 'Authentication\Controller\Authentication',
                        'action'     => 'index',
                    ),
                ),
            ),
            'oAuthredirect' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/oauthredirect',
                    'defaults' => array(
                        'controller' => 'Authentication\Controller\Authentication',
                        'action'     => 'oauthredirect',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'Common\Authentication' => true
        ),
        'template_path_stack' => array(
            'auth' => __DIR__ . '/../view',
        ),
    )
);

