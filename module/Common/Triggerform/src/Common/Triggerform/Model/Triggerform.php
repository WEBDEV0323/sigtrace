<?php

namespace Common\Triggerform\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Session\Container\SessionContainer;

class Triggerform
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
    public function getTriggerForTracker($tracker_id)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('trigger');
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
    public function getTrigger($triggerId)
    {
        $triggerId = (int)$triggerId;
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('trigger');
        $select->where(array('trigger_id' => $triggerId, 'archived' => 0));
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
    public function saveTrigger($post) 
    {
        $triggerId = $post['triggerId'];
        $trackerId = $post['trackerId'];
        $triggername = $post['c_name'];
        $triggerWhen = $post['trigger_when'];
        $triggerThen = $post['trigger_then'];
        $triggerSource = $post['source'];
        $triggerDestination = $post['destination'];
        $triggerCondition = $post['when_conditions'];
        $triggerFieldCopy = $post['fields_to_copy'];
        $reason = $post['reason'];
        
        $param = array();
        $qry = "SELECT count(trigger_id) as checkduplicate FROM `trigger` where LOWER(trigger_name) = :rn AND tracker_id = :t AND archived = :ra";
        $param['rn'] = strtolower($triggername);
        $param['t'] = trim(addslashes($trackerId));
        $param['ra'] = 0;
        if ($triggerId > 0) {
            $qry .= " AND trigger_id != :ri";
            $param['ri'] = $triggerId;
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
                $errMessage = 'Trigger Form already exists with same name'; 
            
            } else {
                $connection = null;
                $sql = new Sql($this->_adapter);
                switch (intval($triggerId)) {
                case 0:
                    try { 
                        $connection = $this->_adapter->getDriver()->getConnection();
                        $connection->beginTransaction();
                        $newData = array(
                                'trigger_name' => $triggername,
                                'tracker_id' => $trackerId,
                                'trigger_when' => $triggerWhen,
                                'trigger_then' => $triggerThen,
                                'source' => $triggerSource,
                                'destination' => $triggerDestination,
                                'when_conditions' => $triggerCondition,
                                'fields_to_copy' => $triggerFieldCopy,
                                'reason' => $reason
                                );
                        $insert = $sql->insert('trigger');
                        $insert->values($newData);
                        $selectString = $sql->prepareStatementForSqlObject($insert);
                        $results = $selectString->execute();
                        $triggerId = $this->_adapter->getDriver()->getLastGeneratedValue();
                        $connection->commit();

                        $responseCode = 1;
                        $errMessage = "Trigger created successfully";
                    } catch(\Exception $e) {
                        if ($connection instanceof ConnectionInterface) {
                            $connection->rollback();
                        }
                        $responseCode = 0;
                        $errMessage = 'Error while inserting triggerform'; 
                    } catch(\PDOException $e) {
                        if ($connection instanceof ConnectionInterface) {
                            $connection->rollback();
                        }
                        $responseCode = 0;
                        $errMessage = 'Error while inserting triggerform'; 
                    }
                    break;
                default:
                    try { 
                        $connection = $this->_adapter->getDriver()->getConnection();
                        $connection->beginTransaction();
                        $newData = array(
                                'trigger_name' => $triggername,
                                'tracker_id' => $trackerId,
                                'trigger_when' => $triggerWhen,
                                'trigger_then' => $triggerThen,
                                'source' => $triggerSource,
                                'destination' => $triggerDestination,
                                'when_conditions' => $triggerCondition,
                                'fields_to_copy' => $triggerFieldCopy,
                                'reason' => $reason
                                );
                        $update = $sql->update('trigger')
                            ->set($newData)
                            ->where(
                                array(
                                        'trigger_id' => $triggerId,
                                        'archived' => 0
                                        )
                            );

                        $selectString = $sql->prepareStatementForSqlObject($update);
                        $selectString->execute();
                        $connection->commit();

                        $responseCode = 2;
                        $errMessage = "Triggerform Updated Successfully";
                    } catch(\Exception $e) {
                        if ($connection instanceof ConnectionInterface) {
                            $connection->rollback();
                        }
                        $responseCode = 0;
                        $errMessage = 'Error while updating triggerform'; 
                    } catch(\PDOException $e) {
                        if ($connection instanceof ConnectionInterface) {
                            $connection->rollback();
                        }
                        $responseCode = 0;
                        $errMessage = 'Error while updating triggerform'; 
                    }
                    break;
                }

            }
        } else {
            $responseCode = 0;
            $errMessage = 'Error';
        }
        $resultsArr['triggerId'] = $triggerId;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    public function deleteTrigger($triggerId,$comment) 
    {
        $connection = null;
        $sql = new Sql($this->_adapter);
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            
            $update = $sql->update('trigger')
                ->set(array('archived' => 1,'reason' => $comment))
                ->where(
                    array(
                    'trigger_id' => $triggerId
                    )
                );
            $selectString = $sql->prepareStatementForSqlObject($update);
            $selectString->execute();
                            
            $connection->commit();

            $responseCode = 1;
            $errMessage = "Trigger Form deleted Successfully";
            
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error while deleting Trigger Form'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error while deleting Trigger Form'; 
        }
        $resultsArr['triggerId'] = $triggerId;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
}
