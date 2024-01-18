<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Tracker\Controller\Tracker' => 'Common\Tracker\Controller\TrackerController',
            'Tracker\Controller\Form' => 'Common\Tracker\Controller\FormController',
            'Tracker\Controller\Workflow' => 'Common\Tracker\Controller\WorkflowController',
            'Tracker\Controller\Field' => 'Common\Tracker\Controller\FieldController',
            'Tracker\Controller\Codelist' => 'Common\Tracker\Controller\CodelistController',
        )
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'tracker' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/tracker[/:action][/:tracker_id][/:form_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+',
                        'form_id' => '[0-9]+'
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
                    'route' => '/form[/:action][/:trackerId][/:form_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+',
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
                    'route' => '/workflow[/:action][/:trackerId][/:form_id][/:workflow_id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+',
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
                    'route' => '/field[/:action][/:trackerId][/:form_id][/:field_id]',
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
                    'route' => '/codelist[/:action][/:trackerId][/:form_id][/:codelist_id]',
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
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'Common\Tracker' => true
        ),
        'template_path_stack' => array(
            'tracker' => __DIR__ . '/../view'
        ),
    ),
);
