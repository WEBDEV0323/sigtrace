<?php
namespace ASRTRACE\Workflow\Model;

use Zend\Db\adapter\adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Session\Container\SessionContainer;

class Workflow
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
    
    public function trackerCheckForms($trackerId, $formId)
    {
        $queryTracker = "SELECT * FROM form WHERE tracker_id =?  AND form_id =? ";
        $statements = $this->_adapter->createStatement($queryTracker, array($trackerId,$formId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $resArr['form_details'] = $resultSet->toArray()[0];
            $resArr['responseCode'] = 1;
            $resArr['errMessage'] = "";
        } else {
            $resArr['form_details'] = array();
            $resArr['responseCode'] = 0;
            $resArr['errMessage'] = "Form Not Found";
        }
        return $resArr; 
    }
    
    public function trackerCheckWorkFlows($trackerId, $formId, $roleId, $roleName)
    {
        if ($roleName == 'SuperAdmin' || $roleName == 'Administrator') {
            $queryClients = "SELECT workflow_id, workflow_name, 'Yes' as can_read, 'Yes' as can_update FROM workflow Where form_id = ? and `status`=? ORDER BY sort_order";
            $statements = $this->_adapter->createStatement($queryClients, array($formId,'Active'));
        } else {
            $queryClients = "SELECT w.workflow_id, w.workflow_name, wr.can_read, wr.can_update FROM workflow as w LEFT JOIN workflow_role as wr on w.workflow_id=wr.workflow_id WHERE w.form_id = ? and wr.role_id = ? and w.status=? ORDER BY w.sort_order";
            $statements = $this->_adapter->createStatement($queryClients, array($formId,$roleId,'Active'));
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
        return $arr;
    }
    
    public function recordsCanEdit($trackerId, $formId)
    {
        $resArr = array();
        $session = new SessionContainer();
        $userContainer = $session->getSession('user'); 
        $trackerContainer = $session->getSession('tracker');
        $userDetails = $userContainer->user_details;
        $roleId = $userDetails['group_id'];
        $queryFormFields = "SELECT field.*, workflow.workflow_name
        FROM field
        LEFT JOIN workflow ON field.workflow_id = workflow.workflow_id
        WHERE workflow.form_id = ? ORDER BY workflow.sort_order ASC, field.sort_order ASC";
        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        $sessionGroup = $trackerUserGroups[$trackerId]['session_group'];
        $sessionGroupId = $trackerUserGroups[$trackerId]['session_group_id'];
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            $queryFormFields = "SELECT field . * , workflow.workflow_name, workflow.form_id, workflow_role.role_id, workflow_role.can_update,
            workflow_role.can_read
            FROM field
            JOIN workflow ON field.workflow_id = workflow.workflow_id
            JOIN workflow_role ON workflow_role.workflow_id = workflow.workflow_id
            WHERE workflow.form_id = ? AND workflow_role.role_id = $sessionGroupId AND (workflow_role.can_read='Yes' OR workflow_role.can_read='Self')
            ORDER BY workflow.sort_order ASC , field.sort_order ASC";
        }
        $statements = $this->_adapter->createStatement($queryFormFields, array($formId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $fieldsArr = array();
        if ($count > 0) {
            $fieldsArr = $resultSet->toArray();
        }
        $workflowArray = array();
        $wkfArr = array();
        $roleForUser = 0;
        $fid = '';
        foreach ($fieldsArr as $key => $value) {
            $workflowName = $value['workflow_name'];

            if (!in_array($workflowName, $workflowArray)) {
                $workflowArray[] = $value['workflow_name'];
            }
            $wkfArr[$workflowName][] = $value;

            if ($value['field_type'] == 'User') {
                if ($fid != '') {
                    $fid = $fid . ',' . $value['field_id'];
                } else {
                    $fid = $value['field_id'];
                }
                $roleForUser = 1;
            }
        }
        if ($roleForUser == 1) {
            $qryforroles = "select * from user
                        left join user_role_tracker on  user_role_tracker.u_id=user.u_id
                        left join `role` on user_role_tracker.group_id = `role`.rid
                        join field on field.formula = `role`.rid and field.field_type = 'User' and field.field_id in (".$fid.")";

            $statements = $this->_adapter->query($qryforroles);
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            $fieldsArr = array();
            if ($count > 0) {
                $fieldsArr = $resultSet->toArray();
            }
            $resArr['roles'] = $fieldsArr;
        }
        $resArr['workflows'] = $workflowArray;
        $resArr['fields'] = $wkfArr;
        return $resArr; 
    }
    
    public function checkHolidayList($trackerId)
    {
        $arr = array();
        try {
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from(array('c'=>'calendar_event'));
            $select->columns(array());
            $select->join(array('u'=>'user_event'), 'c.id = u.event_id', array('start_date','end_date'), $select::JOIN_INNER);
            $select->where(array('c.customer_id' => $trackerId,'c.event_type' =>'Global','u.is_archived'=>'0'));
            $selectString = $sql->prepareStatementForSqlObject($select);
            $results = $selectString->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arr = $resultSet->toArray();
            }
        } catch(\Exception $e) {
        } catch(\PDOException $e) {
        }
        return $arr; 
    }
    
    public function getValidationRules($formId)
    {
        $array = array();
        try {
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('field_validations');
            $select->columns(
                array(`field_validations` . 'validation_id',
                `field_validations` . 'rule_id',
                `field_validations` . 'field_id',
                `field_validations` . 'rule_operator',
                `field_validations` . 'value',
                `field_validations` . 'message')
            );
            $select->join('field_validations_rules', 'field_validations_rules.rule_id = field_validations.rule_id', array(`field_validations_rules` . 'rule_name'));
            $select->join('field', 'field.field_id = field_validations.field_id', array(`field` . 'field_name'));
            $select->join('workflow', 'workflow.workflow_id = field.workflow_id', array(`workflow` . 'workflow_name'));

            $select->where(array('workflow.form_id' => $formId));
            $select->order(array('field.field_id'));

            $newadpater = $this->_adapter;
            $selectString = $sql->getSqlStringForSqlObject($select);
            $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);

            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $array = $resultSet->toArray();
            }
        } catch(\Exception $e) {
        } catch(\PDOException $e) {
        }
        return $array;
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
