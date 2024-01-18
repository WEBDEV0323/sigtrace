<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Audit\Controller\Audit' => 'Common\Audit\Controller\AuditController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'audit' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/auditlog[/:action][/:trackerId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'Audit\Controller\Audit',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'Common\Audit' => true
        ),
        'template_path_stack' => array(
            'audit' => __DIR__ . '/../view',
        ),
    )
);
