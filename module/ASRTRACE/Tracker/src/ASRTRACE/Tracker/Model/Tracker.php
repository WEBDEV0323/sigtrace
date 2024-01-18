<?php

namespace ASRTRACE\Tracker\Model;

use Zend\Db\adapter\adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Session\Container;
use Zend\Db\Adapter\Driver\ConnectionInterface;

class Tracker
{
    protected $_adapter;
    protected $_serviceLocator;

    /**
     * Make the _adapter object available as local protected variable
     *
     * @param _adapter $_adapter - DB PDO PgSQL conn
     */
    public function __construct(adapter $_adapter)
    {
        $this->_adapter = $_adapter;
    }

    public function dashboardResults()
    {
        $container = new Container('user');
        $trackerContainer = new Container('tracker');
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
            $retArr['trackers'][$key]['isDashboardSet'] = boolval($this->isDashboardSet($value['tracker_id'], $formId));
        }
        $trackerContainer->tracker_ids = $trackerIds;        
        return $retArr;
    }
    public function isDashboardSet($trackerId, $formId)
    {
        try {
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('dashboard');
            $select->where(array('trackerId' => $trackerId, 'formId'=>$formId, 'archived'=>0));
            $selectString = $sql->prepareStatementForSqlObject($select);
            $results = $selectString->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                return true;
            }
            return false;
        } catch(\Exception $e) {
            return true;
        } catch(\PDOException $e) {
            return true;
        }
    }
    /*
     * get all client list with tracker name
     */

    public function getAllClientsWithTracker()
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $qry = 'select ctable.*,ttable.name as tracker_name,ttable.tracker_id as tracker_id  from  client ctable  join tracker ttable on ttable.client_id=ctable.client_id where ctable.archived=0 and ttable.archived=0';
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
        return $arr;
    }
    
        /*
     */

    public function getAllClients()
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('client');
        $select->where(array('archived' => 0));
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
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
    * get tracker information
    */

    public function getTrackerInformation($trackerId) 
    {
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
    
    public function getClientInfo($clientId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('client');
        if ($clientId > 0) {
            $select->where(array('client_id' => $clientId));
        }
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
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
    
    /*
     * function to add tracker for client
     */

    public function saveUpdateTracker($post)
    {
        $userContainer = new Container('user');
        $clientId = intval($post['clientId']);
        $trackerName = $post['trackerName'];
        $trackerId = intval($post['trackerId']);
        $trackerCheckQuery = "SELECT tracker_id from tracker WHERE name = :name AND client_id = :cid AND archived = 0";
        $params['name'] = $trackerName;
        $params['cid'] = $clientId;
        
        if ($trackerId > 0) {
            $trackerCheckQuery .= " AND tracker_id  != :ti ";
            $params['ti'] = $trackerId;
        }

        $statement = $this->_adapter->createStatement($trackerCheckQuery, $params);
        $statement->prepare();
        $result = $statement->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($result);
        $resultSet->buffer();
        $trackerCount = $resultSet->count();

        if ($trackerCount > 0) {
            $arr = $resultSet->toArray();
            $trackerId = $arr[0]['tracker_id'];
            $responseCode = 0;
            $errMessage = 'Tracker already exists with same tracker name'; 
        } else {
            $connection = null;
            $sql = new Sql($this->_adapter);
            switch (intval($trackerId)) {
            case 0: 
                try { 
                    $connection = $this->_adapter->getDriver()->getConnection();
                    $connection->beginTransaction();
                    $insert = $sql->insert('tracker');
                    $InsertData = array(
                        'name' => addslashes($trackerName),
                        'client_id' => addslashes($clientId),
                        'created_by' =>  $userContainer->u_id
                    );
                    $insert->values($InsertData);
                    $statement = $sql->prepareStatementForSqlObject($insert);
                    $statement->execute();
                    $trackerId = $this->_adapter->getDriver()->getLastGeneratedValue();
                        
                    $sql = new Sql($this->_adapter);
                    $insert = $sql->insert('group');
                    $newData = array(
                        'group_name' => 'Administrator',
                        'tracker_id' => $trackerId,
                    );
                    $insert->values($newData);
                    $statement = $sql->prepareStatementForSqlObject($insert);
                    $statement->execute();
                        
                    $connection->commit();
                    $responseCode = 1;
                    $errMessage = "Tracker Created Successfully";
                } catch(\Exception $e) {
                    if ($connection instanceof ConnectionInterface) {
                        $connection->rollback();
                    }
                    $responseCode = 0;
                    $errMessage = 'Error While inserting Tracker'; 
                } catch(\PDOException $e) {
                    if ($connection instanceof ConnectionInterface) {
                        $connection->rollback();
                    }
                    $responseCode = 0;
                    $errMessage = 'Error While inserting Tracker'; 
                }
                break;
            default:
                try {
                    $connection = $this->_adapter->getDriver()->getConnection();
                    $connection->beginTransaction();
                    $SetData = array(
                        'name' => addslashes($trackerName)
                    );
                    $WhereData = array(
                        'tracker_id' => $trackerId
                    );
                    $update = $sql->update('tracker');
                    $update->set($SetData);
                    $update->where($WhereData);
                    $statement = $sql->prepareStatementForSqlObject($update);
                    $statement->execute();
                    $connection->commit();
                    $responseCode = 2;
                    $errMessage = 'Tracker Updated Successfully';
                } catch(\Exception $e) {
                    if ($connection instanceof ConnectionInterface) {
                        $connection->rollback();
                    }
                    echo $e->getMessage();
                    $responseCode = 0;
                    $errMessage = 'Error while updating tracker'; 
                } catch(\PDOException $e) {
                    if ($connection instanceof ConnectionInterface) {
                        $connection->rollback();
                    }
                    echo $e->getMessage();
                    $responseCode = 0;
                    $errMessage = 'Error While updating tracker'; 
                }
                break;
            } 
        }
        $resultsArr['tracker_id'] = $trackerId;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    
    /*
     * Function to delete tracker :to make tracker archive
     */
    public function deleteTracker($trackerId)
    {
        
        $connection = null;
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            $sql = new Sql($this->_adapter);
            $update = $sql->update('tracker')
                ->set(array('archived' => 1))
                ->where(
                    array(
                        'tracker_id' => $trackerId
                    )
                );
            $statement = $sql->prepareStatementForSqlObject($update);
            $statement->execute();
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
    
    public function trackerCheckForms($tracker_id, $action_id)
    {
        $queryTracker = "SELECT * FROM form WHERE tracker_id =?  AND form_id =? ";
        $statements = $this->_adapter->createStatement($queryTracker, array($tracker_id,$action_id));
        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $resArr['responseCode'] = 1;
            $arr = $resultSet->toArray();
            $table_details = $arr[0];
            $table_name = $table_details['form_name'];
            $resArr['form_details'] = $table_details;
        } else {
            $resArr['responseCode'] = 0;
            $resArr['errMessage'] = "Form Not Found";
            $resArr['form_details'] = array();
        }
        return $resArr;
    }
    
    public function trackerCheckWorkFlows($trackerId, $actionId)
    {
        $queryClients = "SELECT * FROM workflow Where form_id = ? ORDER BY sort_order";
        $statements = $this->_adapter->createStatement($queryClients, array($actionId));
        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $resultsArr = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    
    public function trackerResults($trackerId)
    {
        $userContainer = new Container('user');
        $trackerContainer = new Container('tracker');
        
        $userDetails = $userContainer->user_details;
        $roleId = $userDetails['group_id'];
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
    
    public function getCodeList($trackerId)
    {
        $query = "Select * from code_list where tracker_id in(0, $trackerId)";
        $statements = $this->_adapter->query($query);
        $resArr = array();
        $maxSortNumber = 0;
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $resFieldsArr = array();
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        return $resArr;
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
}
