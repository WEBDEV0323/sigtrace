<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'FrequencyAnalysis\Controller\FrequencyAnalysis' => 'SigTRACE\FrequencyAnalysis\Controller\FrequencyAnalysisController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'frequency_analysis' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/frequency[/:action][/:trackerId][/:formId][/:exp1][/:exp2]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId'=> '[0-9]+',
                        'formId'=> '[0-9]+',
                        'exp1'=> '[0-9]+',
                        'exp2'=> '[0-9]+'
                    ),
                    'defaults' => array(
                        'controller' => 'FrequencyAnalysis\Controller\FrequencyAnalysis',
                        'action'     => 'analysis',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'SigTRACE\FrequencyAnalysis' => true
        ),
        'template_path_stack' => array(
            'frequency_analysis' => __DIR__ . '/../view',
        )
    ),
);
