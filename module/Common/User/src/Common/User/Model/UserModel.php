<?php

namespace Common\User\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Session\Container\SessionContainer;

class UserModel
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

    /*
     * get all users for particular tracker
     */

    public function getAllUser($tracker_id)
    {
        $qry = 'SELECT * FROM user us
        left join user_role_tracker urt on urt.u_id=us.u_id
        left join `role` gp on gp.rid = urt.group_id where gp.tracker_id=? and user_archived=0 group by us.u_id';
        $statements = $this->_adapter->createStatement($qry, array($tracker_id));
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
        $statements->getResource()->closeCursor();
        return $arr;
    }
    
    /**
     * @to get all information of a user
     */
    public function getUserInfo($user_id)
    {
        $user_id=(int)$user_id;
        $qry = 'SELECT * FROM user us
        left join user_role_tracker urt on urt.u_id=us.u_id
        left join `role` gp on gp.rid=urt.group_id where us.u_id=?';
        $statements = $this->_adapter->createStatement($qry, array($user_id));
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
        $statements->getResource()->closeCursor();
        return $arr;
    }
    
    /*
     * get ldap and domain related value from setting table
     */

    public function getSetting()
    {
        $query="SELECT config_key,config_value FROM `config`";
        $statements = $this->_adapter->createStatement($query);
        $results = $statements->execute();
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
    
    /**
     * @return result object
     */
    public function getUserRoles($tracker_id)
    {
        $tracker_id = (int)$tracker_id;
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('role');
        $select->where(array('archived' => 0, 'tracker_id' => $tracker_id));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    
    /**
     * @to get all information of a user tracker wise
     */
    public function getUserInfoForTracker($user_id, $tracker_id)
    {
        $tracker_id = (int)$tracker_id;
        $user_id = (int)$user_id;
        $qry = 'SELECT urt.u_id,us.u_name,group_concat(gp.rid) as group_name,us.email,us.status,us.user_archived,us.user_type FROM user us
        left join user_role_tracker urt on urt.u_id=us.u_id
        left join `role` gp on gp.rid=urt.group_id where us.u_id=? and gp.tracker_id=?';
        $statements = $this->_adapter->createStatement($qry, array($user_id, $tracker_id));
        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arrData = $resultSet->toArray();
            $arr     = isset($arrData[0])?$arrData[0]:array();
        }
        $statements->getResource()->closeCursor();
        return $arr;
    }
    
    /*
    * Function to add or update user information
    */
    public function userAdd($dataArr)
    {    
        
        $uName = isset($dataArr['u_name'])?$dataArr['u_name']:'';
        $email = isset($dataArr['email'])?$dataArr['email']:'';
        $roleIds = isset($dataArr['role_id'])?$dataArr['role_id']:array();
        $userId = isset($dataArr['user_id'])?$dataArr['user_id']:0;
        $trackerId = isset($dataArr['t_hidden'])?$dataArr['t_hidden']:0;
        $userCheckQuery = 'SELECT DISTINCT us.u_id FROM user us
                           left join user_role_tracker urt on urt.u_id = us.u_id
                           left join `role` gp on gp.rid = urt.group_id 
                           where gp.tracker_id=? and us.user_archived=0 and (us.u_name=? OR email=?)';
        if ($userId > 0) {
            $userCheckQuery .= " and us.u_id != ".intval($userId);
        }
        $statement = $this->_adapter->createStatement($userCheckQuery, array($trackerId, $uName, $email));
        $statement->prepare();
        $result = $statement->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($result);
        $resultSet->buffer();
        $userCount = $resultSet->count();
        $resultArr = array();
        
        if ($userCount > 0) {
            $arr = $resultSet->toArray();
            $userId = $arr[0]['u_id'];
            $responseCode = 0;
            $errMessage = 'User already exists with same username or email'; 
        } else {
            $connection = null;
            switch (intval($userId)) {
            case 0:
                $queryClients = "SELECT * FROM user WHERE u_name = ? and user_archived = 0";
                $statement1 = $this->_adapter->createStatement($queryClients, array($uName));
                $statement1->prepare();
                $result1 = $statement1->execute();
                    
                $resultSet1 = new ResultSet;
                $resultSet1->initialize($result1);
                $resultSet1->buffer();
                $uCount = $resultSet1->count();
                    
                try { 
                    $connection = $this->_adapter->getDriver()->getConnection();
                    $connection->beginTransaction();
                    if ($uCount > 0) {
                        $arr = $resultSet1->toArray();
                        $userId = $arr[0]['u_id'];
                        $qry = "delete user_role_tracker from user_role_tracker
                                    LEFT JOIN `role` ON user_role_tracker.group_id = `role`.rid 
                                    Where user_role_tracker.u_id=? AND `role`.tracker_id = ?";
                        $statement2 = $this->_adapter->createStatement($qry, array($userId, $trackerId));
                        $statement2->prepare();
                        $statement2->execute();

                        //data removing from user_role_session
                        $qry = 'delete from user_role_session where u_id=? AND tracker_id =?';
                        $statement3 = $this->_adapter->createStatement($qry, array($userId, $trackerId));
                        $statement3->prepare();
                        $statement3->execute();

                        foreach ($roleIds as $roleId) { 
                            $statement4 = $this->_adapter->createStatement("insert into user_role_tracker(u_id, group_id) values(?,?)", array($userId, $roleId));
                            $statement4->prepare();
                            $statement4->execute();

                            $statement5 = $this->_adapter->createStatement("insert into user_role_session(u_id, tracker_id, role_id) values(?,?,?)", array($userId, $trackerId, $roleId));
                            $statement5->prepare();
                            $statement5->execute();
                        }
                        $connection->commit();
                        $responseCode = 1;
                        $errMessage = 'User Created Successfully';
                    } else {
                        $sql = new Sql($this->_adapter);
                        $insert = $sql->insert('user');
                        $newData = array(
                            'u_name' => $uName,
                            'email' => $email,
                            'u_realname'=>$uName,
                            'status' => 'Active',
                            'user_archived' => 0
                        );
                        $insert->values($newData);
                        $selectString = $sql->prepareStatementForSqlObject($insert);
                        $selectString->execute();
                        $userId = $this->_adapter->getDriver()->getLastGeneratedValue();
                        foreach ($roleIds as $roleId) {
                            $roleId = (int)$roleId;
                            $userId = (int)$userId;
                            $sql = new Sql($this->_adapter);
                            $insert = $sql->insert('user_role_tracker');
                            $newData = array(
                                'u_id' => $userId,
                                'group_id' => $roleId,
                            );
                            $insert->values($newData);
                            $selectString = $sql->prepareStatementForSqlObject($insert);
                            $selectString->execute();

                            $statement6 = $this->_adapter->createStatement("insert into user_role_session(u_id, tracker_id, role_id) values(?,?,?)", array($userId, $trackerId, $roleId));
                            $statement6->prepare();
                            $statement6->execute(); 
                        }
                        $connection->commit();
                        $responseCode = 1;
                        $errMessage = "User Created Successfully";
                    }
                } catch(\Exception $e) {
                    if ($connection instanceof ConnectionInterface) {
                        $connection->rollback();
                    }
                    $responseCode = 0;
                    $errMessage = 'Error While inserting User'; 
                } catch(\PDOException $e) {
                    if ($connection instanceof ConnectionInterface) {
                        $connection->rollback();
                    }
                    $responseCode = 0;
                    $errMessage = 'Error While inserting User'; 
                }
                break;
            default:
                try {
                    $connection = $this->_adapter->getDriver()->getConnection();
                    $connection->beginTransaction();
                    if (!empty($roleIds)) {
                        $qry = "delete user_role_tracker from user_role_tracker
                                    LEFT JOIN `role` ON user_role_tracker.group_id = `role`.rid 
                                    Where user_role_tracker.u_id=? AND `role`.tracker_id = ?";
                        $statement7 = $this->_adapter->createStatement($qry, array($userId, $trackerId));
                        $statement7->prepare();
                        $statement7->execute();

                        //data removing from user_role_session
                        $qry = 'delete from user_role_session where u_id = ? AND tracker_id = ?';
                        $statement8 = $this->_adapter->createStatement($qry, array($userId, $trackerId));
                        $statement8->prepare();
                        $statement8->execute();
                    }
                        
                    $statement9 = $this->_adapter->createStatement("update user set `email`=? where `u_id`=?", array($email, $userId));
                    $statement9->prepare();
                    $statement9->execute();

                    foreach ($roleIds as $roleId) { 
                        $roleId = (int)$roleId;
                        $statement10 = $this->_adapter->createStatement("insert into user_role_tracker(u_id,group_id) values(?,?)", array($userId, $roleId));
                        $statement10->prepare();
                        $statement10->execute();

                        $statement11 = $this->_adapter->createStatement("insert into user_role_session(u_id, tracker_id, role_id) values(?,?,?)", array($userId, $trackerId, $roleId));
                        $statement11->prepare();
                        $statement11->execute();
                    }
                    $connection->commit();
                    $responseCode = 2;
                    $errMessage = 'User Updated Successfully';
                } catch(\Exception $e) {
                    if ($connection instanceof ConnectionInterface) {
                        $connection->rollback();
                    }
                    echo $e->getMessage();
                    $responseCode = 0;
                    $errMessage = 'Error while updating User'; 
                } catch(\PDOException $e) {
                    if ($connection instanceof ConnectionInterface) {
                        $connection->rollback();
                    }
                    echo $e->getMessage();
                    $responseCode = 0;
                    $errMessage = 'Error While updating User'; 
                }
                break;
            } 
        }
        $resultsArr['user_id'] = $userId;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    
    
    /*
     * Function to delete settings :to make settings archive
     */

    public function deleteUser($user_id, $tracker_id)
    {
        $tracker_id = (int)$tracker_id;
        $user_id = (int)$user_id;
        $connection = null;
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            $query = "DELETE FROM 
                        user_role_tracker
                        USING 
                        user_role_tracker 
                        JOIN `role` ON user_role_tracker.group_id=`role`.rid 
                        WHERE `role`.tracker_id = ? and user_role_tracker.u_id = ?";
            $statements = $this->_adapter->createStatement($query, array($tracker_id, $user_id));
            $statements->prepare();
            $statements->execute();
            $connection->commit();
            $responseCode = 1;
            $errMessage = 'User Deleted Successfully';
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While deleting User'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While deleting User'; 
        }
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    
    public function getTrackerDetails($trackerId) 
    {
        $trackerId = (int)$trackerId;
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('tracker')->join('client', 'tracker.client_id = client.client_id', array('client_name'));
        $select->where(array('tracker_id' => $trackerId, 'tracker.archived' => 0));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute(); 
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arrData = $resultSet->toArray();
            $arr     = isset($arrData[0])?$arrData[0]:array();
        }
        return $arr;
    }
    
    public function trackerResults($trackerId)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
        $trackerContainer = $session->getSession('tracker');
        $trackerId = (int)$trackerId;
        $userDetails = $userContainer->user_details;
        $roleId = (int)$userDetails['group_id'];
        $roleName = $userDetails['group_name'];
        if (isset($trackerContainer->tracker_user_groups) && $roleName != 'SuperAdmin' && $trackerId != 0) {
            $trackerUserGroups = $trackerContainer->tracker_user_groups;
            if (!array_key_exists($trackerId, $trackerUserGroups)) {
                $applicataionModel= new \Application\Model\AdminMapper($this->_adapter);
                $applicataionModel->accessTrackerGroups($userDetails['u_id'], $roleId, $userDetails['user_type']);
                $trackerUserGroups = $trackerContainer->tracker_user_groups;
            }
            $roleName = $trackerUserGroups[$trackerId]['session_group'];
            $roleId = $trackerUserGroups[$trackerId]['session_group_id'];
        }
        $roleId = (int)$roleId;
        if ($roleName != 'SuperAdmin' && $roleName != 'Administrator') {
            $queryTracker = "SELECT tracker . tracker_id,  form.form_id, form.form_name
            FROM tracker
            JOIN form ON tracker.tracker_id = form.tracker_id
            Join form_access_setting on form_access_setting.form_id=form.form_id
            and form_access_setting.role_id=$roleId
            and form_access_setting.can_read='Yes'
            WHERE tracker.tracker_id = ?";
        } else {
            $queryTracker = "SELECT tracker . tracker_id,  form.form_id, form.form_name
            FROM tracker
            JOIN form ON tracker.tracker_id = form.tracker_id
            WHERE tracker.tracker_id = ?";
        }
        $statements = $this->_adapter->createStatement($queryTracker, array($trackerId));
        $statements->prepare();
        $resArr = array();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            $resArr['forms'] = $arr;
        }

        $queryTracker = "SELECT * FROM tracker WHERE tracker_id = ?";
        $statements = $this->_adapter->createStatement($queryTracker, array($trackerId));
        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            $resArr['tracker_details'] = $arr[0];
        }
        return $resArr;
    }  
}
