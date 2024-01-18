<?php
namespace ASRTRACE\Tracker\Model;

use Zend\Db\adapter\adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;

class WorkflowMgmModule
{
    protected $_adapter;
    /**
     * Make the _adapter object available as local protected variable
     *
     * @param _adapter $_adapter - DB PDO PgSQL conn
     */
    public function __construct(adapter $_adapter)
    {
        $this->_adapter = $_adapter;
    }
        
    public function getFieldsBasedOnWorkflow($workflowId)
    {
        $queryFields = "SELECT f.field_id,f.field_name, f.label as fieldlabel,f.field_type,f.formula,f.code_list_id, f.validation_required, f.formula_dependent, clo.value as optionValue, clo.label as optionLabel,clo.kpi,clo.archived
                        FROM field as f 
                        LEFT JOIN code_list_option as clo ON f.code_list_id = clo.code_list_id
                        Where f.workflow_id = ? and (clo.archived IS NULL OR clo.archived = 0) ORDER BY f.sort_order ASC";
        $statements = $this->_adapter->createStatement($queryFields, array($workflowId));
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
        return $arr;
    }

    public function getRecordDatabyId($tableName, $recordId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from($tableName);
        //$select->join(array('cd'=>'code_list_option'),'field.code_list_id = code_list_option.code_list_id',array('code_list_id','cd.value','cd.label'),'left');
        $select->where(array('id' => $recordId,'is_deleted'=>'No'));
        //$select->order('sort_order ASC');
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
    public function updateAndSaveWorkflowData($tableName, $recordId, $dataArr)
    {
        $result=array();
        
        if (!empty($dataArr)) {
            try {
                $sql = new Sql($this->_adapter);
                $update = $sql->update($tableName);
                $update->set($dataArr);
                $update->where(array('id' => $recordId));
                $selectString = $sql->prepareStatementForSqlObject($update);
                $result=$selectString->execute();
            } catch (\Exception $ex) {
                $result=$ex->getMessage();
                echo $result;
            }
        } else {
            $result='Empty data';
        }
        return $result;
    }
    public function getAllWorkflowFieldsData($tableName, $recordId, $dataArr)
    {
        $arr=array();
        try {
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from($tableName);
            $select->columns($dataArr);
            $select->where(array('id' => $recordId,'is_deleted'=>'No'));
            $selectString = $sql->prepareStatementForSqlObject($select);
            $results = $selectString->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arr = $resultSet->toArray();
            }
        } catch (\Exception $ex) {
            $arr=$ex->getMessage();
        }
        return $arr;
    }
    public function getCanReadAndCanUpdateAccess($workflowId, $roleId, $roleName)
    {
        $arr = array();
        if ($roleName == 'SuperAdmin'||$roleName == 'Administrator') {
            $arr[0]['can_read'] = 'Yes';
            $arr[0]['can_update'] = 'Yes';
            $arr[0]['can_delete'] = 'Yes';
            $arr[0]['can_insert'] = 'Yes';
        } else {
            $queryFields = "SELECT * FROM workflow_role Where workflow_id = ? and role_id = ?";
            $statements = $this->_adapter->createStatement($queryFields, array($workflowId,$roleId));
            $statements->prepare();
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arr = $resultSet->toArray();
            }
        }
        return $arr;
    }

    public function getCanReadAndCanUpdateAccessAllWorkflow($formId, $roleId, $roleName)
    {
        $arr = array();
        if ($roleName == 'SuperAdmin'||$roleName == 'Administrator') {
            $arr[0]['can_read'] = 'Yes';
            $arr[0]['can_update'] = 'Yes';
            $arr[0]['can_delete'] = 'Yes';
            $arr[0]['can_insert'] = 'Yes';
        } else {
            $queryFields = "SELECT * FROM workflow AS w JOIN workflow_role AS wr  WHERE w.workflow_id = wr.workflow_id AND w.form_id = ? AND w.status = ? AND wr.role_id = ?";
            $statements = $this->_adapter->createStatement($queryFields, array($formId,'Active',$roleId));
            $statements->prepare();
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arr = $resultSet->toArray();
            }
        }
        return $arr;
    }

    public function updateAndSaveCheckBoxData($recordId, $checkBoxDataArr)
    {
        $results=array();
        try {
            $sql = new Sql($this->_adapter);
            $query = "Delete From form_record_code_list Where form_id=? And record_id= ? AND field_name= ? ";
            foreach ($checkBoxDataArr as $checkBoxData) {
                $statements = $this->_adapter->createStatement($query, array($checkBoxData['form_id'],$recordId, $checkBoxData['field_name'])); 
                $deleteString = $statements->execute(); 
            }
           
            $insert = $sql->insert('form_record_code_list');
            foreach ($checkBoxDataArr as $checkBoxData) {
                if ($checkBoxData['checked'] == "Checked") {
                    unset($checkBoxData['checked']);
                    $insert->values($checkBoxData);
                    $selectString = $sql->prepareStatementForSqlObject($insert);
                    $results = $selectString->execute();
                }
            }
        } catch (\Exception $ex) {
            $results=$ex->getMessage();
        }
        return $results;
    }

    public function getConfigDataByForm($formId)
    {
        $resArr = array();
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('config');
        $select->columns(array('config_key','config_value'));
        $select->where(array('scope_id'=>$formId));
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
  
    public function getUserList($roleId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('user_role_tracker');
        $select->join(array('u'=>'user'), 'user_role_tracker.u_id = u.u_id', array('u_id','u_name'));
        $select->where(array('user_role_tracker.group_id' => $roleId));
        $select->order('u_name');
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

    public function getWorkflowRuleWhen($formId,$workflowId)
    {
        $arr = array();  
        $queryFields = "SELECT r.rule_id, rc.operator, rcl.field_id, rcl.comparision_op, rcl.value,f.field_name,ra.action
        FROM rule AS r 
        LEFT JOIN rule_action AS ra ON r.rule_id = ra.rule_id
        LEFT JOIN rule_condition AS rc ON r.rule_id = rc.rule_id
        LEFT JOIN rule_condition_loop AS rcl ON rc.condition_id = rcl.condition_id
        join field as f
        Where r.form_id = ? and r.status = ? and r.archive = ? and ra.action_workflow_id = ? and rcl.field_id = f.field_id";
        $statements = $this->_adapter->createStatement($queryFields, array($formId,'Active','0',$workflowId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }

    public function getRuleAction($rule_id)
    {
        $arr = array();  
        $queryFields = "SELECT r.rule_action_id, r.action, r.action_workflow_id, f.field_id as action_fields, f.field_name   
        FROM rule_action r
        LEFT OUTER JOIN field f
        ON FIND_IN_SET(f.field_id, r.action_fields) where r.rule_id=?";
        $statements = $this->_adapter->createStatement($queryFields, array($rule_id));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }

    public function getQuerydata($condition,$recordId,$tableName)
    {
        $arr = array();  
        $queryFields = "SELECT * FROM ".$tableName." WHERE ".$condition." and id = ?";
        $statements = $this->_adapter->createStatement($queryFields, array($recordId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function otherWorkflowFields($formId,$workflowId)
    {
        $arr = array();  
        $queryFields = "SELECT field_name,field_type,formula,formula_dependent FROM field WHERE workflow_id <> ? and field_type in ('Formula','Formula Combo Box','Formula Date') and workflow_Id in (select workflow_id from workflow where form_id=?)";
        $statements = $this->_adapter->createStatement($queryFields, array($workflowId,$formId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }

    public function checkNotification($formId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from(array('n'=>'notification_template'));
        $select->columns(array('notification_template_id', 'notification_template_name', 'notification_template_subject', 'notification_template_msg','notification_template_to','notification_template_condition_type','notification_template_cc'));
        $select->join(array('c'=>'condition_for_templates'), 'n.notification_template_id = c.condition_notification_template_id', array('condition_field_name','condition_operand','condition_value'), $select::JOIN_INNER);
        $select->where(array('n.notification_template_form_id' => $formId,'n.notification_template_status' =>'Active','n.notification_template_archive'=>'0'));
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
}
