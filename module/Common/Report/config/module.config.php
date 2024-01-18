<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Report\Controller\Index' => 'Common\Report\Controller\ReportController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'report' => array(
                'type'    => 'Segment',
                'options' => array(
                    'route'    => '/report[/:action][/:trackerId][/:formId][/:reportId][/:type][/:date]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'trackerId'     => '[0-9]+',
                        'formId'     => '[0-9]+',
                        'reportId'     => '[0-9]+',
                        //'date' => '[a-zA-Z][a-zA-Z0-9_-]*',
                    ),
                    'defaults' => array(
                        '__NAMESPACE__' => 'Report\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'Common\Report' => true
        ),
        'template_path_stack' => array(
            'report' => __DIR__ . '/../view',
        ),
    ),
);
