<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'Common\FileHandling\Controller\FileHandling' => 'Common\FileHandling\Controller\FileHandlingController',
        )
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'aws' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/aws[/:action][/:keyname][/:filename]',
                    'constraints' => array(
                    ),
                    'defaults' => array(
                        'controller' => 'FileHandling\Controller\FileHandling',
                        'action' => 'index',
                    ),
                ),
            ),        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'Common\FileHandling' => true
        ),
        'template_path_stack' => array(
            'filehandling' => __DIR__ . '/../view'
        ),
    ),
);
