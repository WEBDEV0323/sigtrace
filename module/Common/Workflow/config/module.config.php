<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Common\Workflow\Controller\Workflow' => 'Common\Workflow\Controller\WorkflowController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'wp' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/wp[/:action][/:trackerId][/:formId][/:dashboardId][/:asId][/:recordId][/:workflowId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId'=> '[0-9]+',
                        'formId'=> '[0-9]+',
                        'dashboardId'=> '[0-9]+',
                        'asId'=> '[0-9]+',
                        'recordId'=> '[0-9]+',
                        'workflowId'=> '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'Common\Workflow\Controller\Workflow',
                        'action'     => 'index',
                    ),
                ),
            ),
            'awsfiles' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/awsfiles[/:action][/:keyname][/:filename]',
                    'constraints' => array(
                    ),
                    'defaults' => array(
                        'controller' => 'Common\FileHandling\Controller\FileHandling',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'Common\Workflow' => true
        ),
        'template_path_stack' => array(
            'wp' => __DIR__ . '/../view',
        ),
    ),
);
