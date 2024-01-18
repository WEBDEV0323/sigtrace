<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'BulkAction\Controller\BulkAction' => 'Common\BulkAction\Controller\BulkActionController',
        ),
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'trigger' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/bulk-action[/:action][/:trackerId][/:formId][/:actionId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+',
                        'formId' => '[0-9]+',
                        'actionId' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'BulkAction\Controller\BulkAction',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    
    'view_manager' => array(
        'controller_map' => array(
            'Common\BulkAction' => true
        ),
        'template_path_stack' => array(
            'bulk-action' => __DIR__ . '/../view',
        ),
        'template_map' => array(
            'bulk/action'           => __DIR__ . '/../view/common/bulk-action/bulk-action/index.phtml',
        ),
    ),
);

