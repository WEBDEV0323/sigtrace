<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Notification\Controller\Email' => 'Notification\Controller\EmailController',
        ),
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'email' => array(
                'type' => 'segment',
                'options' => array(
                    'route'    => '/email[/:action][/:tracker_id][/:template_id][/:param1][/:param2]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+',
                        'template_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Notification\Controller\Email',
                        'action'     => 'sendmail',
                    ),
                ),
            ),
        ),
    ),
    
    'view_manager' => array(
        'template_path_stack' => array(
            'notification' => __DIR__ . '/../view',
        ),
    ),

    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
                'notification' => array(
                    'options' => array(
                        'route'    => 'user_migration',
                        'defaults' => array(
                            'controller' => 'Notification\Controller\Email',
                            'action'     => 'updateDataBaseForEmailChange',
                        ),
                    ),
                ),
            ),
        ),
    ),

);
