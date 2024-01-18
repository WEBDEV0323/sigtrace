<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Product\Controller\Index' => 'SigTRACE\Product\Controller\IndexController',
            'Product\Controller\Labelevent' => 'SigTRACE\Product\Controller\LabeleventController',
            'Product\Controller\Synonym' => 'SigTRACE\Product\Controller\SynonymController',
            'Product\Controller\Special' => 'SigTRACE\Product\Controller\SpecialController',
            'Product\Controller\ActiveSubstance' => 'SigTRACE\Product\Controller\ActiveSubstanceController',
            'Product\Controller\MedicalConcept' => 'SigTRACE\Product\Controller\MedicalConceptController',
        ),
    ),

    'router' => array(
        'routes' => array(
            'product' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/product[/:action][/:trackerId][/:formId][/:productId][/:labelId][/:synonymId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+',
                        'productId' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Product\Controller\Index',
                        'action'     => 'index',
                    ),
                ),
            ),
            'label-event' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/product/label-event[/:action][/:trackerId][/:formId][/:productId][/:labelId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+',
                        'productId' => '[0-9]+',
                        'labelId' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Product\Controller\Labelevent',
                        'action'     => 'index',
                    ),
                ),
            ),
            'synonym' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/product/synonym[/:action][/:trackerId][/:formId][/:productId][/:synonymId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+',
                        'productId' => '[0-9]+',
                        'synonymId' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Product\Controller\Synonym',
                        'action'     => 'index',
                    ),
                ),
            ),
            'special_situation' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/product/special_situation[/:action][/:trackerId][/:formId][/:productId][/:situationId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+',
                        'productId' => '[0-9]+',
                        'situationId' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Product\Controller\Special',
                        'action'     => 'index',
                    ),
                ),
            ),
            'activesubstance_management' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/activesubstance[/:action][/:trackerId][/:formId][/:activeSubstanceId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+',
                        'activeSubstanceId' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Product\Controller\ActiveSubstance',
                        'action'     => 'index',
                    ),
                ),
            ),
            'medicalconcept_management' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/medicalconcept[/:action][/:trackerId][/:formId][/:activeSubstanceId][/:medicalConceptId]',
                    'constraints' => array(
                        'action' => '[a-zA-Z_-][a-zA-Z0-9_-]*',
                        'trackerId' => '[0-9]+',
                        'medicalConceptId' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'Product\Controller\MedicalConcept',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'SigTRACE\Product' => true
        ),
        'template_path_stack' => array(
            'product' => __DIR__ . '/../view',
        ),
    ),
);
