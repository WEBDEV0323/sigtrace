<?php

namespace Application\Model;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Session\Container\SessionContainer;

class AdminMapper extends AbstractActionController
{
    protected $_adapter;
    protected $_serviceLocator;

    /**
     * Make the Adapter object avilable as local prtected variable
     *
     * @param Adapter $adapter - DB PDO PgSQL conn
     */
    public function __construct(Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }
    
    /**
     * Function used to get instance of DB
     *
     * @return instance
     */
    public function dbConnection()
    {
        return $this->getServiceLocator()->get('db')->getDriver()->getConnection();
    }

    
    /**
     * @return result object
     */
    public function userLogin($dataArr)
    {
        $session = new SessionContainer();     
        $unique_name = explode('\\', $dataArr['unique_name']);
        $uName= $unique_name[1];
        $email = $dataArr['upn'];
        $configData=$this->getOauthParams();
        $iss=$configData['issuer'];  
        $errMessage = "";
        if ($dataArr['iss'] == $iss) {
            $queryClients = "SELECT user.*, user_role_tracker.group_id, `role`.role_name, role.super_admin
                FROM user
                LEFT JOIN user_role_tracker ON user.u_id = user_role_tracker.u_id
                LEFT JOIN `role` ON `role`.rid = user_role_tracker.group_id
                WHERE user.u_name = ? and email = ? and user.status=?";
            $uName = htmlspecialchars($uName, ENT_QUOTES);
            $statements = $this->_adapter->createStatement($queryClients, array($uName,$email,'Active'));
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            $resultsArr = array();
            if ($count > 0) {
                    $userDetails = $resultSet->toArray()[0];
                    $responseCode = 200;
                    $errMessage = "Login Success";
                    $udetailsArr = array();
                    $udetailsArr['u_id'] = $userDetails['u_id'];
                    $udetailsArr['u_realname'] = $userDetails['u_realname'];
                    $udetailsArr['u_name'] = $userDetails['u_name'];
                    $udetailsArr['email'] = $userDetails['email'];
                    $udetailsArr['group_id'] = $userDetails['group_id'];
                    $udetailsArr['group_name'] = $userDetails['role_name'];
                    $udetailsArr['user_type'] = $userDetails['user_type'];
                    
                    $session->setSession(
                        'user', 
                        array("user_details"=>$udetailsArr, 
                                  "u_id" => $userDetails['u_id'],
                                  "u_realname" => $userDetails['u_realname'],
                                  "user_type" => $userDetails['user_type']
                            )
                    );
                    $userSession = $session->getSession('user');
                    $userSession->offsetSet('email', $userDetails['email']);
                    $userSession->offsetSet('roleName', $userDetails['role_name']);
                    $userSession->offsetSet('roleNameType', $userDetails['super_admin']);
                    $this->accessTrackerGroups($userDetails['u_id'], $userDetails['group_id'], $userDetails['user_type']);                    
            } else {
                $responseCode = 400;
                $errMessage = "Please Contact Administrator";
            }
        } else {
                $responseCode = 400;
                $errMessage = 'Invalid token';
        }
        $resultsArr['statusCode'] = $responseCode;
        $resultsArr['responseMessage'] = $errMessage;
        return $resultsArr;
    }

