<?php

namespace Common\BulkAction\Model;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Common\Trigger\Model\Trigger;
use Session\Container\SessionContainer;

class BulkAction extends AbstractActionController
{
    protected $_adapter;
    public function __construct(Adapter $adapter)
    {
        $this->_adapter  = $adapter;
    }
    
    public function fetchBulkActions($formId, $userRoleId, $userRoleType) 
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('bulk_action');
        if ($userRoleType != 1) {
            $select->where(array('form_id'=>$formId,'status' => 'Active', 'role_id' =>$userRoleId));
        } else {
            $select->where(array('form_id'=>$formId,'status' => 'Active'));
            $select->group('action_name');
        }
        $selectString = $sql->prepareStatementForSqlObject($select); 
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        return $resArr;
    }
    
    public function getXtraRequiredDetails($id) 
    {
        $resArr = '';
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->columns(array('xtra_required'));
        $select->from('bulk_action');
        $select->where(array('action_id'=>$id));
        $selectString = $sql->prepareStatementForSqlObject($select); 
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $resArr = $resultSet->toArray()[0]['xtra_required'];
        }
        return $resArr;
    }
    
    public function getActionDetails($id)
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('bulk_action');
        $select->where(array('action_id'=>$id));
        $selectString = $sql->prepareStatementForSqlObject($select); 
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $resArr = $resultSet->toArray()[0];
        }
        return $resArr; 
    }
    
    public function getFieldValues($field)
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->columns(array('option_label' => 'label', 'option_value'=> 'value'));
        $select->from('code_list_option')
            ->join('code_list', 'code_list.code_list_id = code_list_option.code_list_id')
            ->join('field', 'field.code_list_id = code_list.code_list_id');
        $select->where(array('code_list_option.archived'=>'0','field.field_name'=>$field)); 
        $selectString = $sql->prepareStatementForSqlObject($select); 
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        return $resArr;
    }
    
    public function getUsersByRole($roleId)
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->columns(array('u_name', 'email'));
        $select->from('user')
            ->join('user_role_tracker', 'user_role_tracker.u_id = user.u_id')
            ->join('role', 'role.rid = user_role_tracker.group_id');
        $select->where(array('role.rid'=>$roleId));
        $selectString = $sql->prepareStatementForSqlObject($select); 
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        return $resArr;
    }
    
    public function getUsers()
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->columns(array('u_name', 'email'));
        $select->from('user')
            ->join('user_role_tracker', 'user_role_tracker.u_id = user.u_id');
        $selectString = $sql->prepareStatementForSqlObject($select); 
        $results = $selectString->execute(); 
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();        
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        return $resArr;
    }
    
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
    
    public function applyBulkAction($trackerId, $formId, $set, $where, $valueArray, $iD)
    {
        $session = new SessionContainer();
        $userSession = $session->getSession('user');
        try {
            $query = "UPDATE form_".$trackerId."_".$formId." SET ".$set.$where;
            $statements = $this->_adapter->createStatement($query, array_merge($valueArray, $iD));
            $statements->prepare();
            $statements->execute();
            $workflowData = $this->getRecordData("form_".$trackerId."_".$formId, $iD[0]);
            $triggerService = new Trigger($this->_adapter);
            $triggerService->checkTrigger("modified", $trackerId, $formId, $workflowData, $workflowData, $iD[0], $userSession->u_id);
            return $iD[0];
        } catch(\Exception $e) {
            return 0;
        } catch(\PDOException $e) {
            return 0;
        }
    }
    public function getRecordData($tableName, $recordId) 
    {
        $arr = array();
        try {
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from($tableName);
            $select->where(array('id' => $recordId));
            $selectString = $sql->prepareStatementForSqlObject($select);
            $results = $selectString->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arr = $resultSet->toArray()[0];
            }
        } catch(\Exception $e) {
        } catch(\PDOException $e) {
        }
        return $arr;
    }
}
