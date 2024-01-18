<?php
return array(
    'modules' => array(
        'Application',
        'Dashboard',
        'Tracker',
        'Client',
        'Notification',
        'Reports',
        'User',
        'Role',
        'Calendar',
        'Import',
        'Session'
    ),
    'module_listener_options' => array(
        'config_glob_paths'    => array(
            '../../../config/autoload/{,*.}{global,local}.php',
        ),
        'module_paths' => array(
            'module',
            'vendor',
        ),
    ),
);
