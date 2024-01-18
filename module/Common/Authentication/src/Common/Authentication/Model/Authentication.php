<?php

namespace Common\Authentication\Model;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Session\Container\SessionContainer;

class Authentication extends AbstractActionController
{
    protected $_adapter;

    /**
     * Make the Adapter object available as local protected variable
     *
     * @param Adapter $adapter - DB PDO PgSQL conn
     */
    public function __construct(Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }
    
    public function getConfigParams()
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('config');
        $select->columns(array('config_key', 'config_value'));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = array_column($resultSet->toArray(), 'config_value', 'config_key');
        }
        return $arr;
    }
    
    public function login($dataArr)
    {
        $session = new SessionContainer();     
        $unique_name = explode('\\', $dataArr['unique_name']);
        $uName= $unique_name[1];
        $email = $dataArr['upn'];
        $configData = $this->getConfigParams();  
        $errMessage = "";
        if (filter_var($dataArr['upn'], FILTER_VALIDATE_EMAIL) ) {            
            $domainName = explode('@', $dataArr['upn']);
            if ($domainName[1] == $configData['domain_name']) {
                $email = $dataArr['upn'];                   
            } else {
                $email='';
            }            
        } else {
            $email='';
        }
        if ($dataArr['iss'] == $configData['issuer']) {
            if ($email != '') {
                $query = "SELECT user.u_id, user.u_name, user.u_realname, user.email
                    FROM user
                    WHERE user.u_name = ? and email = ? and user.status=?";
                $uName = htmlspecialchars($uName, ENT_QUOTES);
                $statements = $this->_adapter->createStatement($query, array($uName, $email, 'Active'));
            } else {
                $query = "SELECT user.u_id, user.u_name, user.u_realname, user.email
                    FROM user
                    WHERE user.u_name = ? and user.status=?";
                $uName = htmlspecialchars($uName, ENT_QUOTES);
                $statements = $this->_adapter->createStatement($query, array($uName,'Active'));                
            }
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            $resultsArr = $rolesUponTrackers = array();
            if ($count > 0) {
                    $userDetails = $resultSet->toArray()[0];
                    $isSuperAdmin = $this->isSuperAdmin($userDetails['u_id']);
                    $responseCode = 200;
                    $errMessage = "Login Success";
                    $udetailsArr = array();
                    $udetailsArr['u_id'] = $userDetails['u_id'];
                    $udetailsArr['u_name'] = $userDetails['u_name'];
                    $udetailsArr['email'] = $userDetails['email'];
                    $udetailsArr['group_id'] = 0;
                    $udetailsArr['group_name'] = "guest";
                if (!empty($isSuperAdmin)) {
                    $udetailsArr['group_id'] = $isSuperAdmin['rid'];
                    $udetailsArr['group_name'] = $isSuperAdmin['role_name'];
                }
                    $userRoleType = ($udetailsArr['group_id'] != 0)? 1: 0;
                    $udetailsArr['isSuperAdmin'] = $userRoleType;
                if ($userRoleType == 0) {
                    $rolesUponTrackers = $this->accessUserRolesByTrackers($userDetails['u_id']);
                }
                    
                    $session->setSession(
                        'user', 
                        array("user_details"=>$udetailsArr, 
                              "u_id" => $userDetails['u_id'],
                              "trackerRoles" => $rolesUponTrackers
                        )
                    );
                    $userSession = $session->getSession('user');
                    $userSession->offsetSet('email', $userDetails['email']);
                    $userSession->offsetSet('roleName', $udetailsArr['group_name']);
                    $userSession->offsetSet('roleNameType', $userRoleType);
                    
                    $this->accessTrackerGroups($udetailsArr['u_id'], $udetailsArr['group_id']);                    
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
    
    public function isSuperAdmin($uId)
    {
        $data = array();
        $query = "SELECT `role`.rid, `role`.role_name
                    FROM `role`
                    INNER JOIN `user_role_tracker` ON `role`.rid = `user_role_tracker`.group_id
                    INNER JOIN `user` ON `user`.u_id = `user_role_tracker`.u_id
                    WHERE user.u_id = ? AND `role`.super_admin = ?";
        
        $statements = $this->_adapter->createStatement($query, array($uId, '1'));
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $data = $resultSet->toArray()[0];
        }
        return $data;
    }
    public function accessUserRolesByTrackers($uId)
    {
        
        $groupAccess = array();
        $query = "SELECT `user_role_tracker`.group_id as role_id, `role`.role_name, `role`.tracker_id, `role`.super_admin
            FROM `user`
            LEFT JOIN `user_role_tracker` ON `user`.u_id = `user_role_tracker`.u_id
            LEFT JOIN `role` ON `role`.rid = `user_role_tracker`.group_id
            LEFT JOIN `tracker` ON `tracker`.tracker_id = `role`.tracker_id
            WHERE `user`.u_id = ? AND `role`.archived = ? ORDER BY `role`.role_name";
        $statements = $this->_adapter->createStatement($query, array($uId, '0'));
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            foreach ($resultSet->toArray() as $value) {
                $trackerId = isset($value['tracker_id'])?$value['tracker_id']:0;
                unset($value['tracker_id']);
                $groupAccess[$trackerId]['roles'][] = $value;
            }
        }
        $querySession = "SELECT role.rid, role.role_name, role.super_admin, user_role_session.tracker_id
            FROM `role`
            LEFT JOIN `user_role_session` ON user_role_session.role_id = role.rid
            WHERE `user_role_session`.u_id = ? AND `role`.archived = ?";
        $statements1 = $this->_adapter->createStatement($querySession, array($uId, '0'));
        $results1 = $statements1->execute();
        $resultSet1 = new ResultSet;
        $resultSet1->initialize($results1);
        $resultSet1->buffer();
        $count1 = $resultSet1->count();

        if ($count1 > 0) {
            foreach ($resultSet1->toArray() as $value) {
                $trackerId = isset($value['tracker_id'])?$value['tracker_id']:0;
                $groupAccess[$trackerId]['sessionRoleId'] = $value['rid'];
                $groupAccess[$trackerId]['sessionRoleName'] = $value['role_name'];
                $groupAccess[$trackerId]['sessionRoleType'] = $value['super_admin'];
            }
        }
        return $groupAccess;
    }

    public function accessTrackerGroups($uId, $roleId)
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
        }
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
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('action');
        $select->columns(array('dependent_on', 'action_url'));
        $select->where(array('is_dashboard'=>'Yes'));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray()[0];
        }
        return $arr;
    }
    
    public function logout()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        $uId = isset($userContainer->u_id)?$userContainer->u_id:0 ;
        $trackerUserGroups =  isset($trackerContainer->tracker_user_groups)?$trackerContainer->tracker_user_groups:array();
        if (is_array($trackerUserGroups) && count($trackerUserGroups) > 0) {
            foreach ($trackerUserGroups as $key => $value) {
                $trackerId = $key;
                $sessionGroupId = $value['session_group_id'];
                if ($sessionGroupId == 0) {
                    $sql = new Sql($this->_adapter);
                    $select = $sql->select();
                    $select->from('role');
                    $select->where(array('role_name' => 'Viewer', 'tracker_id' => $trackerId));
                    $selectString = $sql->getSqlStringForSqlObject($select);
                    $results = $this->_adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
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
}

