<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Tracker\Controller\Tracker' => 'ASRTRACE\Tracker\Controller\TrackerController',
            'Tracker\Controller\Form' => 'ASRTRACE\Tracker\Controller\FormController',
            'Tracker\Controller\Workflow' => 'ASRTRACE\Tracker\Controller\WorkflowController',
            'Tracker\Controller\Field' => 'ASRTRACE\Tracker\Controller\FieldController',
            'Tracker\Controller\Codelist' => 'ASRTRACE\Tracker\Controller\CodelistController',
            // 'Tracker\Controller\Aws' => 'ASRTRACE\Tracker\Controller\AwsController',
            'Tracker\Controller\WorkflowMgm' => 'ASRTRACE\Tracker\Controller\WorkflowMgmController',
            'Tracker\Controller\Access' => 'ASRTRACE\Tracker\Controller\AccessController',
        ),
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'tracker' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/tracker[/:action][/:tracker_id][/:action_id][/:subaction_id][/:type][/:filter][/:workflow]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+',
                        'action_id' => '[0-9]+',
                        'subaction_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Tracker\Controller\Tracker',
                        'action' => 'index',
                    ),
                ),
            ),
            'form' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/form[/:action][/:tracker_id][/:form_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+',
                        'form_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Tracker\Controller\Form',
                        'action' => 'index',
                    ),
                ),
            ),
            'workflow' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/workflow[/:action][/:tracker_id][/:form_id][/:workflow_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+',
                        'form_id' => '[0-9]+',
                        'workflow_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Tracker\Controller\Workflow',
                        'action' => 'index',
                    ),
                ),
            ),
            'field' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/field[/:action][/:tracker_id][/:form_id][/:field_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+',
                        'form_id' => '[0-9]+',
                        'field_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Tracker\Controller\Field',
                        'action' => 'index',
                    ),
                ),
            ),
            'codelist' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/codelist[/:action][/:tracker_id][/:form_id][/:codelist_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+',
                        'form_id' => '[0-9]+',
                        'codelist_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Tracker\Controller\Codelist',
                        'action' => 'index',
                    ),
                ),
            ),
            'workflowmgm' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/workflowmgm[/:action][/:tracker_id][/:form_id][/:record_id][/:type][/:filter][/:workflow_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+',
                        'form_id' => '[0-9]+',
                        'workflow_id' => '[0-9]+',
                        'record_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Tracker\Controller\WorkflowMgm',
                        'action' => 'index',
                    ),
                ),
            ),
            'access' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/access[/:action][/:tracker_id][/:form_id][/:workflow_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+',
                        'form_id' => '[0-9]+',
                        'workflow_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Tracker\Controller\Access',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'ASRTRACE\Tracker' => true
        ),
        'template_path_stack' => array(
            'tracker' => __DIR__ . '/../view'
        ),
    ),
);
