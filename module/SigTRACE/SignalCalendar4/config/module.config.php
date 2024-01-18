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
            'sig-signalcalendar' => array(
                'type' => 'segment',
                'options' => array(
                    'route' => '/sig/signalcalendar[/:action][/:trackerId][/:form_id][/:archive_id][/:subaction_id][/:type][/:filter]',
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
        ),
    ),
    'view_manager' => array(
        'controller_map' => array(
            'SigTRACE\SignalCalendar' => true
        ),
        'template_path_stack' => array(
            'sig-SignalCalendar' => __DIR__ . '/../view',
        ),
    ),
);