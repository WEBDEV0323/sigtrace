<?php

namespace Common\Role\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Session\Container\SessionContainer;

class Role
{
    protected $_adapter;

    /**
     * Make the _adapter object available as local protected variable
     *
     * @param _adapter $_adapter - DB PDO PgSQL conn
     */
    public function __construct(Adapter $_adapter)
    {
        $this->_adapter = $_adapter;
    }

     /**
      * @return result object
      */
    public function getRoleForTracker($tracker_id)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('role');
        $select->where(array('tracker_id' => $tracker_id, 'archived' => 0));
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
    
    public function trackerResults($trackerId)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        
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
        $roleId=(int)$roleId;
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
    
    /**
     * @return result object
     */
    public function getRole($roleId)
    {
        $roleId = (int)$roleId;
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('role');
        $select->where(array('rid' => $roleId, 'archived' => 0));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            return $arr[0];
        }
        return $arr;
    }
    public function getTrackerDetails($trackerId) 
    {
        $trackerId = (int)$trackerId;
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('tracker');
        $select->where(array('tracker_id' => $trackerId, 'archived' => 0));
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
    public function saveRole($post) 
    {
        $roleId = $post['roleId'];
        $trackerId = $post['trackerId'];
        $rolename = $post['c_name'];
        
        $param = array();
        $qry = "SELECT count(rid) as checkduplicate FROM `role` where LOWER(role_name) = :rn AND tracker_id = :t AND archived = :ra";
        $param['rn'] = strtolower($rolename);
        $param['t'] = trim(addslashes($trackerId));
        $param['ra'] = 0;
        if ($roleId > 0) {
            $qry .= " AND rid != :ri";
            $param['ri'] = $roleId;
        }
        $statements = $this->_adapter->createStatement($qry, $param);
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            if ($arr[0]['checkduplicate'] > 0) {
                $responseCode = 0;
                $errMessage = 'Role already exists with same name'; 
            
            } else {
                $connection = null;
                $sql = new Sql($this->_adapter);
                switch (intval($roleId)) {
                case 0:
                    try { 
                        $connection = $this->_adapter->getDriver()->getConnection();
                        $connection->beginTransaction();
                        $newData = array(
                                'role_name' => $rolename,
                                'tracker_id' => $trackerId
                                );
                        $insert = $sql->insert('role');
                        $insert->values($newData);
                        $selectString = $sql->prepareStatementForSqlObject($insert);
                        $results = $selectString->execute();
                        $roleId = $this->_adapter->getDriver()->getLastGeneratedValue();
                        $connection->commit();

                        $responseCode = 1;
                        $errMessage = "Role created successfully";
                    } catch(\Exception $e) {
                        if ($connection instanceof ConnectionInterface) {
                            $connection->rollback();
                        }
                        $responseCode = 0;
                        $errMessage = 'Error while inserting role'; 
                    } catch(\PDOException $e) {
                        if ($connection instanceof ConnectionInterface) {
                            $connection->rollback();
                        }
                        $responseCode = 0;
                        $errMessage = 'Error while inserting role'; 
                    }
                    break;
                default:
                    try { 
                        $connection = $this->_adapter->getDriver()->getConnection();
                        $connection->beginTransaction();
                        $newData = array(
                                'role_name' => $rolename,
                                'tracker_id' => $trackerId
                                );
                        $update = $sql->update('role')
                            ->set($newData)
                            ->where(
                                array(
                                        'rid' => $roleId,
                                        'archived' => 0
                                        )
                            );

                        $selectString = $sql->prepareStatementForSqlObject($update);
                        $selectString->execute();
                        $connection->commit();

                        $responseCode = 2;
                        $errMessage = "Role Updated Successfully";
                    } catch(\Exception $e) {
                        if ($connection instanceof ConnectionInterface) {
                            $connection->rollback();
                        }
                        $responseCode = 0;
                        $errMessage = 'Error while updating role'; 
                    } catch(\PDOException $e) {
                        if ($connection instanceof ConnectionInterface) {
                            $connection->rollback();
                        }
                        $responseCode = 0;
                        $errMessage = 'Error while updating role'; 
                    }
                    break;
                }

            }
        } else {
            $responseCode = 0;
            $errMessage = 'Error';
        }
        $resultsArr['roleId'] = $roleId;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    public function deleteRole($roleId) 
    {
        $connection = null;
        $sql = new Sql($this->_adapter);
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            
            $update = $sql->update('role')
                ->set(array('archived' => 1))
                ->where(
                    array(
                    'rid' => $roleId
                    )
                );
            $selectString = $sql->prepareStatementForSqlObject($update);
            $selectString->execute();
            
            $qry = "delete user_role_tracker from user_role_tracker
                    LEFT JOIN `role` ON user_role_tracker.group_id = `role`.rid 
                    Where user_role_tracker.group_id=?";
            $statement2 = $this->_adapter->createStatement($qry, array($roleId));
            $statement2->prepare();
            $statement2->execute();

            //data removing from user_role_session
            $qry = 'delete user_role_session from user_role_session
                    LEFT JOIN `role` ON user_role_session.role_id = `role`.rid 
                    Where user_role_session.role_id=?';
            $statement3 = $this->_adapter->createStatement($qry, array($roleId));
            $statement3->prepare();
            $statement3->execute();
                            
            $connection->commit();

            $responseCode = 1;
            $errMessage = "Role deleted Successfully";
            
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error while deleting role'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error while deleting role'; 
        }
        $resultsArr['roleId'] = $roleId;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
}
