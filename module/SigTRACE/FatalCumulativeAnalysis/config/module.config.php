<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'FatalCumulativeAnalysis\Controller\FatalCumulativeAnalysis' => 'SigTRACE\FatalCumulativeAnalysis\Controller\FatalCumulativeAnalysisController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'fatal_cumulative_analysis' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/fatal_cumulative[/:action][/:trackerId][/:formId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId'=> '[0-9]+',
                        'formId'=> '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'FatalCumulativeAnalysis\Controller\FatalCumulativeAnalysis',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'SigTRACE\FatalCumulativeAnalysis' => true
        ),
        'template_path_stack' => array(
            'fatal_cumulative_analysis' => __DIR__ . '/../view',
        )
    )
);
