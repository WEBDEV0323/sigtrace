<?php

namespace ASRTRACE\Tracker\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;

class UserModule
{
    protected $_userServiceLocator;

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
     * @return result object
     */
    public function getRoleForTracker($tracker_id)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('group');
        $select->where(array('tracker_id' => $tracker_id, 'group_archived' => 0));
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
        $newdata =  array(
            'group_id' => 'CurrentUser',
            'group_name' => 'CurrentUser',
            'tracker_id' => $tracker_id
        );
        $arr[count($arr)]=$newdata;
        return $arr;
    }
    /**
     * @return result object
     */
    public function getGroupInfo($group_id)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('group');
        $select->where(array('group_id' => $group_id, 'group_archived' => 0));
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

    /*
     * function to add or update group for tracker
     */

    public function savegroup($data, $group_id, $tracker_id)
    {
        $sql = new Sql($this->_adapter);
        $newData = array(
            'group_name' => $data['c_name'],
            'tracker_id' => $tracker_id);

        if ($group_id > 0) {
            $update = $sql->update('group')
                ->set($newData)
                ->where(
                    array(
                    'group_id' => $group_id,
                    'group_archived' => 0
                    )
                );

            $selectString = $sql->prepareStatementForSqlObject($update);
        } else {
            $insert = $sql->insert('group');
            $insert->values($newData);
            $selectString = $sql->prepareStatementForSqlObject($insert);
        }
        $results = $selectString->execute();
        return $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
    }

    /*
     * while inserting into group table check for duplicate
     */

    public function checkDuplicate($post, $group_id, $tracker_id, $operation)
    {
        /*
          3=>already axists
         */
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        if ($operation == 'group') {
            $qry = 'call sp_checkifexists("' . trim(addslashes($post['c_name'])) . '","' . trim(addslashes($group_id)) . '","' . trim(addslashes($tracker_id)) . '","' . trim(addslashes($operation)) . '")';
        } elseif ($operation == 'user') {
            $qry = 'call sp_checkifexists("' . trim(addslashes($post['c_name'])) . '","' . trim(addslashes($group_id)) . '","' . trim(addslashes($tracker_id)) . '","' . trim(addslashes($operation)) . '")';
        }
        $statements = $this->_adapter->query($qry);
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
     * Function to delete settings :to make settings archive
     */

    public function deleteGroup($group_id)
    {
        $sql = new Sql($this->_adapter);
        $update = $sql->update('group')
            ->set(array('group_archived' => 1))
            ->where(
                array(
                'group_id' => $group_id
                )
            );
        $selectString = $sql->prepareStatementForSqlObject($update);
        $results = $selectString->execute();
    }

    /*
     * get all users for particular tracker
     */

    public function getAllUser($tracker_id)
    {
        $qry = 'SELECT * FROM user us
        left join user_role_tracker urt on urt.u_id=us.u_id
        left join `group` gp on gp.group_id=urt.group_id where gp.tracker_id=? and user_archived=0 group by us.u_id';
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
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
        $qry = 'SELECT * FROM user us
        left join user_role_tracker urt on urt.u_id=us.u_id
        left join `group` gp on gp.group_id=urt.group_id where us.u_id=?';
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
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

    /**
     * @return result object
     */
    public function getAllGroupName($tracker_id)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('group');
        $select->where(array('group_archived' => 0, 'tracker_id' => $tracker_id));
        $newadpater = $this->_adapter;
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

    /*
     * function to add or update group for tracker
     */

    public function saveusers($data, $userId, $trackerId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        if ($userId > 0) {
            $uname = $data['c_name'];
            $urealName = $data['c_real_name'];
            $email = $data['c_email'];
            $role = $data['c_role'];
            $active = $data['c_status'];
            $archived = $data['c_archive'];
        } else {
            $uname = $data['c_name'];
            $urealName = $data['c_real_name'];
            $email = $data['c_email'];
            $role = $data['c_role'];
            $active = 'Active';
            $archived = 0;
        }

        $qry = 'call sp_saveUsers("' . $uname . '","' . $urealName . '","' . $email . '","' . $role . '","' . $active . '","' . $archived . '","' . $userId . '","' . $trackerId . '")';
        $statements = $this->_adapter->query($qry);
        $results = $statements->execute();
    }

    public function userAdd($dataArr)
    {
        $clientsesults = 0;
        $uName = $dataArr['u_name'];
        $roleIds = $dataArr['role_id'];
        $userId = $dataArr['user_id'];
        $type=$dataArr['type'];
        if ($type == 'LDAP') {
            $uName=explode('@', $uName);
            $uName=$uName[0];
        }
        $status = $dataArr['status'];
        $archived = $dataArr['archived'];
        $trackerId = $dataArr['t_hidden'];
        $key=$dataArr['key'];
        $salt=$dataArr['salt'];
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('settings')->where(array("status" => "Yes"));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            $resultSArr = $arr[0];
            $ldapUser = $resultSArr['ldap_user'];
            $ldapPass = $resultSArr['ldap_pass'];
            $ldapHost = $resultSArr['ldap_host'];
            $ldapPort = $resultSArr['ldap_port'];
            $ldapUrl = $resultSArr['ldap_url'];
            $ldapDn = $resultSArr['ldap_dn'];
            $ldapDn = $resultSArr['ldap_dn'];
            $ds = @ldap_connect($ldapHost, $ldapPort) or die("Could not connect to $ldapHost");
            $errMessage = "";
            if ($ds) {
                if ($userId > 0) {
                    $sql = new Sql($this->_adapter);
                    $select = $sql->select();
                    $qry = "Delete user_role_tracker From user_role_tracker 
                            LEFT JOIN `group` ON user_role_tracker.group_id = `group`.group_id Where user_role_tracker.u_id=? AND `group`.tracker_id = ?";
                    $statements = $this->_adapter->createStatement($qry, array($userId, $trackerId));
                    $statements->prepare();
                    $results = $statements->execute();
                    $qry = 'delete from user_role_session where u_id=? AND tracker_id =?';
                    $statements = $this->_adapter->createStatement($qry, array($userId, $trackerId));
                    $statements->prepare();
                    $results = $statements->execute();
                }
                $resultsArr = array();
                foreach ($roleIds as $roleId) {
                    if ($userId > 0) {
                        $sql = new Sql($this->_adapter);
                        $select = $sql->select();
                        $qry = 'call sp_updateUsers("' . $roleId . '","' . $status . '","' . $archived . '","' . $userId . '","' . $key . '","' . $salt . '","'.$type.'")';
                        $statements = $this->_adapter->query($qry);
                        $results = $statements->execute();
                        $uId = $userId;
                        $this->uerRoleSessionAdd($uId, $trackerId, $roleId);
                        $responseCode = 1;
                        $errMessage = 'Updated user';
                    } else {
                        $queryClients = "SELECT user . * FROM user WHERE user.u_name = ?";
                        $statements = $this->_adapter->createStatement($queryClients, array($uName));
                        $statements->prepare();
                        $results = $statements->execute();
                        $arr = array();
                        $resultSet = new ResultSet;
                        $resultSet->initialize($results);
                        $resultSet->buffer();
                        $count = @$resultSet->count();
                        if ($count > 0) {
                            $arr = $resultSet->toArray();
                        }
                        if ($count > 0) {
                            $querygroup = "select count('group_id') as tot from `group` where group_id=? and tracker_id=?";
                            $statements = $this->_adapter->createStatement($querygroup, array($roleId, $trackerId));
                            $statements->prepare();
                            $arrGroup = array();
                            $results = $statements->execute();
                            $resultSet = new ResultSet;
                            $resultSet->initialize($results);
                            $resultSet->buffer();
                            $countexist = @$resultSet->count();
                            if ($countexist > 0) {
                                $arrGroup = $resultSet->toArray();
                            }
                            if ($arrGroup[0]['tot'] > 0) {
                                $querygroup = "select count('u_id') as tot from `user_role_tracker` where group_id=? and u_id=?";
                                $statements = $this->_adapter->createStatement($querygroup, array($roleId, $arr[0]['u_id']));
                                $statements->prepare();
                                $querygroup;
                                $arrUserRole = array();
                                $results = $statements->execute();
                                $resultSet = new ResultSet;
                                $resultSet->initialize($results);
                                $resultSet->buffer();
                                $countexisted = @$resultSet->count();
                                if ($countexisted > 0) {
                                    $arrUserRole = $resultSet->toArray();
                                }
                                if ($arrUserRole[0]['tot'] == 0) {
                                    $queryClients = "insert into user_role_tracker(u_id,group_id)values(?,?)";
                                    $statements = $this->_adapter->createStatement($queryClients, array($arr[0]['u_id'], $roleId));
                                    $statements->prepare();
                                    $results = $statements->execute();
                                    if (!isset($responseCode)) {
                                        $responseCode = 2;
                                    }
                                    $errMessage = "User Created Successfully";
                                    $uId = $arr[0]['u_id'];
                                    $this->uerRoleSessionAdd($uId, $trackerId, $roleId);
                                } else {
                                    $responseCode = 3;
                                    $errMessage = "User Already Exist";
                                }
                            } else {
                                $responseCode = 3;
                                $errMessage = "User Already Exist";
                            }
                        } else {
                            if ($type == 'LDAP') {
                                ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
                                ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);
                                // $uNameEmail='';
                                // if ($ldapUrl=='BIODEV') {
                                //     $ldapNameEmail = $ldapUrl."\\".$ldapUser;
                                // } else {
                                //     $uNameEmail = "$ldapUser@$ldapUrl";
                                // }
                                // $ldapbind = @ldap_bind($ds, $uNameEmail, $ldapPass);
                                $ldapbind = @ldap_bind($ds, "$ldapUser@$ldapUrl", $ldapPass);
                                if ($ldapbind) {
                                    $attributes = array("displayname", "mail",
                                        "department",
                                        "title",
                                        "physicaldeliveryofficename");
                                    $filter = "(&(objectCategory=person)(sAMAccountName=$uName))";
                                    $result = ldap_search($ds, $ldapDn, $filter, $attributes);
                                    $entries = ldap_get_entries($ds, $result);

                                    if ($entries["count"] > 0) {
                                        $realName = $entries[0]['displayname'][0];
                                        $email = "$uName@$ldapUrl";
                                        $resultsArr['email'] = $email;
                                        $responseCode = 1;
                                        $errMessage = "User Created Successfully";
                                        $sql = new Sql($this->_adapter);
                                        $insert = $sql->insert('user');
                                        $newData = array(
                                            'u_name' => $uName,
                                            'u_realname' => $realName,
                                            'email' => $email,
                                            'status' => 'Active',
                                            'user_type' => $type
                                        );
                                        $insert->values($newData);
                                        $selectString = $sql->prepareStatementForSqlObject($insert);
                                        $results = $selectString->execute();
                                        $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
                                        $sql = new Sql($this->_adapter);
                                        $insert = $sql->insert('user_role_tracker');
                                        $newData = array(
                                            'u_id' => $lastInsertID,
                                            'group_id' => $roleId,
                                        );
                                        $insert->values($newData);
                                        $selectString = $sql->prepareStatementForSqlObject($insert);
                                        $results = $selectString->execute();

                                        $uId = $lastInsertID;
                                        $this->uerRoleSessionAdd($uId, $trackerId, $roleId);
                                    } else {
                                        $responseCode = 400;
                                        $errMessage = 'Invalid User';
                                    }
                                }
                            } else {
                                $sql = new Sql($this->_adapter);
                                $insert = $sql->insert('user');
                                $newData = array(
                                    'u_name' => $uName,
                                    'user_salt' => $salt,
                                    'user_publickey' => $key,
                                    'email' => $uName,
                                    'u_realname'=>$uName,
                                    'status' => 'Inactive',
                                    'user_type' => $type
                                );
                                $insert->values($newData);
                                $selectString = $sql->prepareStatementForSqlObject($insert);
                                $results = $selectString->execute();
                                $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
                                $responseCode = 1;
                                $errMessage = "User Created Successfully";
                                $sql = new Sql($this->_adapter);
                                $insert = $sql->insert('user_role_tracker');
                                $newData = array(
                                    'u_id' => $lastInsertID,
                                    'group_id' => $roleId,
                                );
                                $insert->values($newData);
                                $selectString = $sql->prepareStatementForSqlObject($insert);
                                $results = $selectString->execute();
                                $uId = $lastInsertID;
                                $this->uerRoleSessionAdd($uId, $trackerId, $roleId);
                            }
                        }
                    }
                }
            } else {
                $responseCode = 400;
                $errMessage = 'Invalid User';
            }
        } else {
            $responseCode = 400;
            $errMessage = 'Invalid User';
        }
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }

    /**
     * @return result object
     */
    public function getUserGroup($tracker_id)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('group');
        $select->where(array('group_archived' => 0, 'tracker_id' => $tracker_id));
        $newadpater = $this->_adapter;
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

    /*
     * Function to delete settings :to make settings archive
     */

    public function deleteUser($user_id, $tracker_id)
    {
        $sql = new Sql($this->_adapter);
        $query="DELETE FROM 
                    user_role_tracker
                    USING 
                    user_role_tracker 
                    JOIN 
                      `group`  ON user_role_tracker.group_id=`group`.group_id 
                    WHERE 
                      `group`.tracker_id=? and user_role_tracker.u_id=?";
        $statements = $this->_adapter->createStatement($query, array($tracker_id, $user_id));
        $statements->prepare();
        $results = $statements->execute();
    }

    public function uerRoleSessionAdd($uId, $trackerId, $roleId)
    {
        $queryRoleSession = "SELECT * FROM user_role_session WHERE u_id = $uId AND tracker_id = $trackerId";
        $statements = $this->_adapter->createStatement($queryRoleSession, array($uId, $trackerId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count_role_session = @$resultSet->count();
        $sql_role = new Sql($this->_adapter);
        if ($count_role_session > 0) {
            $newData = array(
                'role_id' => $roleId
            );
            $update = $sql_role->update('user_role_session');
            $update->set($newData);
            $update->where(
                array(
                'u_id' => $uId,
                'tracker_id' => $trackerId
                )
            );
            $selectStringRole = $sql_role->prepareStatementForSqlObject($update);
        } else {
            $newData = array(
                'u_id' => $uId,
                'tracker_id' => $trackerId,
                'role_id' => $roleId,
            );
            $insert = $sql_role->insert('user_role_session');
            $insert->values($newData);
            $selectStringRole = $sql_role->prepareStatementForSqlObject($insert);
        }
        $selectStringRole->execute();
    }

    /*
     * get ldap and domain related value from setting table
     */

    public function getSetting()
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('settings');
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $arr = $resultSet->toArray();
        return $arr;
    }

    /**
     * @to get all information of a user tracker wise
     */
    public function getUserInfoForTracker($user_id, $tracker_id)
    {
        $qry = 'SELECT urt.u_id,us.u_name,group_concat(gp.group_id) as group_name,us.status,us.user_archived,us.user_type FROM user us
        left join user_role_tracker urt on urt.u_id=us.u_id
        left join `group` gp on gp.group_id=urt.group_id where us.u_id=? and gp.tracker_id=?';
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $statements = $this->_adapter->createStatement($qry, array($user_id, $tracker_id));
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
}
