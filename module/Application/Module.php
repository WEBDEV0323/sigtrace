<?php
namespace Application;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\ModuleManager;
use Zend\ModuleManager\ModuleEvent;
use Application\Factory\AppAdapter;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Session\Container\SessionContainer;
use Common\Authorization\Utility\Acl;
use Zend\Console\Request as ConsoleRequest;

class Module implements AutoloaderProviderInterface
{
    public function init(ModuleManager $moduleManager)
    {
        $events = $moduleManager->getEventManager();
        // Registering a listener at default priority, 1, which will trigger
        // after the ConfigListener merges config.
        $events->attach(ModuleEvent::EVENT_MERGE_CONFIG, array($this, 'onMergeConfig'));

    }

    public function onMergeConfig(ModuleEvent $e)
    {
        $configListener = $e->getConfigListener();
        $config = $configListener->getMergedConfig(false); 
        
        $db = $this->getTraceDb($config);
        if (!empty($db)) {
            $config['trace']['dsn'] = 'mysql:dbname='. $db['customer_db'] .';host='.$db['host'];
            $config['trace']['db'] = $db['customer_db'] ;
            $config['trace']['username'] = $db['customer_username'];
            $config['trace']['password'] = $db['customer_password'];
        }
        $configListener->setMergedConfig($config);
    }

    public function getTraceDb($config)
    {
        $adapter = new Adapter($config['db']);
        $query = "select customer_db, customer_username, customer_password,host from customer WHERE LOWER(`status`) = 'active' AND customer_name = '".$config['appurl']['customer']."'";
        $result = $adapter->query($query, Adapter::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($result);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray()[0];
        }
        return $arr;
    }
 
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        return include __DIR__ . '/config/logs.config.php';
        
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'trace' => new AppAdapter('trace'),
                'Acl' => function ($sm) {
                    return new Acl();
                },
                'RoleTable' => function ($sm) {
                    return new \Common\Authorization\Model\Role($sm->get('trace'));
                },
                'ControllerTable' => function ($sm) {
                    return new \Common\Authorization\Model\ControllerTable($sm->get('trace'));
                },
                'RolePermissionTable' => function ($sm) {
                    return new \Common\Authorization\Model\RolePermissionTable($sm->get('trace'));
                },
                'Logger' => function ($sm) {
                    $logger = new \Zend\Log\Logger;
                    $fileName='error_db_'.date('Y-m-d').'.log';
                    $writer = new \Zend\Log\Writer\Stream(dirname(dirname(__DIR__)).'/data/Db/'.$fileName, 'a');
                    if (substr(sprintf('%o', fileperms(dirname(dirname(__DIR__)).'/data/Db/'.$fileName)), -4) != '0766') {
                             @chmod(dirname(dirname(__DIR__)).'/data/logs/'.$fileName, 0766);
                    }
                    $logger->addWriter($writer);
                    return $logger;
                },
                
                'Application\Model\AdminMapper' => function ($sm) {
                    return new Model\AdminMapper($sm->get('trace'));
                },
                'Application\Model\Helper\ApplicationHelper' => function ($sm) {
                    return new Model\Helper\ApplicationHelper($sm->get('trace'));
                },
                'Audit\Service' => function ($sm) {
                    return new \Common\Audit\Model\Audit($sm->get('trace'));
                }
            ),
        );
    }
    
    public function getViewHelperConfig()
    {
        return array(
           'factories' => array(
               'AppHelper' => function ($sm) {
                   $locator = $sm->getServiceLocator();
                   return new \Application\View\Helper\ApplicationHelper($locator->get('Application\Model\Helper\ApplicationHelper'));
               },

           ),
        );
    }
    
    function boforeDispatch(MvcEvent $event)
    {
        $request = $event->getRequest();
        if (!$request instanceof ConsoleRequest) {
            $response = $event->getResponse();
            $whiteList = array(
                'Application\Controller\Index-healthcheck',
                'Authentication\Controller\Authentication-index',
                'Authentication\Controller\Authentication-oauthredirect',
                'Authentication\Controller\Authentication-errorpage',
                'Authentication\Controller\Authentication-logout',
                'Workflow\Controller\Workflow-popup'
            );
            $controller = $event->getRouteMatch()->getParam('controller');
            $action = $event->getRouteMatch()->getParam('action');
            $trackerId =  $event->getRouteMatch()->getParam('trackerId', 0);
            $requestedResourse = $controller . "-" . $action;
            
            $viewModel = $event->getApplication()->getMvcEvent()->getViewModel();
            $viewModel->isHavingPermission = true;
            $session = new SessionContainer();
            $userSession = $session->getSession('user');
            $trackerRoles = ($userSession->trackerRoles != '' && !empty($userSession->trackerRoles))?$userSession->trackerRoles:array();
            if (isset($userSession->u_id) && $userSession->u_id != '' && !($request->isXmlHttpRequest())) {
                if (in_array($requestedResourse, $whiteList)) {
                    $response->setHeaders($response->getHeaders()->addHeaderLine('Location', '/tracker'));
                    $response->setStatusCode(302);
                } else {
                    $serviceManager = $event->getApplication()->getServiceManager();
                    $userRole = ((!empty($trackerRoles)) && isset($trackerRoles[$trackerId]['sessionRoleName']))?$trackerRoles[$trackerId]['sessionRoleName']: $userSession->offsetGet('roleName');
                    $userRoleType = ((!empty($trackerRoles)) && isset($trackerRoles[$trackerId]['sessionRoleType']))?$trackerRoles[$trackerId]['sessionRoleType']:$userSession->offsetGet('roleNameType');
                    
                    $status = true;
                    if ($userRoleType != 1 && $requestedResourse != 'Tracker\Controller\Tracker-index') {
                        $acl = $serviceManager->get('Acl');
                        $acl->initAcl($trackerId); 
                        $status = $acl->isAccessAllowed($userRole, $controller, $action);
                    }
                    $viewModel->isHavingPermission = $status;
                }
            } else {
                if (!in_array($requestedResourse, $whiteList) && !($request->isXmlHttpRequest())) {
                    $response->setHeaders($response->getHeaders()->addHeaderLine('Location', '/auth'));
                    $response->setStatusCode(302);
                }
                $response->sendHeaders();
            }
        }
    }
}
