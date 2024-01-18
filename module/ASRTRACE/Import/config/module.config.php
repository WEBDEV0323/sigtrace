<?php

return array(
    'controllers' => array(
        'invokables' => array(
                'Import\Controller\Import' => 'Import\Controller\ImportController',
        )
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'import' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/import[/:action][/:tracker_id][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+',
                        'id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Import\Controller\Import',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    
    'view_manager' => array(
        'template_path_stack' => array(
            'import' => __DIR__ . '/../view',
        ),
    ),
);
