<?php
use Zend\Mvc\MvcEvent;

    /*Saving exception happends before DB connection  into log file-----start------*/
    $e->getParam('exception');
    $logger = new \Zend\Log\Logger();//create logger
    $fileName='error_log_'.date('Y-m-d').'.log';

    $writer = new \Zend\Log\Writer\Stream(dirname(dirname(dirname(__DIR__))).'/data/logs/'.$fileName, 'a');
if (substr(sprintf('%o', fileperms(dirname(dirname(dirname(__DIR__))).'/data/logs/'.$fileName)), -4) != '0766') {
    @chmod(dirname(dirname(dirname(__DIR__))).'/data/logs/'.$fileName, 0766);
}
    //create a file to write the exception
    $logger->addWriter($writer, 0);
    $logger->registerErrorHandler($logger);
    $logger->registerExceptionHandler($logger);
    register_shutdown_function(
        function () use ($logger) {
            if ($e = error_get_last()) {
                $logger->ERR($e['message'] . " in " . $e['file'] . ' line ' . $e['line']);
                $logger->__destruct();
            }
        }
    );
    /*---------End-----------*/
    /*Save exception happends after DB connection  into log file-----start------*/
    $sharedManager = $e->getApplication()->getEventManager()->getSharedManager();
    $sm = $e->getApplication()->getServiceManager();
    $sharedManager->attach(
        'Zend\Mvc\Application',
        'dispatch.error',
        function ($e) use ($sm) {
            if ($e->getParam('exception')) {
                $sm->get('Logger')->crit($e->getParam('exception'));
            }
        }
    );
    /*---------End-----------*/
    $eventManager->attach(
        MvcEvent::EVENT_DISPATCH, array(
        $this,
        'boforeDispatch'
        ), 100
    );
