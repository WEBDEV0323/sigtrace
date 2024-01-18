<?php
namespace Application;

return array(
    'controllers' => array(
        'invokables' => array(
            'Application\Controller\Index' => 'Application\Controller\IndexController',
            'Notification\Controller\Email' => 'Common\Notification\Controller\EmailController',
            'Authentication\Controller\Authentication' => 'Common\Authentication\Controller\AuthenticationController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Authentication\Controller',
                        'controller' => 'Authentication',
                        'action'     => 'index',
                    ),
                ),
            ),
            'application' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/application',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Application\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                ),
            ),
            'healthcheck' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/healthcheck',
                    'defaults' => array(
                        'controller' => 'Application\Controller\Index',
                        'action'     => 'healthcheck',
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
    ),
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
        ),
    ),
    
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/500.phtml',
            // 'error/index'             => __DIR__ . '/../view/error/index.phtml',
            'navbar'                 =>__DIR__ . '/../view/layout/topnav.phtml',
            'sidebar'                =>__DIR__ . '/../view/layout/sidenav.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),


    ),
    'service_manager' => array(
        'aliases' => array(
            'db' => 'Zend\Db\Adapter\Adapter',
        ),
    ),
    // Placeholder for console routes
    'console' => array(
        'router' => array(
            'routes' => array(
                'home' => array(
                    'options' => array(
                        'route'    => 'cronjob',
                        'defaults' => array(
                            'controller' => 'Notification\Controller\Email',
                            'action'     => 'sendMailCronJob',
                        ),
                    ),
                ),
            'home1' => array(
                    'options' => array(
                        'route'    => 'cronjob1',
                        'defaults' => array(
                            'controller' => 'Notification\Controller\Email',
                            'action'     => 'sendMailCronJob1',
                        ),
                    ),
                ),
                'home2' => array(
                    'options' => array(
                        'route'    => 'cronjob2',
                        'defaults' => array(
                            'controller' => 'Notification\Controller\Email',
                            'action'     => 'sendMailCronJob2',
                        ),
                    ),
                ),
                'home3' => array(
                    'options' => array(
                        'route'    => 'cronjob3',
                        'defaults' => array(
                            'controller' => 'Notification\Controller\Email',
                            'action'     => 'sendMailCronJob3',
                        ),
                    ),
                ),
               'home4' => array(
                    'options' => array(
                        'route'    => 'cronjob4',
                        'defaults' => array(
                            'controller' => 'Notification\Controller\Email',
                            'action'     => 'sendMailCronJob4',
                        ),
                    ),
                ),
                'home5' => array(
                    'options' => array(
                        'route'    => 'checkReminder',
                        'defaults' => array(
                            'controller' => 'Notification\Controller\Email',
                            'action'     => 'checkReminder',
                        ),
                    ),
                ),
                'home6' => array(
                    'options' => array(
                        'route'    => 'checkSubscription',
                        'defaults' => array(
                            'controller' => 'Notification\Controller\Email',
                            'action'     => 'checkSubscription',
                        ),
                    ),
                ),
                'move_to_s3' => array(
                    'options' => array(
                        'route'    => 'move_to_s3 [--verbose|-v] <filetype>',
                        'defaults' => array(
                            'controller' => 'Common\FileHandling\Controller\FileHandling',
                            'action'     => 'moveToS3',
                        ),
                    ),
                ),
                'report_move_to_s3' => array(
                    'options' => array(
                        'route'    => 'report_move_to_s3 [--verbose|-v] <filename>',
                        'defaults' => array(
                            'controller' => 'Common\FileHandling\Controller\FileHandling',
                            'action'     => 'reportmoveToS3',
                        ),
                    ),
                ),
                'report_in_mail' => array(
                    'options' => array(
                        'route'    => 'report_in_mail [--verbose|-v] <id> <secured_key> <mail_id> <csv_files>',
                        'defaults' => array(
                            'controller' => 'Notification\Controller\Email',
                            'action'     => 'sendMailForCSVReport',
                        ),
                    ),
                ),
                'getSchemaInfo' => array(
                    'options' => array(
                        'route'    => 'getSchemaInfo',
                        'defaults' => array(
                            'controller' => 'Notification\Controller\Email',
                            'action'     => 'getSchemaInfo',
                        ),
                    ),
                ),
                'readUnitTestCSV' => array(
                    'options' => array(
                        'route'    => 'readUnitTestCSV',
                        'defaults' => array(
                            'controller' => 'Notification\Controller\Email',
                            'action'     => 'readUnitTestCSV',
                        ),
                    ),
                ),
            ),
        ),
    ),
);
