<?php
return array(
    'controllers' => array(
        'invokables' => array(
            'SigTRACE\SignalCalendar\Controller\SignalCalendar' => 'SigTRACE\SignalCalendar\Controller\SignalCalendarController',
        ),
    ),
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'signalcalendar' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/signalcalendar[/:action][/:trackerId][/:form_id][/:archive_id][/:subaction_id][/:type][/:filter]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'tracker_id' => '[0-9]+',
                        'form_id' => '[0-9]+',
                        'archive_id' => '[0-9]+',
                        'subaction_id' => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'SigTRACE\SignalCalendar\Controller\SignalCalendar',
                        'action' => 'index',
                    ),
                ),
            ),
            'aws' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/aws[/:action][/:keyname][/:filename]',
                    'constraints' => array(
                    ),
                    'defaults' => array(
                        'controller' => 'SigTRACE\SignalCalendar\Controller\SignalCalendar',
                        'action' => 'index',
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'SigTRACE\SignalCalendar' => true
        ),
        'template_path_stack' => array(
            'signalcalendar' => __DIR__ . '/../view',
        ),
    ),
);