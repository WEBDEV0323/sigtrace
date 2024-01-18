<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Customer\Controller\Customer' => 'Common\Customer\Controller\CustomerController',
        ),
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'customer' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/customer[/:action][/:customer_id][/:action_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'customer_id'     => '[0-9]+',
                        'action_id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Customer\Controller\Customer',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    
    'view_manager' => array(
        'controller_map' => array(
            'Common\Customer' => true
        ),
        'template_path_stack' => array(
            'customer' => __DIR__ . '/../view',
        ),
    ),
);
