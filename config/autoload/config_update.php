<?php

/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */
$_SERVER["HTTP_HOST"] = (isset($_SERVER["HTTP_HOST"])) ? $_SERVER["HTTP_HOST"] :'';
    $protocol= "{{HTTP_PROTOCOL}}";
    $defaultUrl = "{{APPURL}}".'/';
    $base_url = $protocol.$_SERVER['HTTP_HOST'].'/';
    if (strcmp($base_url, $defaultUrl) !== 0) {
        $parseUrl=parse_url($base_url);
        $parseUrlArray=isset($parseUrl['host'])?explode(".",$parseUrl['host']):array(0 => '');
        $customer=($parseUrlArray[0]!="")?$parseUrlArray[0]:"{{DEFAULT_DB}}"; //"unittest";
    } else {
        $customer="{{DEFAULT_DB}}";        
    }
return array(
    'db' => array(
        'driver' => 'Pdo',
        'dsn' => 'mysql:dbname={{RDSDBNAME}};host={{RDSENDPOINT}}',
        'username' => "{{RDSUSERNAME}}", //here I added my valid username
        'password' => "{{RDSSECRET}}", //here I added my valid password", //here I added my valid username
        'driver_options' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
        ),
    ),
    'trace' => array (
        'driver'         => 'Pdo',
        'driver_options' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''
        )
    ),
    'service_manager' => array(
        'factories' => array(
            'Zend\Db\Adapter\Adapter'
            => 'Zend\Db\Adapter\AdapterServiceFactory',
        ),
    ),
    'aws' => array(
        'region' => '{{REGION}}',
        'credentials' => array(
            'access_key_id'=> '{{AWS_ACCESS_KEY_ID}}',
            'secret_access_key'=> '{{ACCESS_KEY}}',
        ),
        's3' => array(
            'bucket_name'=> '{{BUCKET_NAME}}',
        ),
    ),
    'kibana' => array(
        'url' => array(
            'elasticsearch'=> "{{ES_HOST}}", 
            'kibana_url'=> "{{KIBANAURL}}"            
        ),
    ),
    'appurl' => array(
        'url' => $defaultUrl,
        'path' => '{{APP_PATH}}',
        'customer' => $customer,
    ),
    'group' => array(
        'group_id' => '{{GROUP_ID}}'
    ),
    'logo' => array(
        'logo_file_name' => $defaultUrl.'{{LOGO}}'
    ),
    'session' => array(
        'config' => array(
            'class' => 'Zend\Session\Config\SessionConfig',
            'options' => array(
                'name' => 'asrtrace',
            ),
        ),
        'storage' => 'Zend\Session\Storage\SessionArrayStorage',
        'validators' => array(
            'Zend\Session\Validator\RemoteAddr',
            'Zend\Session\Validator\HttpUserAgent',
        ),
    ),
);
