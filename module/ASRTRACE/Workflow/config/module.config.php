<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'ASRTRACE\Workflow\Controller\Workflow' => 'ASRTRACE\Workflow\Controller\WorkflowController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'aWorkflow' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/aWorkflow[/:action][/:trackerId][/:formId][/:recordId][/:type][/:filter][/:workflowId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId'=> '[0-9]+',
                        'formId'=> '[0-9]+',
                        'workflowId'=> '[0-9]+',
                        'productId'=> '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'ASRTRACE\Workflow\Controller\Workflow',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'ASRTRACE\Workflow' => true
        ),
        'template_path_stack' => array(
            'aworkflow' => __DIR__ . '/../view',
        ),
    ),
);
