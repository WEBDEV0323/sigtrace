<?php

namespace Common\Tracker\Model;

use Zend\Db\adapter\adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Session\Container\SessionContainer;
use Zend\Mvc\Controller\AbstractActionController;

class Field extends AbstractActionController
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
    
    public function trackerCheckForms($tracker_id, $formId)
    {
        $queryTracker = "SELECT * FROM form WHERE tracker_id =?  AND form_id =? ";
        $statements = $this->_adapter->createStatement($queryTracker, array($tracker_id, $formId));
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
            $resArr['form_details'] = $table_details;
        } else {
            $resArr['responseCode'] = 0;
            $resArr['errMessage'] = "Form Not Found";
            $resArr['form_details'] = array();
        }
        return $resArr;
    }
    
    public function getCodeList($trackerId)
    {
        $query = "Select * from code_list where tracker_id in(0, $trackerId) AND archived = '0'";
        $statements = $this->_adapter->query($query);
        $resArr = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        return $resArr;
    }
    
    public function trackerCheckFields($trackerId, $actionId)
    {
        $queryClients = "SELECT field.*, workflow.workflow_name,
            group_concat(field_validations.`rule_id` ORDER BY field_validations.validation_id ASC SEPARATOR '==>') AS rule_id,
            group_concat(field_validations.`value` ORDER BY field_validations.validation_id ASC SEPARATOR '==>') AS value,
            group_concat(field_validations.`message` ORDER BY field_validations.validation_id ASC SEPARATOR '==>') AS message
        FROM field
        LEFT JOIN workflow ON field.workflow_id = workflow.workflow_id
        LEFT JOIN field_validations ON field.field_id = field_validations.field_id
        WHERE workflow.form_id = ?
        group by field.field_id
        ORDER BY workflow.sort_order ASC, field.sort_order ASC";
        $statements = $this->_adapter->createStatement($queryClients, array($actionId));
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
    
    public function getWorkFlows($formId)
    {
        $queryClients = "SELECT * FROM workflow where form_id=?";
        $statements = $this->_adapter->createStatement($queryClients, array($formId));
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
    
    public function trackerResults($trackerId)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
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
    public function getRolesForTracker($trackerId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('role');
        $select->where(array('tracker_id' => $trackerId, 'archived' => 0));
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
            'rid' => 'CurrentUser',
            'role_name' => 'CurrentUser',
            'tracker_id' => $trackerId
        );
        $arr[count($arr)]=$newdata;
        return $arr;
    }
    
    public function getFieldsInfo($field_id)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field');
        $select->where->in('field_id', $field_id);
        $ids_string = implode(',', $field_id);
        $select->order(array(new Expression('FIELD (field_id, ' . $ids_string . ')')));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $data = $resultSet->toArray();
            $arr = $data[0];
        }
        return $arr;
    }
    
    public function takeBackup($tables) 
    {
        set_time_limit(0);
        $config = $this->getServiceLocator()->get('Config');
        $dsn = $config['db']['dsn'];
        $dbuser = $config['db']['username'];
        $dbpass = $config['db']['password'];
        $dbhost = explode("=", explode(";", $dsn)[1])[1] ;
        $dbname = explode("=", explode(";", $dsn)[0])[1] ;
        ob_start();
        $backup_file = dirname(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))))."/public/backup/deletedFieldBackup/fields_".date("Y-m-d_H-i-s").'_history.sql';
        if (!file_exists(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))))."/public/backup/deletedFieldBackup")) {
            mkdir(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))))."/public/backup/deletedFieldBackup", 0777, true);
        }
        chmod(dirname(dirname(dirname(dirname(dirname(dirname(dirname(__DIR__)))))))."/public/backup/deletedFieldBackup", 0777);
        $handle = fopen($backup_file, 'w+');
        fclose($handle);
        chmod($backup_file, 0777);
        $command = "mysqldump --default-character-set=utf8 --comments --host=".escapeshellarg($dbhost)." --user=".escapeshellarg($dbuser)." ";
        if ($dbpass) {
            $command .= " --password=".escapeshellarg($dbpass)." ";
        }
        $command .= $dbname." ".$tables." > ".escapeshellarg($backup_file);
        passthru($command, $ret);
        ob_end_clean();
        return true;
    }
    
    public function editFieldById($data)
    { 
        $connection = null;
        $resultsArr = array();
        $fId = isset($data['f_id'])?intval($data['f_id']):0;
        $actionType = ($fId > 0)? "updating":"adding";
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            $label = $data['fieldName'];
            $string = preg_replace('/[^A-Za-z0-9]/', '', $label);
            $string = str_replace(" ", '_', $string);
            $string = str_replace("'", '_', $string);
            $string = str_replace('""', '_', $string);
            $string = str_replace(" ", '_', $string);
            $fieldName = strtolower($string);
            $codeListId = $data['code_list_id'];
            $fieldType = $data['fieldType'];
            $formula = (strval($data['role_id']) != '0')?$data['role_id']:null;
            $kpi = $data['kpi'];
            $formId = $data['form_id'];
            $trackerId = $data['tracker_id'];
            $validation_required_edit=$data['validation_req'];
            $default_value = $data['default_value'];
            $rule_id = isset($data['rule_id'])?$data['rule_id']:array();
            if (array_key_exists('rule_message', $data)) {
                $rule_message=$data['rule_message'];
            }
            if (array_key_exists('rule_value', $data)) {
                $rule_value=$data['rule_value'];
            }
            $formId = htmlspecialchars($formId, ENT_QUOTES);
            $formula = htmlspecialchars($formula, ENT_QUOTES);
            $fieldName = htmlspecialchars($fieldName, ENT_QUOTES);
            $fId = htmlspecialchars($fId, ENT_QUOTES);
            $query = "Select field.*
             from field
             INNER JOIN workflow On field.workflow_id = workflow.workflow_id
             INNER JOIN form ON workflow.form_id = form.form_id
             Where form.form_id=? AND field.field_name = ? ";
            if ($fId > 0) {
                $query .= " AND field.formula = ? AND field.field_id NOT IN(?)";
                $statements = $this->_adapter->createStatement($query, array($formId, $fieldName, $formula, $fId));
            } else {
                $statements = $this->_adapter->createStatement($query, array($formId, $fieldName));
            }
            $resArr = array();
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0 && $fId == 0) {
                $time = time();
                $fieldName .= "_$time";
            }
            $modifyOrAdd = "";
            $oldRules = $this->getFieldRulesInfo($fId);
            if ($fId > 0) {
                $sql = new Sql($this->_adapter);
                $newData = array(
                    'label' => $label,
                    'kpi' => $kpi,
                    'field_type' => $fieldType,
                    'code_list_id' => $codeListId,
                    'formula' => $formula,
                    'validation_required'=>$validation_required_edit, 
                    'default_value'=>$default_value
                );
                $update = $sql->update('field')
                    ->set($newData)
                    ->where(array('field_id' => $fId));
                $statement = $sql->prepareStatementForSqlObject($update);
                $results = $statement->execute();
                $responseCode = 2;
                $errMessage = "Field Updated successfully.";
                $modifyOrAdd = "MODIFY";
            } else {
                $sql = new Sql($this->_adapter);
                $sortNumber = intval($this->getMaxSortNumber($data['workflowId']));
                $newData = array(
                    'field_name' => $fieldName,
                    'label' => $label,
                    'kpi' => $kpi,
                    'field_type' => $fieldType,
                    'code_list_id' => $codeListId,
                    'formula' => $formula,
                    'validation_required'=>$validation_required_edit,
                    'workflow_id' => $data['workflowId'],
                    'default_value'=>$default_value, 
                    'sort_order' => $sortNumber + 1
                );
                $insert = $sql->insert('field');
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
                $results = $statement->execute();
                $fId = $this->_adapter->getDriver()->getLastGeneratedValue();
                $responseCode = 1;
                $errMessage = "Field Added successfully.";
                $modifyOrAdd = "ADD";
            }
            $newRulesData = array();
            if ($validation_required_edit == "1") {
                $statements = $this->_adapter->query("DELETE FROM field_validations WHERE field_id = ".$fId);
                $results = $statements->execute();
                for ($i=0; count($rule_id) > $i; $i++) {
                    $sql = new Sql($this->_adapter);
                    $insert = $sql->insert('field_validations');
                    $validationData = array(
                        'rule_id' => $rule_id[$i],
                        'field_id' => $fId,
                        'value' => $rule_value[$i],
                        'message' => $rule_message[$i]
                    );
                    $insert ->values($validationData);
                    $selectString = $sql->getSqlStringForSqlObject($insert);
                    $this->_adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
                    $id = $this->_adapter->getDriver()->getLastGeneratedValue();
                    $newRulesData[] = "{id: '$id',rule_id: '$rule_id[$i]', value: '$rule_value[$i]', message: '$rule_message[$i]'}";
                }
            } else {
                $statements = $this->_adapter->query("DELETE FROM field_validations WHERE field_id = ".$fId);
                $statements->execute();
            }
            $newRules = "{".implode(",", $newRulesData)."}";
            $tableName = "form_$trackerId" . "_$formId";
            $fieldName = (isset($data['edit_field_name_hidden']) && $data['edit_field_name_hidden'] != '') ?$data['edit_field_name_hidden']:$fieldName;
            switch ($fieldType) {
            case "Check Box":
                $queryAlter = "ALTER TABLE form_" . $trackerId . "_" . $formId . " $modifyOrAdd $fieldName TEXT";
                $stmts = $this->_adapter->query($queryAlter);
                $stmts->execute();
                
                $newField = "comment_checkbox_$fieldName";
                $queryField = "show columns from $tableName where Field = ?";
                $statements = $this->_adapter->createStatement($queryField, array($newField));
                $statements->prepare();
                $resArr = array();
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = $resultSet->count();
                if ($count == 0) {
                    $fielLabel = "ADD COLUMN comment_checkbox_$fieldName" . " ";
                    $fielLabel .= "TEXT";
                    $tableArr = array();
                    $tableArr[] = $fielLabel;
                    $alter = implode(', ', $tableArr);
                    $queryAlter = "ALTER TABLE $tableName $alter";
                    $statements = $this->_adapter->query($queryAlter);
                    $results = $statements->execute();
                }
                $newField = "critical_checkbox_$fieldName";
                $queryField = "show columns from $tableName where Field = ?";
                $statements = $this->_adapter->createStatement($queryField, array($newField));
                $statements->prepare();
                $resArr = array();
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                if ($count == 0) {
                    $fielLabel = "ADD COLUMN critical_checkbox_$fieldName" . " ";
                    $fielLabel .= "INT(11) NOT NULL DEFAULT '0'";
                    $tableArr = array();
                    $tableArr[] = $fielLabel;
                    $alter = implode(', ', $tableArr);
                    $queryAlter = "ALTER TABLE $tableName $alter";
                    $statements = $this->_adapter->query($queryAlter);
                    $results = $statements->execute();
                }
                $newField = "major_checkbox_$fieldName";
                $queryField = "show columns from $tableName where Field = ?";
                $statements = $this->_adapter->createStatement($queryField, array($newField));
                $statements->prepare();
                $resArr = array();
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = $resultSet->count();
                if ($count == 0) {
                    $fielLabel = "ADD COLUMN major_checkbox_$fieldName" . " ";
                    $fielLabel .= "INT(11) NOT NULL DEFAULT '0'";
                    $tableArr = array();
                    $tableArr[] = $fielLabel;
                    $alter = implode(', ', $tableArr);
                    $queryAlter = "ALTER TABLE $tableName $alter";
                    $statements = $this->_adapter->query($queryAlter);
                    $results = $statements->execute();
                }
                $newField = "minor_checkbox_$fieldName";
                $queryField = "show columns from $tableName where Field = ?";
                $statements = $this->_adapter->createStatement($queryField, array($newField));
                $statements->prepare();
                $resArr = array();
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = $resultSet->count();
                if ($count == 0) {
                    $fielLabel = "ADD COLUMN minor_checkbox_$fieldName" . " ";
                    $fielLabel .= "INT(11) NOT NULL DEFAULT '0'";
                    $tableArr = array();
                    $tableArr[] = $fielLabel;
                    $alter = implode(', ', $tableArr);
                    $queryAlter = "ALTER TABLE $tableName $alter";
                    $statements = $this->_adapter->query($queryAlter);
                    $results = $statements->execute();
                }
                break;
            case 'Integer':
            case 'ReadOnly Integer':
                $queryAlter = "ALTER TABLE form_" . $trackerId . "_" . $formId . " $modifyOrAdd $fieldName INT(11)";
                $statements = $this->_adapter->query($queryAlter);
                $results = $statements->execute();
                break;
            case 'Text Area':
            case 'ReadOnly Text Area':
                $queryAlter = "ALTER TABLE form_" . $trackerId . "_" . $formId . " $modifyOrAdd $fieldName TEXT";
                $statements = $this->_adapter->query($queryAlter);
                $results = $statements->execute();
                break;
            case 'Date':
            case 'ReadOnly Date':
                $queryAlter = "ALTER TABLE form_" . $trackerId . "_" . $formId . " $modifyOrAdd $fieldName DATE DEFAULT NULL";
                $stmts = $this->_adapter->query($queryAlter);
                $stmts->execute();
                break;
            case 'Date Time':
            case 'ReadOnly Date Time':
                $queryAlter = "ALTER TABLE form_" . $trackerId . "_" . $formId . " $modifyOrAdd $fieldName DATETIME DEFAULT NULL";
                $stmts = $this->_adapter->query($queryAlter);
                $stmts->execute();
                break;
            case 'Text':
            case 'Formula':
            case 'Combo Box':
            case 'User Role':
            case 'Formula Combo Box':
            case 'Dependent Text':
            case 'Formula Date':
            case 'File':
            case 'ReadOnly':
                $queryAlter = "ALTER TABLE form_" . $trackerId . "_" . $formId." ".$modifyOrAdd." `".$fieldName."` VARCHAR(255);";
                $statements = $this->_adapter->query($queryAlter);
                $results = $statements->execute();
                break;
            default:
                break;
            }
            $validationRequired = isset($data['validation_req'])?intval($data['validation_req']):0;
            if ($validationRequired == 1) {
                $validationData = " validation_required: 'Yes', rules: {}";
            }
            $connection->commit();
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While '.$actionType.' Field';
            $oldRules = $newRules = "{}";
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While '.$actionType.' field';
            $oldRules = $newRules = "{}";
        }
        $resArr['field_id'] = $fId;
        $resArr['form_name'] = $fieldName;
        $resArr['oldRules'] = $oldRules;
        $resArr['newRules'] = $newRules;
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function getFieldRulesInfo($fieldId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field_validations');
        $select->where(array('field_id' => $fieldId));
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
        $rulesData = array();
        foreach ($arr as $rule) {
            $rulesData[] = "{id: '".$rule['validation_id']."',rule_id: '".$rule['rule_id']."', value: '".$rule['value']."', message: '".$rule['message']."'}";
        }
        return "{".implode(",", $rulesData)."}";
    }
    public function getMaxSortNumber($workflowId)
    {
        $query = "Select MAX(sort_order) as max_sort_num from field where workflow_id=?";
        $statements = $this->_adapter->createStatement($query, array($workflowId));
        $statements->prepare();
        $maxSortNumber = 0;
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $resFieldsArr = $resultSet->toArray();
            if ($resFieldsArr[0]['max_sort_num'] != null) {
                $maxSortNumber = $resFieldsArr[0]['max_sort_num'];
            }
        }
        return $maxSortNumber;
    }
    public function deleteField($post)
    {
        $connection = null;
        $resultsArr = array();
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            $fieldId = intval(trim(addslashes($post['fieldID'])));
            $trackerId = intval(trim(addslashes($post['tracker_id'])));
            $formId = intval(trim(addslashes($post['form_id'])));
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('field');
            $select->where(array('field_id' => $fieldId));
            $statement = $sql->prepareStatementForSqlObject($select);
            $results = $statement->execute();
            $resFieldsArr = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $resFieldsArr = $resultSet->toArray();
            }
            $this->takeBackup("form_".$trackerId."_".$formId." field_validations");
            if (array_key_exists(0, $resFieldsArr) && is_numeric($formId)) {
                $alterArr = array();
                $alterQuery = "ALTER TABLE form_$trackerId" . "_$formId ";

                foreach ($resFieldsArr as $key => $value) {
                    $fieldName = $value['field_name'];
                    if ($value['field_type'] == 'Check Box') {
                        $alterArr[] = "DROP $fieldName";
                        $alterArr[] = "DROP comment_checkbox_$fieldName";
                        $alterArr[] = "DROP critical_checkbox_$fieldName";
                        $alterArr[] = "DROP major_checkbox_$fieldName";
                        $alterArr[] = "DROP minor_checkbox_$fieldName";
                    } else if ($value['field_type'] != 'Heading' && $value['field_type'] != '') {
                        $alterArr[] = "DROP $fieldName";
                    }
                }
                if (!empty($alterArr)) {
                    $alterQuery .= implode(',', $alterArr);
                    $statements = $this->_adapter->query($alterQuery);
                    $statements->execute();
                }
            }
            $query = "Delete From field Where field_id=?";
            $statements = $this->_adapter->createStatement($query, array($fieldId));
            $statements->execute();
            $statements_val = $this->_adapter->query("DELETE FROM field_validations WHERE field_id = ".$fieldId);
            $statements_val->execute();
            $connection->commit();
            $responseCode = 1;
            $errMessage = 'Field Deleted Successfully';
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While deleting Field'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While deleting field'; 
        }
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    
    public function getTrackerDetails($trackerId) 
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
    
    public function getValidationRule($fieldtype)
    { 
        try {
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('field_validations_rules');
            $select->where(array('datatype' => $fieldtype));
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
        } catch (\Exception $e) {
            return 'dberror';
        }
    }
    public function getformulafields($formId)
    {
        $query = "Select field.*
         from field
         INNER JOIN workflow On field.workflow_id = workflow.workflow_id
         INNER JOIN form ON workflow.form_id = form.form_id
         Where form.form_id=? AND (field.field_type = 'Formula' OR field.field_type = 'Formula Combo Box' OR field.field_type = 'Dependent Text' OR field.field_type = 'Formula Date' OR field.field_type = 'Formula Check Box' )";
        $statements = $this->_adapter->createStatement($query, array($formId));
        $statements->prepare();
        $arr = array();
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
    public function trackerCheckFieldsForFormula($trackerId, $actionId)
    {
        $tableName = "form_$trackerId" . "_$actionId";
        $queryField = "show columns from $tableName Where Field NOT IN('id', 'created_by', 'last_updated_by')";
        $statements = $this->_adapter->createStatement($queryField);
        $statements->prepare();
        $resArr = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        return $resArr;
    }
    public function getFormulaList()
    {
        $query = "Select * from formula";
        $statements = $this->_adapter->createStatement($query);
        $statements->prepare();
        $arr = array();
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
    public function getFormulaForField($fieldId)
    {
        $query = "Select field_id,formula from field where field_id=?";
        $statements = $this->_adapter->createStatement($query, array($fieldId));
        $statements->prepare();
        $arr = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arrData = $resultSet->toArray();
            $arr = $arrData[0];
        }
        return $arr;
    }
    public function saveFormula($fieldId, $formula)
    {
        $connection = null;
        $resultsArr = array();
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            $formula = html_entity_decode($formula);
            $formulaDependent = "";
            $formulaDependentArray = array();
            if (preg_match_all('/\{\{(.*?)\}\}/', $formula, $match)) {
                $formulaDependentArray = $match[1];
            }
            if (!empty($formulaDependentArray)) {
                $formulaDependent = implode(',', $formulaDependentArray);
            }
            $sql = new Sql($this->_adapter);
            $newData = array(
                'formula' => $formula,
                'formula_dependent' => $formulaDependent
            );
            $update = $sql->update('field')
                ->set($newData)
                ->where(array('field_id' => $fieldId));
            $statement = $sql->prepareStatementForSqlObject($update);
            $statement->execute();
            $connection->commit();
            
            $responseCode = 1;
            $errMessage = "Formula Field updated Successfully";
            
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While updating Formula Field'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While updating Formula Field'; 
        }
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    
    public function getFieldTypes() 
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field_datatypes');
        $select->columns(array('name'=>'type_name', 'label'=>'type_label'));
        $select->where(array('archived' => 'No'));
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