    public function accessTrackerGroups($uId, $roleId, $userType)
    {
        $session = new SessionContainer();
        if ($roleId != 1) {
            $queryTracker = "SELECT user. u_id, user_role_tracker.group_id, `role`.role_name, role.tracker_id
            FROM user
            LEFT JOIN user_role_tracker ON user.u_id = user_role_tracker.u_id
            LEFT JOIN `role` ON role.rid = user_role_tracker.group_id
            LEFT JOIN `tracker` ON tracker.tracker_id = role.tracker_id
            WHERE user.u_id = ?";
            $statements = $this->_adapter->createStatement($queryTracker, array($uId));
            $results = $statements->execute();
            $arrGroups = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arrGroups = $resultSet->toArray();
            }

            $groupAccess = array();
            foreach ($arrGroups as $key => $value) {
                $trackerId = $value['tracker_id'];
                $groupName = $value['role_name'];
                unset($value['tracker_id']);
                unset($value['u_id']);
                $groupAccess[$trackerId]['groups'][] = $value;
            }

            $queryTracker = "SELECT user_role_session . * , role.role_name
            FROM user_role_session
            LEFT JOIN `role` ON user_role_session.role_id = role.rid
                    WHERE user_role_session.u_id = ?";
            $statements = $this->_adapter->createStatement($queryTracker, array($uId));
            $results = $statements->execute();
            $arrGroupsSession = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();

            if ($count > 0) {
                $arrGroupsSession = $resultSet->toArray();
                foreach ($arrGroupsSession as $key => $value) {
                    $groupId = $value['role_id'];
                    $trackerId = $value['tracker_id'];
                    $groupName = $value['role_name'];
                    $groupAccess[$trackerId]['session_group_id'] = $groupId;
                    $groupAccess[$trackerId]['session_group'] = $groupName;
                }
            } else {
                $groupAccess[$trackerId]['session_group_id'] = 0;
                $groupAccess[$trackerId]['session_group'] = "Viewer";
            }
            $session->setSession('tracker', array("tracker_user_groups"=>$groupAccess));
            //$trackerContainer->tracker_user_groups = $groupAccess;
        }
    }

    public function userLogout()
    {
        //$container = new Container('user');
        //$trackerContainer = new Container('tracker');
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        $userDetails = $userContainer->user_details ;
        $uId = $userContainer->u_id ;
        $trackerUserGroups =  $trackerContainer->tracker_user_groups;
        if (is_array($trackerUserGroups) && count($trackerUserGroups) > 0) {
            foreach ($trackerUserGroups as $key => $value) {
                $trackerId = $key;
                $sessionGroupId = $value['session_group_id'];
                if ($sessionGroupId == 0) {
                    $sql = new Sql($this->_adapter);
                    $select = $sql->select();
                    $select->from('role');
                    $select->where(array('role_name' => 'Viewer', 'tracker_id' => $trackerId));
                    $newadpater = $this->_adapter;
                    $selectString = $sql->getSqlStringForSqlObject($select);
                    $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
                    $arr = array();
                    $resultSet = new ResultSet;
                    $resultSet->initialize($results);
                    $resultSet->buffer();
                    $count = $resultSet->count();
                    if ($count > 0) {
                        $arr = $resultSet->toArray();
                        $resultSArr = $arr[0];
                        $sessionGroupId = $resultSArr['rid'];
                    }
                }
                $sql = new Sql($this->_adapter);
                $newData = array(
                    'role_id' => $sessionGroupId,
                );
                $update = $sql->update('user_role_session')
                    ->set($newData)
                    ->where(array('u_id' => $uId, 'tracker_id' => $trackerId));
                $selectString = $sql->getSqlStringForSqlObject($update);
                $results = $this->_adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
            }
        }
    }
    public function createHash($password, $salt, $iterations, $length)
    {
        return bin2hex(hash_pbkdf2("sha256", $password, $salt, $iterations, $length, true));
    }
    
    public function getOauthParams()
    {
        $query="SELECT config_key,config_value FROM `config`";
        $statements = $this->_adapter->createStatement($query);
        $results = $statements->execute();
        $res = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $res=array_column($resultSet->toArray(), 'config_value', 'config_key');       
        }
        return $res;
    }
    
    public function dashboardResults()
    {
        $session = new SessionContainer();
        $container = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        $userDetails = $container->user_details ;
        $uId = $container->u_id;
        $roleId = $userDetails['group_id'];
        $queryTracker = "SELECT DISTINCT user_role_tracker . u_id , tracker.name, tracker.tracker_id
            FROM user_role_tracker
            JOIN `role` ON role.rid = user_role_tracker.group_id
            LEFT JOIN tracker ON role.tracker_id = tracker.tracker_id
            WHERE user_role_tracker.u_id = $uId AND tracker.archived=?";
        $statements = $this->_adapter->createStatement($queryTracker, array('0'));
        if ($roleId == 1) {
            $queryTracker = "SELECT tracker.*  FROM tracker join `client` on `tracker`.client_id=`client`.client_id where `tracker`.archived=? AND `client`.archived=?";
            $statements = $this->_adapter->createStatement($queryTracker, array('0', '0'));
        }

        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $retArr['trackerCounts'] = $count;
        $retArr['trackers'] = $arr;
        $trackerIds = array();
        foreach ($arr as $key => $value) {
            $trackerIds[] = $value['tracker_id'];
            $statements = $this->_adapter->createStatement("SELECT form_id FROM form WHERE tracker_id = ? LIMIT 1", array($value['tracker_id']));
            $statements->prepare();
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            $formId = 0;
            if ($count > 0) {
                $Id = $resultSet->toArray();
                $formId = $Id[0]['form_id'];
            }
            $retArr['trackers'][$key]['form_id'] = $formId;
        }
        $trackerContainer->tracker_ids = $trackerIds;        
        return $retArr;
    }
    public function getDashboardUrl()
    {
        $query="SELECT dependent_on, action_url FROM `permission` WHERE LOWER(is_dashboard) = 'yes'";
        $statements = $this->_adapter->createStatement($query);
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $arr = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function getConfigData($scopeLevel = 'Global', $scopeId = null)
    {
        $query="SELECT config_key,config_value FROM `config` WHERE scope_level = '$scopeLevel'";
        if ($scopeLevel != 'Global') {
            $query.=" AND scope_id = $scopeId";
        }
        $statements = $this->_adapter->createStatement($query);
        $statements->prepare();
        $results = $statements->execute();
        $arrGroupsSession = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $res=array_column($arr, 'config_value', 'config_key');       
        return $res;
    }
}
