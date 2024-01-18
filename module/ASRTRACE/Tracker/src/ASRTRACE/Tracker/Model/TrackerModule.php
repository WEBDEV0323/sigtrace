<?php

namespace ASRTRACE\Tracker\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Session\Container;
use Session\Container\SessionContainer;
class TrackerModule
{
    protected $_adapter;
    protected $_serviceLocator;
    protected $_adminmapper;
    public function __construct(Adapter $adapter)
    {
        $this->_adapter  = $adapter;
    }
    public function dashboardRsults()
    {
        $container = new Container('user');
        $trackerContainer = new Container('tracker');
        $clientsesults = 0;
        $userDetails = $container->user_details ;
        $uId = $container->u_id;
        $roleId = $userDetails['group_id'];
        $queryTracker = "SELECT DISTINCT user_role_tracker . u_id , tracker.name, tracker.tracker_id
            FROM user_role_tracker
            JOIN `group` ON group.group_id = user_role_tracker.group_id
            LEFT JOIN tracker ON group.tracker_id = tracker.tracker_id
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
        $count = @$resultSet->count();
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
    public function idCheck($id)
    {
        $illegal = "#$@_!\"%^&*()+=-[]';,./{}|:<>?~";
        $id = (strpbrk($id, $illegal) === false) ? $id : 0;
        return $id;
    }
    public function trackerRsults($trackerId)
    {
        $container = new Container('user');
        $trackerContainer = new Container('tracker');
        if (!isset($container)) {
            $container = new Container('user');
        }
        $userDetails = $container->user_details ;
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
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        if (count($arr)>0) {
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
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        if (count($arr)>0) {
            $resArr['tracker_details'] = $arr[0];
        }
        return $resArr;
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
        $count = @$resultSet->count();
        if ($count > 0) {
            $responseCode = 1;
            $resArr['responseCode'] = $responseCode;
            $arr = $resultSet->toArray();
            $table_details = $arr[0];
            $table_name = $table_details['form_name'];
            $resArr['form_details'] = $table_details;
        } else {
            $responseCode = 0;
            $errMessage = "Form Not Found";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
            $resArr['form_details'] = array();
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
        $count = @$resultSet->count();
        $resultsArr = array();
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
        $count = @$resultSet->count();
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        return $resArr;
    }
    public function trackerGetFormFields($trackerId, $actionId)
    {
        $container = new Container('user');
        $trackerContainer = new Container('tracker');
        $resArr = array();
        $fid = '';
        $userDetails = $container->user_details;
        $roleId = $userDetails['group_id'];
        $queryFormFields = "SELECT field.*, workflow.workflow_name
        FROM field
        LEFT JOIN workflow ON field.workflow_id = workflow.workflow_id
        WHERE workflow.form_id = ?  ORDER BY workflow.sort_order ASC, field.sort_order ASC";
        $tracker_user_groups = @$trackerContainer->tracker_user_groups;
        $sessionGroup = @$tracker_user_groups[$trackerId]['session_group'];
        $sessionGroupId = @$tracker_user_groups[$trackerId]['session_group_id'];
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            $queryFormFields = "SELECT field . * , workflow.workflow_name, workflow_role.role_id, workflow_role.can_update
            FROM field
            JOIN workflow ON field.workflow_id = workflow.workflow_id
            JOIN workflow_role ON workflow_role.workflow_id = workflow.workflow_id
            WHERE workflow.form_id = ? AND workflow_role.role_id = $sessionGroupId
            ORDER BY workflow.sort_order ASC , field.sort_order ASC";
        }
        $statements = $this->_adapter->createStatement($queryFormFields, array($actionId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $fieldsArr = array();
        if ($count > 0) {
            $fieldsArr = $resultSet->toArray();
        }

        $workflowArray = array();
        $wkfArr = array();
        $roleForUser = 0;
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
                        left join `group` on user_role_tracker.group_id=`group`.group_id
                        join field on field.formula=`group`.group_id and field.field_type='User' and field.field_id in (" . $fid . ") and user.status='Active'";

            $statements = $this->_adapter->query($qryforroles);
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = @$resultSet->count();
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
    public function trackerCheckWorkFlows($trackerId, $formId,$roleId,$roleName)
    {
        if ($roleName=='SuperAdmin'||$roleName=='Administrator') {
            $queryClients = "SELECT workflow_id, workflow_name, 'Yes' as can_read, 'Yes' as can_update FROM workflow Where form_id = ? and `status`=? ORDER BY sort_order";
            $statements = $this->_adapter->createStatement($queryClients, array($formId,'Active'));
        } else {
            $queryClients = "SELECT w.workflow_id, w.workflow_name, wr.can_read, wr.can_update FROM workflow as w LEFT JOIN workflow_role as wr on w.workflow_id=wr.workflow_id WHERE w.form_id = ? and wr.role_id = ? and w.status=? ORDER BY w.sort_order";
            $statements = $this->_adapter->createStatement($queryClients, array($formId,$roleId,'Active'));
        }
        //echo"<pre>";print_r($workflowArray);die;
        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $resultsArr = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        
        return $arr;
    }
    public function getMasterWorkFlows()
    {
        $queryClients = "SELECT * FROM master_workflow";
        $statements = $this->_adapter->createStatement($queryClients);
        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $resultsArr = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function getWorkFlows($trackerId, $actionId)
    {
        $queryClients = "SELECT * FROM workflow where form_id=?";
        $statements = $this->_adapter->createStatement($queryClients, array($actionId));
        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $resultsArr = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function getMasterFields($masterWorkflowId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('master_field');
        $select->where(array('master_workflow_id ' => $masterWorkflowId));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $arr = array();
        $resArr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function saveworkflow($dataArr)
    {
        $container = new Container('user');
        $wfNames = $dataArr['wf_names'];
        $formId = $dataArr['action_id'];
        $subType = $dataArr['subType'];
        $wfSortOrder = $dataArr['wf_sort_order'];
        $wfsArr = array();

        foreach ($wfNames as $key => $value) {
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('master_workflow');
            $select->where(array('workflow_name ' => "$value"));
            $newadpater = $this->_adapter;
            $selectString = $sql->prepareStatementForSqlObject($select);
            //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
            $results = $selectString->execute();
            $arr = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            $wfName = $value;
            $mWfId = 0;
            if ($count > 0) {
                $arr = $resultSet->toArray();
                $mWfId = $arr[0]['master_workflow_id'];
            }
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('workflow');
            $select->where(array('workflow_name ' => "$value", 'form_id' => $formId));
            $newadpater = $this->_adapter;
            $selectString = $sql->prepareStatementForSqlObject($select);
            //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
            $results = $selectString->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count == 0) {
                $sort_order = $wfSortOrder[$key];
                $sql = new Sql($this->_adapter);
                $insert = $sql->insert('workflow');
                $newData = array(
                    'workflow_name' => $wfName,
                    'form_id' => $formId,
                    'master_workflow_id' => $mWfId,
                    'sort_order' => $sort_order,
                );
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
                $results = $statement->execute();
                $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
                $wf = array();
                $wf['wf_id'] = $lastInsertID;
                $wf['wf_name'] = $wfName;
                $wf['master_workflow_id'] = $mWfId;
                $wfsArr[] = $wf;
            }
        }
        if ($subType == 'next') {
            $container->wfs = $wfsArr;
        }
        $resArr = array();
        $responseCode = 1;
        $errMessage = "Workflow successFully created";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function updateworkflow($dataArr)
    {
        $wfNames = @$dataArr['wf_names'];
        $wfIdForSort = @$dataArr['wf_id_for_sort'];
        $subType = @$dataArr['subType'];
        $wfSortOrder = @$dataArr['wf_sort_order'];
        $wfsArr = array();
        foreach ($wfNames as $key => $value) {
            $workflowId = $wfIdForSort[$key];
            $sortOrder = $wfSortOrder[$key];
            $sql = new Sql($this->_adapter);
            $newData = array(
                'workflow_name' => $value,
                'sort_order' => $sortOrder,
            );
            $update = $sql->update('workflow')
                ->set($newData)
                ->where(array('workflow_id' => $workflowId));  //print_r($update);die;
            $selectString = $sql->prepareStatementForSqlObject($update);
            //$results = $this->_adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
            $results = $selectString->execute();
        }
        $responseCode = 1;
        $errMessage = "SccessFully updated";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function editworkflow($dataArr)
    {
        $wfName = @$dataArr['workflow_name'];
        $wfId = @$dataArr['workflow_id'];
        $status = @$dataArr['status'];
        $sql = new Sql($this->_adapter);
        $newData = array(
            'workflow_name' => $wfName,
            'status' => $status,
        );
        $update = $sql->update('workflow')
            ->set($newData)
            ->where(array('workflow_id' => $wfId));  //print_r($update);die;
        $selectString = $sql->prepareStatementForSqlObject($update);
        //$results = $this->_adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
        $results = $selectString->execute();
        $responseCode = 1;
        $errMessage = "SccessFully updated";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function savenewFields($dataArray)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession("tracker");
        $container = new Container('user');
        $workflowContainer = new Container('workflow');
        $formulaFieldsContainer = new Container('formula_fields');
        $trackerId = @$dataArray['tracker_id'];
        $actionId = $dataArray['action_id'];
        $workflowId = $dataArray['workflow_id'];
        $labelArr = @$dataArray['names'];
        $namesArr = @$dataArray['label_names'];
        $types = @$dataArray['types'];
        $formulas = @$dataArray['formula_id'];
        $expected = @$dataArray['expected'];

        $kpivalues = @$dataArray['kpivalues'];
        $fieldSortOrder = @$dataArray['field_sort_order'];
        $formId = $actionId;

        $formulaArray = array();
        $tableArr = array();

        if (is_array($namesArr)) {
            $i = 0;
            foreach ($namesArr as $key => $fieldName) {
                $query = "Select field.* From field INNER JOIN workflow On field.workflow_id = workflow.workflow_id
                 INNER JOIN form ON workflow.form_id = form.form_id Where form.form_id=? AND field.field_name = ?";
                $statements = $this->_adapter->createStatement($query, array($formId,$fieldName));
                $statements->prepare();
                $results = $statements->execute();
                $arr = array();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = @$resultSet->count();
                if ($count == 0) {
                    $label = $labelArr[$key];
                    $fieldType = $types[$key];
                    $options = $expected[$key];
                    $kpi = $kpivalues[$key];
                    $sortOrder = $fieldSortOrder[$key];
                    $sql = new Sql($this->_adapter);
                    $insert = $sql->insert('field');
                    $newData = array(
                        'field_name' => $fieldName,
                        'workflow_id' => $workflowId,
                        'label' => $label,
                        'kpi' => $kpi,
                        'sort_order' => $sortOrder,
                        'field_type' => $fieldType,
                        'code_list_id' => $options,
                    );
                    if ($fieldType == "User") {
                        $newData['formula'] = @$formulas[$key];
                    }
                    $insert->values($newData);
                    $statement = $sql->prepareStatementForSqlObject($insert);
                    $results = $statement->execute();

                    $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
                    $tableArrVal = array();
                    $tableArrVal['field_id'] = $lastInsertID;
                    $tableArrVal['field_name'] = $fieldName;
                    $tableArrVal['type'] = $fieldType;
                    $tableArrVal['label'] = $label;
                    if ($fieldType == 'Formula') {
                        $container->formula_fields[] = $tableArrVal;
                        $formula_sess = 1;
                    }
                    $field_name = $tableArrVal['field_name'];
                    $type = $tableArrVal['type'];

                    $fielLabel = "ADD COLUMN $fieldName" . " ";
                    if ($type == "Integer") {
                        $fielLabel .= "INT(11) COMMENT '" . addslashes($label) . "'";
                    } elseif ($type == "Date") {
                        $fielLabel .= "DATE COMMENT '" . addslashes($label) . "'";
                    } elseif ($type == "TextArea") {
                        $fielLabel .= "TEXT COMMENT '" . addslashes($label) . "'";
                    } else {
                        $fielLabel .= "VARCHAR(255) COMMENT '" . addslashes($label) . "'";
                    }
                    $tableArr[] = $fielLabel;
                    if ($type == "Check Box") {
                        $fielLabel = "ADD COLUMN comment_checkbox_$fieldName" . " ";
                        $fielLabel .= "TEXT";
                        $tableArr[] = $fielLabel;

                        $fielLabel = "ADD COLUMN critical_checkbox_$fieldName" . " ";
                        $fielLabel .= "INT(11) DEFAULT 0";
                        $tableArr[] = $fielLabel;

                        $fielLabel = "ADD COLUMN major_checkbox_$fieldName" . " ";
                        $fielLabel .= "INT(11) DEFAULT 0";
                        $tableArr[] = $fielLabel;

                        $fielLabel = "ADD COLUMN minor_checkbox_$fieldName" . " ";
                        $fielLabel .= "INT(11) DEFAULT 0";
                        $tableArr[] = $fielLabel;
                    }
                } else {
                    $time = time();
                    $fieldName .= "_$time" . "" . $i;
                    $label = $labelArr[$key];
                    $fieldType = $types[$key];
                    $options = $expected[$key];
                    $kpi = $kpivalues[$key];
                    $sortOrder = $fieldSortOrder[$key];
                    $sql = new Sql($this->_adapter);
                    $insert = $sql->insert('field');
                    $newData = array(
                        'field_name' => $fieldName,
                        'workflow_id' => $workflowId,
                        'label' => $label,
                        'kpi' => $kpi,
                        'sort_order' => $sortOrder,
                        'field_type' => $fieldType,
                        'code_list_id' => $options
                    );
                    if ($fieldType == "User") {
                        $newData['formula'] = @$formulas[$key];
                    }
                    $insert->values($newData);
                    $statement = $sql->prepareStatementForSqlObject($insert);
                    $results = $statement->execute();
                    $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
                    $tableArrVal = array();
                    $tableArrVal['field_id'] = $lastInsertID;
                    $tableArrVal['field_name'] = $fieldName;
                    $tableArrVal['type'] = $fieldType;
                    $tableArrVal['label'] = $label;
                    if ($fieldType == 'Formula') {
                        $container->formula_fields[] = $tableArrVal;
                        $formula_sess = 1;
                    }
                    $fieldName = $tableArrVal['field_name'];
                    $type = $tableArrVal['type'];
                    $fielLabel = "ADD COLUMN $fieldName" . " ";
                    if ($type == "Integer") {
                        $fielLabel .= "INT(11) COMMENT '" . addslashes($label) . "'";
                    } elseif ($type == "Date") {
                        $fielLabel .= "DATE COMMENT '" . addslashes($label) . "'";
                    } elseif ($type == "TextArea") {
                        $fielLabel .= "TEXT COMMENT '" . addslashes($label) . "'";
                    } else {
                        $fielLabel .= "VARCHAR(255) COMMENT '" . addslashes($label) . "'";
                    }
                    $tableArr[] = $fielLabel;
                    if ($type == "Check Box") {
                        $fielLabel = "ADD COLUMN comment_checkbox_$fieldName" . " ";
                        $fielLabel .= "TEXT";
                        $tableArr[] = $fielLabel;

                        $fielLabel = "ADD COLUMN critical_checkbox_$fieldName" . " ";
                        $fielLabel .= "INT(11) DEFAULT 0";
                        $tableArr[] = $fielLabel;

                        $fielLabel = "ADD COLUMN major_checkbox_$fieldName" . " ";
                        $fielLabel .= "INT(11) DEFAULT 0";
                        $tableArr[] = $fielLabel;

                        $fielLabel = "ADD COLUMN minor_checkbox_$fieldName" . " ";
                        $fielLabel .= "INT(11) DEFAULT 0";
                        $tableArr[] = $fielLabel;
                    }
                }
                $i++;
            }
            if (count($tableArr) > 0) {
                $alter = implode(', ', $tableArr);
                $table_name = "form_$trackerId" . "_$actionId";
                $queryAlter = "ALTER TABLE $table_name $alter";

                $statements = $this->_adapter->createStatement($queryAlter);
                $statements->prepare();
                $results = $statements->execute();
            }
        }
        $subType = @$dataArray['subType'];
        if (isset($subType)) {
            $sessKey = @$dataArray['sess_key'];
            if ($subType == 'next') {
                unset($workflowContainer->wfs->$sessKey);
            }
        }
        $formulaSess = 0;
        if (count($formulaArray) > 0) {
            $formulaFieldsContainer->formula_fields = $formulaArray;
            $formulaSess = 1;
        }
        $responseCode = 1;
        $errMessage = "Fields successFully created";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        $resArr['formula'] = $formulaSess;
        return $resArr;
    }
    public function editfields($dataArray)
    {
        $trackerId = $dataArray['tracker_id'];
        $actionId = $dataArray['action_id'];
        $workflowId = $dataArray['workflow_id'];
        $namesArr = @$dataArray['names'];
        $types = @$dataArray['types'];
        $expected = @$dataArray['expected'];
        $kpivalues = @$dataArray['kpivalues'];
        $fieldSortOrder = @$dataArray['field_sort_order'];
        $fieldIdArr = @$dataArray['field_id_arr'];
        $table_arr = array();
        if (is_array($namesArr)) {
            foreach ($namesArr as $key => $value) {
                $label = $value;
                $string = preg_replace('/[^A-Za-z0-9]/', '', $label);
                $string = str_replace(" ", '_', $string);
                $string = str_replace("'", '_', $string);
                $string = str_replace('""', '_', $string);
                $fieldName = strtolower($string);
                $fieldId = $fieldIdArr[$key];
                $queryClients = "SELECT * FROM field WHERE field_name = ? AND workflow_id =?  AND field_id NOT IN ( ? )";
                $statements = $this->_adapter->createStatement($queryClients, array($fieldName,$workflowId,$fieldId));
                $statements->prepare();
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = $resultSet->count();
                if ($count == 0) {
                    $count;
                    $fieldType = $types[$key];
                    $options = $expected[$key];
                    $kpi = $kpivalues[$key];
                    $sortOrder = $fieldSortOrder[$key];
                    $sql = new Sql($this->_adapter);
                    $newData = array(
                        'workflow_id' => $workflowId,
                        'label' => $label,
                        'kpi' => $kpi,
                        'sort_order' => $sortOrder,
                        'field_type' => $fieldType,
                    );

                    $update = $sql->update('field')
                        ->set($newData)
                        ->where(array('field_id' => $fieldId));
                    $statement = $sql->prepareStatementForSqlObject($update);
                    $results = $statement->execute();
                }
            }
        }
        $responseCode = 1;
        $errMessage = "Fields successFully updated";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function trackerUsers($tracker_id)
    {
        $queryClients = "SELECT user. * , user_role_tracker.group_id, group.group_name
            FROM user
            LEFT JOIN user_role_tracker ON user.u_id = user_role_tracker.u_id
            LEFT JOIN `group` ON user_role_tracker.group_id = group.group_id
            WHERE user_role_tracker.tracker_id = '$tracker_id'";
        $statements = $this->_adapter->query($queryClients);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $resultsArr = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function formRecords($trackerId, $actionId, $sLimit, $search, $sOrder, $subactionId = 0)
    {
        $formName = 'form_' . $trackerId . '_' . $actionId;
        $queryClients = "SELECT * FROM $formName WHERE  is_deleted='No' ";
        if ($subactionId != 0) {
            if ($search != '') {
                $queryClients .= " AND id = $subactionId AND $search";
            } else {
                $queryClients .= " AND id = $subactionId";
            }
        } else {
            if ($search != '') {
                $queryClients.= " AND $search";
            }
        }
        $queryClients .= $sOrder;
        $query = "SELECT count(id) as tot FROM $formName WHERE  is_deleted='No' ";
        if ($subactionId != 0) {
            if ($search != '') {
                $query .= " AND id = $subactionId AND $search";
            } else {
                $query .= " AND id = $subactionId";
            }
        } else {
            if ($search != '') {
                $query.= " AND $search";
            }
        }
        $queryClients .= $sLimit;
        $statements = $this->_adapter->query($queryClients);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $resultsArr = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $resArr['form_data'] = $arr;
        $statements = $this->_adapter->query($query);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $resArr['tot'] = $arr[0]['tot'];
        return $resArr;
    }
    public function settingRsults($actionId)
    {
        $queryCount = "Select count(*) as wf_count from workflow where form_id = ?";
        $statements = $this->_adapter->createStatement($queryCount, array($actionId));
        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $resultsArr = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $resultsArr['wf_count'] = $arr[0]['wf_count'];
        $queryCount = "SELECT count( * ) as field_count
        FROM `field`
        JOIN workflow ON field.workflow_id = workflow.workflow_id
        WHERE workflow.form_id = ?";
        $statements = $this->_adapter->createStatement($queryCount, array($actionId));
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
        $resultsArr['field_count'] = $arr[0]['field_count'];
        return $resultsArr;
    }
    public function saveaccesssetting($dataArray)
    {
        $formId = $dataArray['form_id'];
        $roleId = $dataArray['role_id'];
        $canInsert = isset($dataArray['can_insert']) && !empty($dataArray['can_insert']) ? $dataArray['can_insert'] : '';
        $canDelete = isset($dataArray['can_delete']) && !empty($dataArray['can_delete']) ? $dataArray['can_delete'] : '';

        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('workflow');
        $select->where(array('form_id' => $formId, 'status ' => 'Active'));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $results = $selectString->execute();
        $array = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $array = $resultSet->toArray();
        foreach ($array as $workflowarray) {
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $workflowId = $workflowarray['workflow_id'];
            $select->from('workflow_role');
            $select->where(array('workflow_id ' => $workflowId, 'role_id ' => $roleId));
            $newadpater = $this->_adapter;
            $selectString = $sql->prepareStatementForSqlObject($select);
            //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
            $results = $selectString->execute();
            $arr = array();
            $resArr = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            $newData = array();
            if ($count > 0) {
                $arr = $resultSet->toArray();
                $newData = array(
                    'can_insert' => $canInsert,
                    'can_delete' => $canDelete
                );
                $update = $sql->update('workflow_role')
                    ->set($newData)
                    ->where(
                        array(
                        'workflow_id' => $workflowId, 'role_id' => $roleId
                        )
                    );
                $statement = $sql->prepareStatementForSqlObject($update);
            } else {
                $newData = array(
                    'can_insert' => $canInsert,
                    'can_delete' => $canDelete,
                    'workflow_id' => $workflowId,
                    'role_id' => $roleId
                );
                $insert = $sql->insert('workflow_role');
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
            }
            $results = $statement->execute();
        }
        $responseCode = 1;
        $errMessage = "Success";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function newformadd($dataArr)
    {
        $userContainer= new Container('user');
        $trackerId = $dataArr['tracker_id'];
        $formName = $dataArr['form_name'];
        $record = $dataArr['record'];
        $uId = $userContainer->u_id;
        $description = $dataArr['description'];
        $queryTracker = "SELECT * FROM form Where tracker_id = ? AND form_name = ? ";
        $trackerId = htmlspecialchars($trackerId, ENT_QUOTES);
        $formName = htmlspecialchars($formName, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($queryTracker, array($trackerId, $formName));
        $resArr = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();

        if ($count > 0) {
            $responseCode = 0;
            $errMessage = "Form Already Exist";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
        } else {
            $responseCode = 1;
            $sql = new Sql($this->_adapter);
            $insert = $sql->insert('form');
            $newData = array(
                'tracker_id' => $trackerId,
                'form_name' => $formName,
                'record_name' => $record,
                'description' => $description,
                'created_by' => $uId
            );
            $insert->values($newData);
            $statement = $sql->prepareStatementForSqlObject($insert);
            $results = $statement->execute();
            $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
            $query = "Create Table form_$trackerId" . "_$lastInsertID (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, created_by INT(6), last_updated_by INT(6), `created_date_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, `is_deleted` ENUM('Yes','No') NOT NULL DEFAULT 'No') collate utf8_general_ci";
            $statements = $this->_adapter->query($query);
            $results = $statements->execute();
            $errMessage = "Form successfully created";
            $resArr['responseCode'] = $responseCode;
            $resArr['errMessage'] = $errMessage;
            $resArr['form_id'] = $lastInsertID;
        }
        return $resArr;
    }
    public function deleteWorkflow($dataArr)
    {
        $workflowId = $dataArr['workflowID'];
        $workflowId = filter_var($workflowId, FILTER_SANITIZE_STRING);
        $trackerId = $dataArr['tracker_id'];
        $trackerId = filter_var($trackerId, FILTER_SANITIZE_STRING);
        $formId = $dataArr['form_id'];
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field');
        $select->where(array('workflow_id' => $workflowId));
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
        if (array_key_exists(0, $resFieldsArr)) {
            $alterArr = array();
            $alterQuery = "ALTER TABLE form_$trackerId" . "_$formId ";
            foreach ($resFieldsArr as $key => $value) {
                $fieldName = $value['field_name'];
                $alterArr[] = "DROP $fieldName";
            }
            $alterQuery .= implode(',', $alterArr);
            $statements = $this->_adapter->query($alterQuery);
            $results = $statements->execute();
            $count = $results->count();
        }
        $query = "Delete From field Where workflow_id=?";
        $workflowId = htmlspecialchars($workflowId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($query, array($workflowId));
        $results = $statements->execute();
        $query = "Delete From workflow_role Where workflow_id=?";
        $workflowId = htmlspecialchars($workflowId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($query, array($workflowId));
        $results = $statements->execute();
        $query = "Delete From workflow Where workflow_id=?";
        $workflowId = htmlspecialchars($workflowId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($query, array($workflowId));
        $results = $statements->execute();
        $this->workflowSort($formId);
    }
    public function workflowSort($formId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('workflow');
        $select->where(array('form_id' => $formId));
        $select->order('sort_order ASC');
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
        for ($j=0; $j<count($resFieldsArr); $j++) {
            $res=$resFieldsArr[$j];
            for ($i=1;$i<=count($res);$i++) {
                if ($res['sort_order']!=($j+1)) {
                    $newData = array(
                       'sort_order' => ($j+1)
                    );
                    $update = $sql->update('workflow')
                        ->set($newData)
                        ->where(
                            array('workflow_id' => $res['workflow_id'])
                        );
                    $statement = $sql->prepareStatementForSqlObject($update);
                    $statement->execute();
                }
                break;
            }
        }
    }
    public function deleteField($dataArr)
    {
        $fieldId = trim(addslashes($dataArr['fieldID']));
        $trackerId = trim(addslashes($dataArr['tracker_id']));
        $formId = trim(addslashes($dataArr['form_id']));
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
        if (array_key_exists(0, $resFieldsArr)) {
            $alterArr = array();
            $alterQuery = "ALTER TABLE form_$trackerId" . "_$formId ";

            foreach ($resFieldsArr as $key => $value) {
                $fieldName = $value['field_name'];
                $alterArr[] = "DROP $fieldName";
            }
            $alterQuery .= implode(',', $alterArr);
            $statements = $this->_adapter->query($alterQuery);
            $results = $statements->execute();
            $count = $results->count();
        }
        $query = "Delete From field Where field_id=?";
        $fieldId = htmlspecialchars($fieldId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($query, array($fieldId));
        $results = $statements->execute();
        $statements_val = $this->_adapter->query("DELETE FROM field_validations WHERE field_id = ".$fieldId);
        $results_val = $statements_val->execute();
        $count = $results->count();
    }

    public function deleteForm($data)
    {
        $formId = $data['form_id'];
        $trackerId = $data['tracker_id'];
        $sql = new Sql($this->_adapter);

        $query = "Delete field From field INNER JOIN workflow On field.workflow_id = workflow.workflow_id
            INNER JOIN form ON workflow.form_id = form.form_id Where form.form_id=?";
        $formId = htmlspecialchars($formId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($query, array($formId));
        $results = $statements->execute();
        $count = $results->count();

        $query = "Delete workflow_role From workflow_role
            INNER JOIN workflow On workflow_role.workflow_id = workflow.workflow_id
            INNER JOIN form ON workflow.form_id = form.form_id Where form.form_id=?";
        $formId = htmlspecialchars($formId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($query, array($formId));
        $results = $statements->execute();
        $count = $results->count();

        $query = "Delete workflow From workflow
            INNER JOIN form ON workflow.form_id = form.form_id Where form.form_id=?";
        $formId = htmlspecialchars($formId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($query, array($formId));
        $results = $statements->execute();
        $count = $results->count();

        $query = "Delete  From form Where form_id=?";
        $formId = htmlspecialchars($formId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($query, array($formId));
        $results = $statements->execute();
        $count = $results->count();

        $query = "DROP TABLE IF EXISTS form_$trackerId" . "_$formId";
        $responseCode = 1;
        $trackerId = htmlspecialchars($trackerId, ENT_QUOTES);
        $formId = htmlspecialchars($formId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($query, array($trackerId,$formId));
        $results = $statements->execute();
    }

    public function getsortlargest($actionId)
    {
        $query = "Select MAX(sort_order) as max_sort_num from workflow where form_id=?";
        $statements = $this->_adapter->createStatement($query, array($actionId));
        $statements->prepare();
        $resArr = array();
        $maxSortNumber = 0;
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $resFieldsArr = $resultSet->toArray();
            if ($resFieldsArr[0]['max_sort_num'] != null) {
                $maxSortNumber = $resFieldsArr[0]['max_sort_num'];
            }
        }
        return $maxSortNumber;
    }

    public function getmaxfieldsortNumber($dataArray)
    {
        $workflowId = $dataArray['workflow_id'];
        $workflowId = filter_var($workflowId, FILTER_SANITIZE_STRING);
        $query = "Select MAX(sort_order) as max_sort_num from field where workflow_id=?";
        $workflowId = htmlspecialchars($workflowId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($query, array($workflowId));
        $statements->prepare();
        $resArr = array();
        $maxSortNumber = 0;
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $resFieldsArr = $resultSet->toArray();
            if ($resFieldsArr[0]['max_sort_num'] != null) {
                $maxSortNumber = $resFieldsArr[0]['max_sort_num'];
            }
        }
        $responseCode = 1;
        $resArr['responseCode'] = $responseCode;
        $resArr['maxfieldSortNumber'] = $maxSortNumber;
        return $resArr;
    }

    public function getfieldsbyworkflowid($dataArray)
    {
        try {
            $workflowId = ($dataArray['workflow_id']);
            $workflowId = filter_var($workflowId, FILTER_SANITIZE_STRING);
            $workflowId = htmlspecialchars($workflowId, ENT_QUOTES);
            $query = "Select * from field where binary(workflow_id)=? ORDER BY sort_order";
            $statements = $this->_adapter->createStatement($query, array($workflowId));
            $resArr = array();
            $maxSortNumber = 0;
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = @$resultSet->count();
            $resFieldsArr = array();
            if ($count > 0) {
                $resFieldsArr = $resultSet->toArray();
            }
            $responseCode = $count;
            $resArr['responseCode'] = $responseCode;
            $resArr['results'] = $resFieldsArr;
            return $resArr;
        } catch (Exception $e) {
            echo "Caught exception $e\n";
            exit;
        }
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
        $count = @$resultSet->count();
        $resFieldsArr = array();
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        return $resArr;
    }
    public function getCodeListGroup()
    {
        $qry = 'call sp_getCodeListGroup()';
        $result = $this->_adapter->query($qry, Adapter::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($result);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $resCodeListArr = array();
        if ($count > 0) {
            $resArr = $resultSet->toArray();
            foreach ($resArr as $key => $value) {
                $codeListId = $value['code_list_id'];
                $options = $value['options'];
                $kpiValues = $value['kpi_values'];
                $resCodeListArr[$codeListId]['option_values'] = $options;
                $resCodeListArr[$codeListId]['kpi_values'] = $kpiValues;
            }
        }

        return $resCodeListArr;
    }
    public function accessSettingsAction()
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession("tracker");
        session_start();
        if (!isset($userContainer->u_id)) {
            return $this->redirect()->toRoute('home');
        } else {
            $trackerId = $this->params()->fromRoute('tracker_id', 0);
            $result = $this->getTrackerService()->getWorkflowRoleForForms($trackerId);
            return new ViewModel(
                array(
                    'resultset' => $result,
                )
            );
        }
    }
    public function addWorkflowRoleIdAction()
    {
        $request = $this->getRequest();
        $response = $this->getResponse();
        $post = $this->getRequest()->getPost()->toArray();
        $resultset = $this->getTrackerService()->addWorkflowRoleId($post);
        echo $resultset[0]['result'];
        die;
    }

    public function getCanInsertList($trackerId, $actionId)
    {
        $queryFormFields = "SELECT DISTINCT `role_id` FROM `workflow_role` where `can_insert`='Yes' and `role_id` in (SELECT `group_id` FROM `group` where `tracker_id`=?) and `workflow_id` in (SELECT `workflow_id` FROM `workflow` where `form_id`=?)";
        $statements = $this->_adapter->createStatement($queryFormFields, array( $trackerId,$actionId ));
        $results = $statements->execute();
        $arr=array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $res=array();
        foreach ($arr as $v) {
            $res[] = $v['role_id'];
        }
        return $res;
    }
    public function getWorkflowRoleForForms($trackerId)
    {
        $res = array();
        $connection = $this->_adapter->getDriver()->getConnection();
        $qry = 'call sp_getWorkflowRoleForForms("' . $trackerId . '")';
        $result = $connection->execute($qry);
        $statement = $result->getResource();
        $resultSet1 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[0] = $resultSet1;
        $statement->nextRowSet(); // Advance to the second result set
        $resultSet2 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[1] = $resultSet2;
        return $res;
    }
    public function addWorkflowRoleId($data)
    {
        $roleId = $data['roleId'];
        $workflowId = $data['workflowId'];
        $Read = $data['Read'];
        $Insert = $data['Insert'];
        $Update = $data['Update'];
        $Delete = $data['Delete'];
        $connection = $this->_adapter->getDriver()->getConnection();
        $qry = 'call sp_addWorflowRoleId("' . $roleId . '","' . $workflowId . '","' . $Read . '","' . $Insert . '","' . $Update . '","' . $Delete . '")';
        $result = $this->_adapter->query($qry, Adapter::QUERY_MODE_EXECUTE);
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($result);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function saverecord($data, $trackerId, $actionId, $filename)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession("tracker");
        $pregmatch_date_time = str_replace("'", "", $data["pregmatch_date_time"]);
        $pregmatch_date = str_replace("'", "", $data["pregmatch_date"]);
        $userDetails = $userContainer->user_details;
        $roleId = $userDetails['group_id'];
        $templateids = array();
        $queryFormFields = "SELECT field.field_name, field.field_type
        FROM field
        LEFT JOIN workflow ON field.workflow_id = workflow.workflow_id
        WHERE workflow.form_id = ? ORDER BY workflow.sort_order ASC, field.sort_order ASC";

        $actionId = htmlspecialchars($actionId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($queryFormFields, array( $actionId ));
        $trackerUserGroups = @$trackerContainer->tracker_user_groups;
        $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
        $sessionGroupId = @$trackerUserGroups[$trackerId]['session_group_id'];

        if ($roleId != 1 && $sessionGroup != "Administrator") {
            $queryFormFields = "SELECT field . field_name, field.field_type
            FROM field
            JOIN workflow ON field.workflow_id = workflow.workflow_id
            JOIN workflow_role ON workflow_role.workflow_id = workflow.workflow_id
            WHERE workflow.form_id = ? AND workflow_role.role_id = ? AND (workflow_role.can_insert = 'Yes' OR field.field_type='Formula')
            ORDER BY workflow.sort_order ASC , field.sort_order ASC";

            $action_id = htmlspecialchars($actionId, ENT_QUOTES);
            $sessionGroupId = htmlspecialchars($sessionGroupId, ENT_QUOTES);
            $statements = $this->_adapter->createStatement($queryFormFields, array( $action_id, $sessionGroupId ));
        }
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $fieldsArr = array();
        if ($count > 0) {
            $fieldsArr = $resultSet->toArray();
        }
        $queryfortemplate = "SELECT `notification_template`.* from notification_template WHERE `notification_template_form_id` = ? and notification_template_status='Active'";
        $actionId = htmlspecialchars($actionId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($queryfortemplate, array($actionId));
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();

        $count = @$resultSet->count();
        $templatearray = array();
        if ($count > 0) {
            $templatearray = $resultSet->toArray();
        }
        if (count($templatearray) > 0) {
            foreach ($templatearray as $arrayoftemplate) {
                $condition = '';
                $queryforfields = "SELECT * from `condition_for_templates` WHERE `condition_notification_template_id` = ? ";
                $arrayoftemplate['notification_template_id'] = htmlspecialchars($arrayoftemplate['notification_template_id'], ENT_QUOTES);
                $statements = $this->_adapter->createStatement($queryforfields, array($arrayoftemplate['notification_template_id']));
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = @$resultSet->count();
                $fieldsarray = array();
                if ($count > 0) {
                    $fieldsarray = $resultSet->toArray();
                }
                foreach ($fieldsarray as $fieldsarrays) {
                    if ($condition == '') {
                        $condition = '"' . $data[$fieldsarrays['condition_field_name']] . '"' . $fieldsarrays['condition_operand'] . '"' . $fieldsarrays['condition_value'] . '"';
                    } else {
                        if ($arrayoftemplate['notification_template_condition_type'] == 'AND') {
                            $operandtype = '&&';
                        } else {
                            $operandtype = '||';
                        }
                        $condition = $condition . ' ' . $operandtype . ' ' . '"' . $data[$fieldsarrays['condition_field_name']] . '"' . $fieldsarrays['condition_operand'] . '"' . $fieldsarrays['condition_value'] . '"';
                    }
                }
                if (eval("return $condition ;")) {
                    $templateids[] = $arrayoftemplate['notification_template_id'];
                }
            }
        }
        $checkBoxArray = array();
        $newData = array();
        $i=0;
        foreach ($fieldsArr as $key => $value) {
            $fieldName = $value['field_name'];
            $fieldType = $value['field_type'];
            if ($fieldType == 'Check Box') {
                $valuesCheckboxArr = $data[$fieldName];
                $checkBoxArray[$fieldName] = $valuesCheckboxArr;
                $optionsArray = array();
                foreach ($valuesCheckboxArr as $keyCheck => $valueCheck) {
                    $valueCheck=stripslashes(html_entity_decode($valueCheck));
                    $valuesCheckboxValues = json_decode($valueCheck, true);
                    $optionsArray[] = $valuesCheckboxValues['option'];
                }
                $newData[$fieldName] = implode(',', $optionsArray);
                $newData["comment_checkbox_$fieldName"] = addslashes(trim($data["comment_checkbox_$fieldName"]));
                $newData["critical_checkbox_$fieldName"] = $data["critical_checkbox_$fieldName"];
                $newData["major_checkbox_$fieldName"] = $data["major_checkbox_$fieldName"];
                $newData["minor_checkbox_$fieldName"] = $data["minor_checkbox_$fieldName"];
            } elseif ($fieldType == "Formula") {
                if (preg_match($pregmatch_date_time, $data[$fieldName], $matches)) {
                    $data[$fieldName] = trim($data[$fieldName]).":00";
                    $newData[$fieldName] = date("Y-m-d H:i:s", strtotime($data[$fieldName]));
                } elseif (preg_match($pregmatch_date, $data[$fieldName], $matches)) {
                    $newData[$fieldName] = date("Y-m-d", strtotime($data[$fieldName]));
                } else {
                    $data[$fieldName]=explode(":", $data[$fieldName]);
                    $newData[$fieldName] = $data[$fieldName][0];
                }
            } elseif ($fieldType == "Formula Date") {
                if ($data[$fieldName]!='') {
                    $data[$fieldName] = trim($data[$fieldName]).":00";
                    $data[$fieldName]=explode(':', $data[$fieldName]);
                    $newData[$fieldName] = date("Y-m-d", strtotime($data[$fieldName][0]));
                } else {
                    $newData[$fieldName] =$data[$fieldName];
                }
            } elseif ($fieldType == "Date") {
                $newData[$fieldName] = (trim($data[$fieldName]) != '')?date("Y-m-d", strtotime($data[$fieldName])):null;
            } elseif ($fieldType == "Date Time") {
                if (trim($data[$fieldName]) != '') {
                    $data[$fieldName] = trim($data[$fieldName]).":00";
                    $newData[$fieldName] = date("Y-m-d H:i:s", strtotime(trim($data[$fieldName])));
                } else {
                    $newData[$fieldName] = null;
                }
            } elseif ($fieldType == 'File') {
                if (isset($filename[$i])) {
                    $newData[$fieldName] = $filename[$i];
                    $i++;
                }
            } else {
                $newData[$fieldName] = addslashes(trim($data[$fieldName]));
            }
        }
        $newData['created_by'] = $userContainer->u_id;
        $sql = new Sql($this->_adapter);
        $formName = "form_$trackerId" . "_$actionId";
        $insert = $sql->insert($formName);
        $insert->values($newData);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $results = $statement->execute();
        $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();

        if (count($checkBoxArray) > 0) {
            foreach ($checkBoxArray as $key => $value) {
                $fieldName = $key;
                foreach ($value as $keyCheck => $valueCheckJson) {
                    $valueCheckJson=stripslashes(html_entity_decode($valueCheckJson));
                    $valuesArr = json_decode($valueCheckJson, true);
                    $optionName = $valuesArr['option'];
                    $kpi = $valuesArr['kpi'];

                    $sql = new Sql($this->_adapter);
                    $insert = $sql->insert('form_record_code_list');
                    $newData = array(
                        'form_id' => $actionId,
                        'record_id' => $lastInsertID,
                        'user_id' => $userContainer->u_id,
                        'field_name' => $fieldName,
                        'option_name' => $optionName,
                        'kpi' => $kpi
                    );
                    $insert->values($newData);
                    $statement = $sql->prepareStatementForSqlObject($insert);
                    $results = $statement->execute();
                }
            }
        }

        $resArr['responseCode'] = 1;
        $resArr['errMessage'] = "Inserted Success Fully";
        $resArr['record_id'] =  $lastInsertID;
        $resArr['templateids'] = $templateids;
        return $resArr;
    }
    public function saveeditrecord($data, $trackerId, $actionId, $subactionId, $filename)
    {
        $userContainer= new Container('user');
        $trackerContainer = new Container('tracker');
        $pregmatch_date_time = str_replace("'", "", $data["pregmatch_date_time"]);
        $pregmatch_date = str_replace("'", "", $data["pregmatch_date"]);
        unset($data["pregmatch_date_time"]);
        unset($data["pregmatch_date"]);
        $userDetails = $userContainer->user_details;
        $roleId = $userDetails['group_id'];
        $templateids = array();
        $queryFormFields = "SELECT field.field_name, field.field_type
        FROM field
        LEFT JOIN workflow ON field.workflow_id = workflow.workflow_id
        WHERE workflow.form_id = ? ORDER BY workflow.sort_order ASC, field.sort_order ASC";

        $actionId = htmlspecialchars($actionId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($queryFormFields, array( $actionId ));

        $trackerUserGroups = @$trackerContainer->tracker_user_groups;
        $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
        $sessionGroupId = @$trackerUserGroups[$trackerId]['session_group_id'];
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            $queryFormFields = "SELECT field . field_name, field.field_type
            FROM field
            JOIN workflow ON field.workflow_id = workflow.workflow_id
            JOIN workflow_role ON workflow_role.workflow_id = workflow.workflow_id
            WHERE workflow.form_id = ? AND workflow_role.role_id = ? AND ((workflow_role.can_update = 'Yes' OR workflow_role.can_update = 'SELF') OR field.field_type='Formula' )
            ORDER BY workflow.sort_order ASC , field.sort_order ASC";

            $action_id = htmlspecialchars($actionId, ENT_QUOTES);
            $sessionGroupId = htmlspecialchars($sessionGroupId, ENT_QUOTES);
            $statements = $this->_adapter->createStatement($queryFormFields, array( $action_id, $sessionGroupId ));
        }
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $fieldsArr = array();
        if ($count > 0) {
            $fieldsArr = $resultSet->toArray();
        }
        $queryfortemplate = "SELECT `notification_template`.* from notification_template WHERE `notification_template_form_id` = ? and notification_template_status='Active'";
        $actionId = htmlspecialchars($actionId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($queryfortemplate, array($actionId));
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();

        $count = @$resultSet->count();
        $templatearray = array();
        if ($count > 0) {
            $templatearray = $resultSet->toArray();
        }
        if (count($templatearray) > 0) {
            foreach ($templatearray as $arrayoftemplate) {
                $condition = '';
                $queryforfields = "SELECT * from `condition_for_templates` WHERE `condition_notification_template_id` = ? ";
                $arrayoftemplate['notification_template_id'] = htmlspecialchars($arrayoftemplate['notification_template_id'], ENT_QUOTES);
                $statements = $this->_adapter->createStatement($queryforfields, array($arrayoftemplate['notification_template_id']));
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = @$resultSet->count();
                $fieldsarray = array();
                if ($count > 0) {
                    $fieldsarray = $resultSet->toArray();
                }
                foreach ($fieldsarray as $fieldsarrays) {
                    if ($condition == '') {
                        $condition = '"' . $data[$fieldsarrays['condition_field_name']] . '"' . $fieldsarrays['condition_operand'] . '"' . $fieldsarrays['condition_value'] . '"';
                    } else {
                        if ($arrayoftemplate['notification_template_condition_type'] == 'AND') {
                            $operandtype = '&&';
                        } else {
                            $operandtype = '||';
                        }
                        $condition = $condition . ' ' . $operandtype . ' ' . '"' . $data[$fieldsarrays['condition_field_name']] . '"' . $fieldsarrays['condition_operand'] . '"' . $fieldsarrays['condition_value'] . '"';
                    }
                }
                if (eval("return $condition ;")) {
                    $templateids[] = $arrayoftemplate['notification_template_id'];
                }
            }
        }
        $newData = array();
        $checkBoxArray = array();
        $i=0;
        foreach ($fieldsArr as $key => $value) {
            $fieldName = $value['field_name'];
            $fieldType = $value['field_type'];
            if (array_key_exists($fieldName, $data)) {
                if ($fieldType == 'Check Box') {
                    $valuesCheckboxArr = $data[$fieldName];
                    $checkBoxArray[$fieldName] = $valuesCheckboxArr;
                    $optionsArray = array();
                    foreach ($valuesCheckboxArr as $keyCheck => $valueCheck) {
                        $valueCheck=stripslashes(html_entity_decode($valueCheck));
                        $valuesCheckboxValues = json_decode($valueCheck, true);
                        $optionsArray[] = $valuesCheckboxValues['option'];
                    }

                    $newData[$fieldName] = implode(',', $optionsArray);
                    $newData["comment_checkbox_$fieldName"] = (addslashes(trim($data["comment_checkbox_$fieldName"])));
                    $newData["critical_checkbox_$fieldName"] = $data["critical_checkbox_$fieldName"];
                    $newData["major_checkbox_$fieldName"] = $data["major_checkbox_$fieldName"];
                    $newData["minor_checkbox_$fieldName"] = $data["minor_checkbox_$fieldName"];
                } elseif ($fieldType == 'File') {
                    if (isset($filename[$fieldName])) {
                        $newData[$fieldName] = $filename[$fieldName];
                        $i++;
                    }
                } elseif ($fieldType == "Formula") {
                    if (preg_match($pregmatch_date_time, $data[$fieldName], $matches)) {
                        $data[$fieldName] = trim($data[$fieldName]).":00";
                        $newData[$fieldName] = date("Y-m-d H:i:s", strtotime($data[$fieldName]));
                    } elseif (preg_match($pregmatch_date, $data[$fieldName], $matches)) {
                        $data[$fieldName]=explode(':', $data[$fieldName]);
                        $newData[$fieldName] = date("Y-m-d", strtotime($data[$fieldName][0]));
                    } else {
                        $data[$fieldName]=explode(':', $data[$fieldName]);
                        $newData[$fieldName] =$data[$fieldName][0];
                    }
                } elseif ($fieldType == "Formula Date") {
                    if ($data[$fieldName]!='') {
                        $data[$fieldName] = trim($data[$fieldName]).":00";
                        $data[$fieldName]=explode(':', $data[$fieldName]);
                        $newData[$fieldName] = date("Y-m-d", strtotime($data[$fieldName][0]));
                    } else {
                        $newData[$fieldName] =$data[$fieldName];
                    }
                } elseif ($fieldType == "Date") {
                    $newData[$fieldName] = (trim($data[$fieldName]) != '')?date("Y-m-d", strtotime($data[$fieldName])):null;
                } elseif ($fieldType == "Date Time") {
                    if (trim($data[$fieldName]) != '') {
                        $data[$fieldName] = trim($data[$fieldName]).":00";
                        $newData[$fieldName] = date("Y-m-d H:i:s", strtotime(trim($data[$fieldName])));
                    } else {
                        $newData[$fieldName] = null;
                    }
                } else {
                    $newData[$fieldName] = addslashes(trim($data[$fieldName]));
                }
            } else {
                if ($fieldType == 'Check Box') {
                    $checkBoxArray[$fieldName] = array();
                    $newData[$fieldName] = '';
                    $newData["comment_checkbox_$fieldName"] = '';
                    $newData["critical_checkbox_$fieldName"] = 0;
                    $newData["major_checkbox_$fieldName"] = 0;
                    $newData["minor_checkbox_$fieldName"] = 0;
                }
                if ($fieldType == 'Formula Combo Box') {
                    $newData[$fieldName] = '';
                }
            }
        }

        $newData['last_updated_by'] = $userContainer->u_id;
        $sql = new Sql($this->_adapter);
        $formName = "form_$trackerId" . "_$actionId";

        $update = $sql->update($formName)
            ->set($newData)
            ->where(array('id' => $subactionId));

        $selectString = $sql->prepareStatementForSqlObject($update);
        $results = $selectString->execute();
        if (count($checkBoxArray) > 0) {
            foreach ($checkBoxArray as $key => $value) {
                $fieldName = $key;
                $query = "Delete From form_record_code_list Where record_id= ? AND field_name= ? ";
                $subactionId = htmlspecialchars($subactionId, ENT_QUOTES);                          
                $fieldName = htmlspecialchars($fieldName, ENT_QUOTES);
                $statements = $this->_adapter->createStatement($query, array($subactionId, $fieldName));
                $results = $statements->execute();
                foreach ($value as $keyCheck => $valueCheckJson) {
                    $valueCheckJson=stripslashes(html_entity_decode($valueCheckJson));
                    $valuesArr = json_decode($valueCheckJson, true);
                    $optionName = $valuesArr['option'];
                    $kpi = $valuesArr['kpi'];

                    $sql = new Sql($this->_adapter);
                    $insert = $sql->insert('form_record_code_list');
                    $newData = array(
                        'form_id' => $actionId,
                        'record_id' => $subactionId,
                        'user_id' => $userContainer->u_id,
                        'field_name' => $fieldName,
                        'option_name' => $optionName,
                        'kpi' => $kpi
                    );
                    $insert->values($newData);
                    $statement = $sql->prepareStatementForSqlObject($insert);
                    $results = $statement->execute();
                }
            }
        }
        $resArr['responseCode'] = 1;
        $resArr['errMessage'] = "Updated Success Fully";
        $resArr['templateids'] = $templateids;
        return $resArr;
    }
    public function recordsCanRead($trackerId, $actionId)
    {
        try {
            $userContainer= new Container('user');
            $trackerContainer = new Container('tracker');
            $userDetails = $userContainer->user_details;
            $roleId = $userDetails['group_id'];
            $queryFormFields = "SELECT field.field_name, field.field_type, field.label, field.search_field
                                FROM field
                                LEFT JOIN workflow ON field.workflow_id = workflow.workflow_id
                                WHERE workflow.form_id = ? ORDER BY workflow.sort_order ASC, field.sort_order ASC";
            $trackerUserGroups = @$trackerContainer->tracker_user_groups;
            $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
            $sessionGroupId = @$trackerUserGroups[$trackerId]['session_group_id'];
            if ($roleId != 1 && $sessionGroup != "Administrator") {
                $queryFormFields = "SELECT field . field_name, field.field_type, field.label, field.search_field
                workflow_role.can_read,
                workflow_role.can_insert,
                workflow_role.can_delete,
                workflow_role.can_update,
                workflow_role.role_id
                FROM field
                JOIN workflow ON field.workflow_id = workflow.workflow_id
                JOIN workflow_role ON workflow_role.workflow_id = workflow.workflow_id
                WHERE workflow.form_id = ? AND workflow_role.role_id = $sessionGroupId AND (workflow_role.can_read = 'Yes' OR workflow_role.can_read = 'SELF')
                ORDER BY workflow.sort_order ASC , field.sort_order ASC";
            }
            $actionId = htmlspecialchars($actionId, ENT_QUOTES);
            $statements = $this->_adapter->createStatement($queryFormFields, array($actionId));
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = @$resultSet->count();
            $fieldsArr = array();
            if ($count > 0) {
                $fieldsArr = $resultSet->toArray();
            }
            return $fieldsArr;
        } catch (\Zend\Db\Adapter\Exception $e) {
            return "Exeption";
        } catch (\Exception $e) {
            return "Exeption";
        }
    }
    public function allrecords($trackerId, $actionId)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');

        //$userContainer= new Container('user');
        //$trackerContainer = new Container('tracker');
        $userDetails = $userContainer->user_details;


        $roleId = $userDetails['group_id'];
        $queryFormFields = "SELECT field.field_name, field.field_type, field.label
        FROM field
        LEFT JOIN workflow ON field.workflow_id = workflow.workflow_id
        WHERE workflow.form_id = ? ORDER BY workflow.sort_order ASC, field.sort_order ASC";

        $trackerUserGroups = $trackerContainer->tracker_user_groups;
        $sessionGroup = $trackerUserGroups[$trackerId]['session_group'];
        $sessionGroupId = $trackerUserGroups[$trackerId]['session_group_id'];

        if ($roleId != 1 && $sessionGroup != "Administrator") {
            $queryFormFields = "SELECT field . field_name, field.field_type, field.label,
            workflow_role.can_read,
            workflow_role.can_insert,
            workflow_role.can_delete,
            workflow_role.can_update,
            workflow_role.role_id
            FROM field
            JOIN workflow ON field.workflow_id = workflow.workflow_id
            JOIN workflow_role ON workflow_role.workflow_id = workflow.workflow_id
            WHERE workflow.form_id = ? AND workflow_role.role_id = $sessionGroupId
            AND (workflow_role.can_read = 'Yes' OR workflow_role.can_read = 'SELF')
            ORDER BY workflow.sort_order ASC , field.sort_order ASC";
        }
        $statements = $this->_adapter->createStatement($queryFormFields, array($actionId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $fieldsArr = array();
        if ($count > 0) {
            $fieldsArr = $resultSet->toArray();
        }
        return $fieldsArr;
    }
    public function addnewcodelist($data)
    {
        $trackerId = $data['tracker_id'];
        $codeListName = $data['new_code_list'];
        $query = "Select * from code_list where code_list_name = ? AND tracker_id IN (?,?)";
        $statements = $this->_adapter->createStatement($query, array($codeListName,0,$trackerId));
        $statements->prepare();
        $resArr = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();

        if ($count > 0) {
            $responseCode = 0;
            $errMessage = "Code List Already Exist";
        } else {
            $sql = new Sql($this->_adapter);
            $insert = $sql->insert('code_list');
            $newData = array(
                'tracker_id' => $trackerId,
                'code_list_name' => $codeListName,
            );
            $insert->values($newData);
            $statement = $sql->prepareStatementForSqlObject($insert);
            $results = $statement->execute();
            $responseCode = 1;
            $errMessage = "Code List Created";
        }
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function editcodelist($data)
    {
        $trackerId = $data['tracker_id'];
        $codeLlistId = $data['edit_code_list_id'];
        $codeListName = $data['edit_code_list'];
        $query = "Select * from code_list where code_list_name = ? AND tracker_id = ? AND code_list_id NOT IN(?)";
        $statements = $this->_adapter->createStatement($query, array($codeListName,$trackerId,$codeLlistId));
        $statements->prepare();
        $resArr = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $responseCode = 0;
            $errMessage = "Code List Already Exist";
        } else {
            $sql = new Sql($this->_adapter);
            $newData = array(
                'code_list_name' => $codeListName,
            );
            $update = $sql->update('code_list')
                ->set($newData)
                ->where(array('code_list_id' => $codeLlistId)); // print_r($update);die;
            $statement = $sql->prepareStatementForSqlObject($update);
            $results = $statement->execute();
            $responseCode = 1;
            $errMessage = "Code List Updated";
        }
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function editfieldbyid($data)
    {
        $fId = $data['f_id'];
        $label = $data['fieldName'];
        $string = preg_replace('/[^A-Za-z0-9]/', '', $label);
        $string = str_replace(" ", '_', $string);
        $string = str_replace("'", '_', $string);
        $string = str_replace('""', '_', $string);
        $string = str_replace(" ", '_', $string);
        $fieldName = strtolower($string);
        $codeListId = $data['code_list_id'];
        $fieldType = $data['fieldType'];
        $formula = $data['role_id'];
        $kpi = $data['kpi'];
        $formId = $data['form_id'];
        $trackerId = $data['tracker_id'];
        $validation_required_edit=$data['validation_req'];
        $rule_id=$data['rule_id'];
        if (array_key_exists('rule_message', $data)) {
            $rule_message=$data['rule_message'];
        }
        if (array_key_exists('rule_value', $data)) {
            $rule_value=$data['rule_value'];
        }
        $query = "Select field.*
         from field
         INNER JOIN workflow On field.workflow_id = workflow.workflow_id
         INNER JOIN form ON workflow.form_id = form.form_id
         Where form.form_id=? AND field.formula = ? AND field.field_name = ? AND field.field_id NOT IN(?)";
        $formId = htmlspecialchars($formId, ENT_QUOTES);
        $formula = htmlspecialchars($formula, ENT_QUOTES);
        $fieldName = htmlspecialchars($fieldName, ENT_QUOTES);
        $fId = htmlspecialchars($fId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($query, array($formId, $formula, $fieldName, $fId));
        $resArr = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $sql = new Sql($this->_adapter);
        $newData = array(
            'label' => $label,
            'kpi' => $kpi,
            'field_type' => $fieldType,
            'code_list_id' => $codeListId,
            'formula' => $formula,
            'validation_required'=>$validation_required_edit
        );
        $update = $sql->update('field')
            ->set($newData)
            ->where(array('field_id' => $fId)); // print_r($update);die;
        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute();
        $responseCode = 1;
        $errMessage = "Field Updated";

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
            }
        } else {
            $statements = $this->_adapter->query("DELETE FROM field_validations WHERE field_id = ".$fId);
            $results = $statements->execute();
        }
        switch ($fieldType) {
        case "Check Box":
            $fieldName = $data['edit_field_name_hidden'];
            $tableName = "form_$trackerId" . "_$formId";
            $newField = "comment_checkbox_$fieldName";
            $queryField = "show columns from $tableName where Field = ?";
            $statements = $this->_adapter->createStatement($queryField, array($newField));
            $statements->prepare();
            $resArr = array();
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = @$resultSet->count();
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
                $fielLabel .= "INT(11)";
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
            $count = @$resultSet->count();
            if ($count == 0) {
                $fielLabel = "ADD COLUMN major_checkbox_$fieldName" . " ";
                $fielLabel .= "INT(11)";
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
            $count = @$resultSet->count();
            if ($count == 0) {
                $fielLabel = "ADD COLUMN minor_checkbox_$fieldName" . " ";
                $fielLabel .= "INT(11)";
                $tableArr = array();
                $tableArr[] = $fielLabel;
                $alter = implode(', ', $tableArr);
                $queryAlter = "ALTER TABLE $tableName $alter";
                $statements = $this->_adapter->query($queryAlter);
                $results = $statements->execute();
            }
            break;

        case 'Integer':
            $tableNname = "form_$trackerId" . "_$formId";
            $fieldName = $data['edit_field_name_hidden'];
            $queryAlter = "ALTER TABLE form_" . $trackerId . "_" . $formId . " MODIFY $fieldName INTEGER";
            $statements = $this->_adapter->query($queryAlter);
            $results = $statements->execute();
            break;
        case 'TextArea':
            $tableNname = "form_$trackerId" . "_$formId";
            $fieldName = $data['edit_field_name_hidden'];
            $queryAlter = "ALTER TABLE form_" . $trackerId . "_" . $formId . " MODIFY $fieldName TEXT";
            $statements = $this->_adapter->query($queryAlter);
            $results = $statements->execute();
            break;
        case 'Date':
        case 'Text':
        case 'Date Time':
        case 'Formula':
        case 'Combo Box':
        case 'User':
            $tableNname = "form_$trackerId" . "_$formId";
            $fieldName = $data['edit_field_name_hidden'];
            $queryAlter = "ALTER TABLE form_" . $trackerId . "_" . $formId . " MODIFY $fieldName VARCHAR(255)";
            $statements = $this->_adapter->query($queryAlter);
            $results = $statements->execute();
            break;
        default:
            break;
        }

        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function addoptionscodes($data)
    {
        $trackerId = $data['tracker_id'];
        $codeListId = $data['code_list_id'];
        $optionNamesArr = $data['names'];
        $kpiArr = $data['kpi'];
        foreach ($optionNamesArr as $key => $nameValue) {
            $query = "Select * from code_list_option where label = ? AND code_list_id = ? ";
            $nameValue = trim($nameValue);
            $statements = $this->_adapter->createStatement($query, array($nameValue,$codeListId));
            $statements->prepare();
            $resArr = array();
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = @$resultSet->count();
            if ($count == 0) {
                $kpi = $kpiArr[$key];
                $sql = new Sql($this->_adapter);
                $insert = $sql->insert('code_list_option');
                $newData = array(
                    'code_list_id' => $codeListId,
                    'value' => $nameValue,
                    'label' => $nameValue,
                    'kpi' => $kpi
                );
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
                $results = $statement->execute();
            }
        }
        $responseCode = 1;
        $errMessage = "Options Created";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function editoptionscodes($data)
    {
        $codeListId = $data['code_list_id'];
        $optionNamesArr = $data['names'];
        $kpiArr = $data['kpi'];
        $optionIds = $data['option_ids'];
        foreach ($optionNamesArr as $key => $nameValue) {
            $optionId = $optionIds[$key];
            $query = "Select * from code_list_option where label = ? AND code_list_id = ? AND option_id NOT IN(?)";
            $statements = $this->_adapter->createStatement($query, array($nameValue,$codeListId,$optionId));
            $statements->prepare();
            $resArr = array();
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = @$resultSet->count();
            if ($count == 0) {
                $kpi = $kpiArr[$key];
                $sql = new Sql($this->_adapter);
                $newData = array(
                    'value' => $nameValue,
                    'label' => $nameValue,
                    'kpi' => $kpi
                );

                $update = $sql->update('code_list_option')
                    ->set($newData)
                    ->where(array('option_id' => $optionId));
                $statement = $sql->prepareStatementForSqlObject($update);
                $results = $statement->execute();
            }
        }
        $responseCode = 1;
        $errMessage = "Options Updated";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function getoptionsbycodelist($data)
    {
        $codeListId = $data['code_list_id'];
        $query = "Select * from code_list_option where code_list_id = ?";
        $statements = $this->_adapter->createStatement($query, array($codeListId));
        $statements->prepare();
        $resArr = array();
        $arr = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $responseCode = 1;
        $resArr['responseCode'] = $responseCode;
        $resArr['results'] = $arr;
        return $resArr;
    }
    public function deletecodelistoption($data)
    {
        $optionId = $data['option_id'];
        $query = "Delete From code_list_option Where option_id=?";
        $statements = $this->_adapter->createStatement($query, array($optionId));
        $statements->prepare();
        $results = $statements->execute();
        return $count = $results->count();
    }
    public function getformulafields($formId)
    {
        $query = "Select field.*
         from field
         INNER JOIN workflow On field.workflow_id = workflow.workflow_id
         INNER JOIN form ON workflow.form_id = form.form_id
         Where form.form_id=? AND (field.field_type = 'Formula' OR field.field_type = 'Formula Combo Box' OR field.field_type = 'DependentText' OR field.field_type = 'Formula Date' )";
        $statements = $this->_adapter->createStatement($query, array($formId));
        $statements->prepare();
        $arr = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function saveformula($data)
    {
        $fieldId = $data['field_id'];
        $formula = $data['formula'];
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
            ->where(array('field_id' => $fieldId)); // print_r($update);die;
        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute();
        $responseCode = 1;
        $errMessage = "Success";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
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
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function recordsCanView($trackerId, $actionId)
    {
        $resArr = array();
        $userContainer= new Container('user');
        $trackerContainer = new Container('tracker');   
        $userDetails = $userContainer->user_details;
        $roleId = $userDetails['group_id'];
        $queryFormFields = "SELECT field.*, workflow.workflow_name
        FROM field
        LEFT JOIN workflow ON field.workflow_id = workflow.workflow_id
        WHERE workflow.form_id = ? ORDER BY workflow.sort_order ASC, field.sort_order ASC";
        $trackerUserGroups = @$trackerContainer->tracker_user_groups;
        $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
        $sessionGroupId = @$trackerUserGroups[$trackerId]['session_group_id'];
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            $queryFormFields = "SELECT field . * , workflow.workflow_name, workflow_role.role_id, workflow_role.can_read
            FROM field
            JOIN workflow ON field.workflow_id = workflow.workflow_id
            JOIN workflow_role ON workflow_role.workflow_id = workflow.workflow_id
            WHERE workflow.form_id = ? AND workflow_role.role_id = $sessionGroupId AND (workflow_role.can_read = 'Yes' OR workflow_role.can_read = 'Self')
            ORDER BY workflow.sort_order ASC , field.sort_order ASC";
        }
        $statements = $this->_adapter->createStatement($queryFormFields, array($actionId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $fieldsArr = array();
        if ($count > 0) {
            $fieldsArr = $resultSet->toArray();
        }
        $workflowArray = array();
        $wkfArr = array();
        foreach ($fieldsArr as $key => $value) {
            $workflowName = $value['workflow_name'];

            if (!in_array($workflowName, $workflowArray)) {
                $workflowArray[] = $value['workflow_name'];
            }
            $wkfArr[$workflowName][] = $value;
        }
        $resArr['workflows'] = $workflowArray;
        $resArr['fields'] = $wkfArr;
        return $resArr;
    }
    public function recordsCanEdit($trackerId, $actionId)
    {
        $resArr = array();
        $userContainer= new Container('user');
        $trackerContainer = new Container('tracker');
        $userDetails = $userContainer->user_details;
        $roleId = $userDetails['group_id'];
        $queryFormFields = "SELECT field.*, workflow.workflow_name
        FROM field
        LEFT JOIN workflow ON field.workflow_id = workflow.workflow_id
        WHERE workflow.form_id = ? ORDER BY workflow.sort_order ASC, field.sort_order ASC";
        $trackerUserGroups = @$trackerContainer->tracker_user_groups;
        $sessionGroup = @$trackerUserGroups[$trackerId]['session_group'];
        $sessionGroupId = @$trackerUserGroups[$trackerId]['session_group_id'];
        if ($roleId != 1 && $sessionGroup != "Administrator") {
            $queryFormFields = "SELECT field . * , workflow.workflow_name, workflow.form_id, workflow_role.role_id, workflow_role.can_update,
            workflow_role.can_read
            FROM field
            JOIN workflow ON field.workflow_id = workflow.workflow_id
            JOIN workflow_role ON workflow_role.workflow_id = workflow.workflow_id
            WHERE workflow.form_id = ? AND workflow_role.role_id = $sessionGroupId AND (workflow_role.can_read='Yes' OR workflow_role.can_read='Self')
            ORDER BY workflow.sort_order ASC , field.sort_order ASC";
        }
        $statements = $this->_adapter->createStatement($queryFormFields, array($actionId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
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
                        left join `group` on user_role_tracker.group_id=`group`.group_id
                        join field on field.formula=`group`.group_id and field.field_type='User' and field.field_id in (" . $fid . ")";

            $statements = $this->_adapter->query($qryforroles);
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = @$resultSet->count();
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
    public function deleterecord($trackerId, $actionId, $id)
    {
        $newData = array(
                    'is_deleted' => 'Yes',
                );
        $sql = new Sql($this->_adapter);
        $delete = $sql->Update('form_' . $trackerId . '_' . $actionId)->set($newData)
            ->where(
                array(
                'id' => $id
                )
            );
        $statement = $sql->prepareStatementForSqlObject($delete);
        $results = $statement->execute();
        $codelistdelete = $sql->Update('form_record_code_list')->set($newData)
            ->where(
                array(
                'record_id' => $id,
                'form_id' => $actionId
                )
            );
        $statement = $sql->prepareStatementForSqlObject($codelistdelete);
        $results = $statement->execute();
    }
    public function saveupdatesetting($dataArray)
    {
        $workflowIds = $dataArray['workflow_id'];
        $roleId = $dataArray['role_id'];
        $canUpdates = $dataArray['can_update'];
        $i = 0;
        foreach ($workflowIds as $workflowId) {
            $canUpdate = $canUpdates[$i];
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('workflow_role');
            $select->where(array('workflow_id ' => $workflowId, 'role_id ' => $roleId));
            $newadpater = $this->_adapter;
            $selectString = $sql->prepareStatementForSqlObject($select);
            //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
            $results = $selectString->execute();
            $arr = array();
            $resArr = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            $newData = array();
            if ($count > 0) {
                $arr = $resultSet->toArray();
                $newData = array(
                    'can_update' => $canUpdate,
                );
                $update = $sql->update('workflow_role')
                    ->set($newData)
                    ->where(
                        array(
                        'workflow_id' => $workflowId, 'role_id' => $roleId
                        )
                    );
                $statement = $sql->prepareStatementForSqlObject($update);
            } else {
                $newData = array(
                    'can_update' => $canUpdate,
                    'workflow_id' => $workflowId,
                    'role_id' => $roleId
                );
                $insert = $sql->insert('workflow_role');
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
            }
            $results = $statement->execute();
            $i++;
        }

        $responseCode = 1;
        $errMessage = "Success";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function savereadsetting($dataArray)
    {
        $workflowIds = $dataArray['workflow_id'];
        $roleId = $dataArray['role_id'];
        $canReads = $dataArray['can_read'];
        $i = 0;
        foreach ($workflowIds as $workflowId) {
            $canRead = $canReads[$i];
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('workflow_role');
            $select->where(array('workflow_id ' => $workflowId, 'role_id ' => $roleId));
            $newadpater = $this->_adapter;
            $selectString = $sql->prepareStatementForSqlObject($select);
            //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
            $results = $selectString->execute();
            $arr = array();
            $resArr = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            $newData = array();
            if ($count > 0) {
                $arr = $resultSet->toArray();
                $newData = array(
                    'can_read' => $canRead,
                );
                $update = $sql->update('workflow_role')
                    ->set($newData)
                    ->where(
                        array(
                        'workflow_id' => $workflowId, 'role_id' => $roleId
                        )
                    );
                $statement = $sql->prepareStatementForSqlObject($update);
            } else {
                $newData = array(
                    'can_read' => $canRead,
                    'workflow_id' => $workflowId,
                    'role_id' => $roleId
                );
                $insert = $sql->insert('workflow_role');
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
            }
            $results = $statement->execute();
            $i++;
        }

        $responseCode = 1;
        $errMessage = "Success";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function getformsreport($trackerId)
    {
        $res = array();
        $connection = $this->_adapter->getDriver()->getConnection();
        $qry = 'call sp_getformsreport("' . $trackerId . '")';
        $result = $connection->execute($qry);
        $statement = $result->getResource();
        $resultSet1 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[0] = $resultSet1;

        $statement->nextRowSet(); // Advance to the second result set
        $resultSet2 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[1] = $resultSet2;

        $statement->nextRowSet(); // Advance to the second result set
        $resultSet3 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[2] = $resultSet3;
        return $res;
    }
    public function savedefaultreportsetting($dataArray)
    {
        //print_r($data_array);die;
        $formId = $dataArray['form_id'];
        $roleId = $dataArray['role_id'];
        //$can_read = $data_array['can_read'];
        $canAccess = isset($dataArray['can_access'][0]) && !empty($dataArray['can_access'][0]) ? $dataArray['can_access'][0] : '';

        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('default_report_setting');
        $select->where(array('role_id ' => $roleId, 'form_id' => $formId));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $results = $selectString->execute();
        $arr = array();
        $resArr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $newData = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            $newData = array(
                'report_id' => $canAccess,
            );
            $update = $sql->update('default_report_setting')
                ->set($newData)
                ->where(
                    array(
                    'form_id' => $formId, 'role_id' => $roleId
                    )
                );
            $statement = $sql->prepareStatementForSqlObject($update);
        } else {
            $newData = array(
                'report_id' => $canAccess,
                'form_id' => $formId,
                'role_id' => $roleId
            );
            $insert = $sql->insert('default_report_setting');
            $insert->values($newData);
            $statement = $sql->prepareStatementForSqlObject($insert);
        }
        $results = $statement->execute();
        $responseCode = 1;
        $errMessage = "Success";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function getdefaultreportsetting($dataArray)
    {
        $reportId = $dataArray['can_access'];
        $roleId = $dataArray['role_id'];
        $formId = $dataArray['form_id'];
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('default_report_setting');
        //$select->where->in('report_id',$reportId);
        $select->where(array('role_id' => $roleId,'form_id'=>$formId));
        $reportIds = implode(',', $reportId);
        //$reportIds = "'" . implode ( "', '", $reportId ) . "'";
        //$select->order(array(new Expression('FIELD (`report_id`, '. $reportIds .')')));
        //$select->limit(1);
        $newadpater = $this->_adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $array = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $array = $resultSet->toArray();
            return $array;
        } else {
            return $array;
        }
    }
    public function saveformsetting($dataArray)
    {
        $formIds = $dataArray['form_id'];
        $roleId = $dataArray['role_id'];
        $canReads = $dataArray['can_read'];
        $i = 0;
        foreach ($formIds as $formId) {
            $canRead = $canReads[$i];
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('form_access_setting');
            $select->where(array('form_id ' => $formId, 'role_id ' => $roleId));
            $newadpater = $this->_adapter;
            $selectString = $sql->prepareStatementForSqlObject($select);
            //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
            $results = $selectString->execute();
            $arr = array();
            $resArr = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            $newData = array();
            if ($count > 0) {
                $arr = $resultSet->toArray();
                $newData = array(
                    'can_read' => $canRead,
                );
                $update = $sql->update('form_access_setting')
                    ->set($newData)
                    ->where(
                        array(
                        'form_id' => $formId, 'role_id' => $roleId
                        )
                    );
                $selectString = $sql->prepareStatementForSqlObject($update);
                $results = $selectString->execute();
            } else {
                $newData = array(
                    'can_read' => $canRead,
                    'form_id' => $formId,
                    'role_id' => $roleId
                );
                $insert = $sql->insert('form_access_setting');
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
                $results = $statement->execute();
            }
            //$results = $statement->execute();
            $i++;
        }

        $responseCode = 1;
        $errMessage = "Success";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function getformaccessdetail($trackerId)
    {
        $res = array();
        $connection = $this->_adapter->getDriver()->getConnection();
        $qry = 'call sp_getformdefaultdetail("' . $trackerId . '")';
        $result = $connection->execute($qry);
        $statement = $result->getResource();
        $resultSet1 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[0] = $resultSet1;
        $statement->nextRowSet(); // Advance to the second result set
        $resultSet2 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[1] = $resultSet2;
        $statement->nextRowSet(); // Advance to the second result set
        $resultSet3 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[2] = $resultSet3;
        return $res;
    }
    public function getReportAccessSetting($trackerId)
    {
        $res = array();
        $connection = $this->_adapter->getDriver()->getConnection();
        $qry = 'call sp_getReportAccessSetting("' . $trackerId . '")';
        $result = $connection->execute($qry);
        $statement = $result->getResource();
        $resultSet1 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[0] = $resultSet1;
        $statement->nextRowSet(); // Advance to the next result set
        $resultSet2 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[1] = $resultSet2;
        $statement->nextRowSet(); // Advance to the second result set
        $resultSet3 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[2] = $resultSet3;
        $statement->nextRowSet(); // Advance to the third result set
        $resultSet4 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[3] = $resultSet4;

        return $res;
    }
    public function savereportaccesssetting($dataArray)
    {
        $reportIds = $dataArray['report_id'];
        $formId = $dataArray['form_id'];
        $roleId = $dataArray['role_id'];
        $canUpdates = $dataArray['can_update'];
        $i = 0;
        foreach ($reportIds as $reportId) {
            $canUpdate = $canUpdates[$i];
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('report_access_setting');
            $select->where(array('report_id ' => $reportId, 'role_id ' => $roleId, 'form_id' => $formId));
            $newadpater = $this->_adapter;
            $selectString = $sql->prepareStatementForSqlObject($select);
            //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
            $results = $selectString->execute();
            $arr = array();
            $resArr = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            $newData = array();
            if ($count > 0) {
                $arr = $resultSet->toArray();
                $newData = array(
                    'can_access' => $canUpdate,
                );
                $update = $sql->update('report_access_setting')
                    ->set($newData)
                    ->where(
                        array(
                        'report_id' => $reportId, 'role_id' => $roleId, 'form_id' => $formId
                        )
                    );
                $statement = $sql->prepareStatementForSqlObject($update);
            } else {
                $newData = array(
                    'can_access' => $canUpdate,
                    'report_id' => $reportId,
                    'role_id' => $roleId,
                    'form_id' => $formId
                );
                $insert = $sql->insert('report_access_setting');
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
                //$results = $statement->execute();
            }
            $results = $statement->execute();
            $i++;
        }

        $responseCode = 1;
        $errMessage = "Success";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function getReportExportSetting($trackerId)
    {
        $res = array();
        $connection = $this->_adapter->getDriver()->getConnection();
        $qry = 'call sp_getReportExportSetting("' . $trackerId . '")';
        $result = $connection->execute($qry);
        $statement = $result->getResource();
        $resultSet1 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[0] = $resultSet1;
        $statement->nextRowSet(); // Advance to the second result set
        $resultSet2 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[1] = $resultSet2;
        $statement->nextRowSet(); // Advance to the second result set
        $resultSet3 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[2] = $resultSet3;
        return $res;
    }
    public function getexportreportsetting($dataArray)
    {
        $reportId = $dataArray['report_id'];
        $roleId = $dataArray['role_id'];
        $formId = $dataArray['form_id'];
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('report_export_setting');
        $select->where->in('report_id', $reportId);
        $select->where(array('role_id' => $roleId,'form_id'=>$formId));
        $reportIds = implode(',', $reportId);
        $reportIds = "'" . implode("', '", $reportId) . "'";
        $select->order(array(new Expression('FIELD (`report_id`, '. $reportIds .')')));
        //$select->limit(1);
        $newadpater = $this->_adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $array = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $array = $resultSet->toArray();
            return $array;
        } else {
            return $array;
        }
    }
    public function savereportexportsetting($dataArray)
    {
        $reportIds = $dataArray['report_id'];
        $formId = $dataArray['form_id'];
        //  print_r($workflow_id);die;
        $roleId = $dataArray['role_id'];
        $canUpdates = $dataArray['can_update'];
        $i = 0;
        foreach ($reportIds as $reportId) {
            $canUpdate = $canUpdates[$i];
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('report_export_setting');
            $select->where(array('report_id ' => $reportId, 'role_id ' => $roleId, 'form_id' => $formId));
            $newadpater = $this->_adapter;
            $selectString = $sql->prepareStatementForSqlObject($select);
            //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
            $results = $selectString->execute();
            $arr = array();
            $resArr = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            $newData = array();
            if ($count > 0) {
                $arr = $resultSet->toArray();
                $newData = array(
                    'can_access' => $canUpdate,
                );
                $update = $sql->update('report_export_setting')
                    ->set($newData)
                    ->where(
                        array(
                        'report_id' => $reportId, 'role_id' => $roleId, 'form_id' => $formId
                        )
                    );
                $statement = $sql->prepareStatementForSqlObject($update);
            } else {
                $newData = array(
                    'can_access' => $canUpdate,
                    'report_id' => $reportId,
                    'role_id' => $roleId,
                    'form_id' => $formId
                );
                $insert = $sql->insert('report_export_setting');
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
            }
            $results = $statement->execute();
            $i++;
        }

        $responseCode = 1;
        $errMessage = "Success";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    public function getvaluefromotherform($form, $originfields, $id, $value)
    {
        $queryTracker = "SELECT $originfields FROM $form where $id=?";
        $statements = $this->_adapter->createStatement($queryTracker, array($value));
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
        return $arr;
    }
    public function copytrcker($withdata, $trackerName, $withuserrole, $prevtrackerId, $userid)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('tracker');
        $select->where(array('tracker_id' => $prevtrackerId));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $results = $selectString->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }

        /*
         * check for duplicate tracker name
         */
        $qry = 'call sp_checkifexists("' . trim($trackerName) . '",0,"' . $arr[0]['client_id'] . '","tracker")';
        $statements = $this->_adapter->query($qry);
        $results = $statements->execute();
        $arrres = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arrres = $resultSet->toArray();
        }
        $statements->getResource()->closeCursor();
        if ($arrres[0]['checkduplicate']>0) {
            return -1;
        }
        /***************/

        $sql = new Sql($this->_adapter);
        $newData = array(
            'client_id' => $arr[0]['client_id'],
            'name' => trim($trackerName),
            'created_by' => $userid,
        );
        $insert = $sql->insert('tracker');
        $insert->values($newData);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $results = $statement->execute();
        $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
        if (!empty($lastInsertID)) {
            $connection = $this->_adapter->getDriver()->getConnection();
            $qry = 'call sp_exporttrackerallinfo("' . $lastInsertID . '","' . $userid . '","' . $withdata . '","' . $withuserrole . '","' . $prevtrackerId . '")';
            $result = $connection->execute($qry);
            return $lastInsertID;
        } else {
            return 0;
        }
    }
    public function getfieldsInfo($field_id_arr)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field');
        $select->columns(
            array('expected' => 'code_list_id',
            'names' => 'label',
            'field_id_arr' => 'field_id',
            'field_sort_order' => 'sort_order',
            'kpivalues' => 'kpi',
            'types' => 'field_type',
            )
        );
        $select->where->in('field_id', $field_id_arr);
        $ids_string = implode(',', $field_id_arr); // assuming integers here...
        $select->order(array(new Expression('FIELD (field_id, ' . $ids_string . ')')));
        //$newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        //results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
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
        //  print_r($arr);die;
    }
    public function getworkflowinfo($workflowid)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('workflow');
        $select->columns(
            array('workflow_name' => 'workflow_name',
            'status' => 'status',
            'sort_order' => 'sort_order',
            )
        );
        $select->where(array('workflow_id' => $workflowid));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $results = $selectString->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr[0];
    }
    public function getFormulaforfield($field_id)
    {
        $query = "Select field_id,formula from field where field_id=?";
        $statements = $this->_adapter->createStatement($query, array($field_id));
        $statements->prepare();
        $arr = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr[0];
    }
    public function formRecordsByID($trackerId, $actionId, $subactionId)
    {
        $formName = 'form_' . $trackerId . '_' . $actionId;
        $queryClients = "SELECT * FROM $formName";
        $queryClients .= " Where id = $subactionId";
        $formName = htmlspecialchars($formName);
        $subactionId = htmlspecialchars($subactionId);
        $statements = $this->_adapter->createStatement($queryClients);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $resultsArr = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr[0];
    }
    public function checkWorkflowExist($formId, $value, $workflowid, $action)
    {
        $sql = new Sql($this->_adapter);

        $select = $sql->select();
        if ($action == 'add') {
            $select->from('workflow');
            $select->where(array('workflow_name ' => "$value", 'form_id' => $formId));
        } else {
            $select->from('workflow');
            $select->where(array('workflow_name ' => "$value", 'form_id' => $formId));
            $select->where->notEqualTo('workflow_id', $workflowid);
        }
        $newadpater = $this->_adapter;
        // print_r($newadpater);exit;
        $selectString = $sql->prepareStatementForSqlObject($select);
        //$results = $newadpater->query($selectString);
        $results = $selectString->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        return $count;
    }
    public function checkFormExist($trackerId, $value)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('form');
        $select->where(array('form_name ' => "$value", 'tracker_id' => $trackerId));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $results = $selectString->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        return $count;
    }
    public function getfieldsAllInfo($field_id_arr)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field');
        $select->columns(
            array(
            'names' => 'label',
            'field_id_arr' => 'field_id',
            'field_sort_order' => 'sort_order',
            'kpivalues' => 'kpi',
            'types' => 'field_type',
            )
        );
        $select->where->in('field_id', $field_id_arr);
        $ids_string = implode(',', $field_id_arr); // assuming integers here...
        $select->order(array(new Expression('FIELD (field_id, ' . $ids_string . ')')));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
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
    public function getaccesssetting($dataArray)
    {
        $formId = $dataArray['form_id'];
        $roleId = $dataArray['role_id'];
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('workflow_role');
        $select->columns(array(`workflow_role` . 'can_insert', `workflow_role` . 'can_delete', `workflow_role` . 'role_id'));
        $select->join('workflow', 'workflow.workflow_id = workflow_role.workflow_id', array(`workflow` . 'form_id'), 'left');
        $select->where(array('form_id' => $formId, 'role_id ' => $roleId));
        $select->limit(1);
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $results = $selectString->execute();
        $array = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $array = $resultSet->toArray();
            return $array[0];
        } else {
            return $array;
        }
    }
    public function getreadaccesssetting($dataArray)
    {
        $formId = $dataArray['form_id'];
        $roleId = $dataArray['role_id'];
        $workflowid = $dataArray['workflow_id'];
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('workflow_role');
        $select->columns(array(`workflow_role` . 'can_read', `workflow_role` . 'role_id'));
        $select->join('workflow', 'workflow.workflow_id = workflow_role.workflow_id', array(`workflow` . 'form_id'), 'left');
        $select->where(array('form_id' => $formId, 'role_id ' => $roleId));
        $workflowid = implode(',', $workflowid);
        $select->order(array(new Expression('FIELD (`workflow_role`.workflow_id, ' . $workflowid . ')')));
        //$select->limit(1);
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $results = $selectString->execute();
        $array = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $array = $resultSet->toArray();
            return $array;
        } else {
            return $array;
        }
    }

    public function getcodelistinfo($codelist_id)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('code_list');
        $select->columns(array('code_list_name'));
        $select->where(array('code_list_id' => $codelist_id));
        $newadpater = $this->_adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
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
    public function getoptioninfo($codelist_id)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('code_list_option');
        $select->columns(array('kpi'=> 'kpi','names' => 'value', 'option_ids' => 'option_id' ));
        $select->where(array('code_list_id' => $codelist_id));
        $newadpater = $this->_adapter;

        $selectString = $sql->getSqlStringForSqlObject($select);
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
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

    public function getupdateaccesssetting($dataArray)
    {
        $formId = $dataArray['form_id'];
        $roleId = $dataArray['role_id'];
        $workflowid = $dataArray['workflow_id'];
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('workflow_role');
        $select->columns(array(`workflow_role` . 'can_update', `workflow_role` . 'role_id'));
        $select->join('workflow', 'workflow.workflow_id = workflow_role.workflow_id', array(`workflow` . 'form_id'), 'left');
        $select->where(array('form_id' => $formId, 'role_id ' => $roleId));
        $workflowid = implode(',', $workflowid);
        $select->order(array(new Expression('FIELD (`workflow_role`.workflow_id, ' . $workflowid . ')')));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $results = $selectString->execute();
        $array = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $array = $resultSet->toArray();
            return $array;
        } else {
            return $array;
        }
    }
    public function getformaccesssetting($dataArray)
    {
        $formId = $dataArray['form_id'];
        $roleId = $dataArray['role_id'];
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('form_access_setting');
        $select->where->in('form_id', $formId);
        $select->where(array('role_id' => $roleId));
        $formIds = implode(',', $formId);
        $select->order(array(new Expression('FIELD (`form_id`, ' . $formIds . ')')));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $results = $selectString->execute();
        $array = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $array = $resultSet->toArray();
            return $array;
        } else {
            return $array;
        }
    }
    public function getreportsetting($dataArray)
    {
        $reportId = $dataArray['report_id'];
        $roleId = $dataArray['role_id'];
        $formId = $dataArray['form_id'];
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('report_access_setting');
        $select->where->in('report_id', $reportId);
        $select->where(array('role_id' => $roleId, 'form_id' => $formId));
        $reportIds = implode(',', $reportId);
        $reportIds = "'" . implode("', '", $reportId) . "'";
        $select->order(array(new Expression('FIELD (`report_id`, ' . $reportIds . ')')));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        //$results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $results = $selectString->execute();
        $array = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $array = $resultSet->toArray();
            return $array;
        } else {
            return $array;
        }
    }
    public function workflowStatus($trackerId, $actionId, $recordId, $current_user, $role_id)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('workflow');
        $select->columns(array(`workflow` . 'workflow_name'));
        $select->join('rule_action', 'workflow.workflow_id = rule_action.value');
        $select->join('rule', 'rule_action.rule_id = rule.rule_id');
        $select->where(array('rule_action.action'=>'Edit Workflow','rule.form_id' => $actionId, 'rule.archive' => '0','rule.status'=>'Active'));
        $select->order('workflow.workflow_id');
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $workflow_array = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $workflow_array = $resultSet->toArray();
            $qry = 'call sp_statusdrivenworkflow(' . $trackerId . ', ' . $actionId . ',' . $recordId . ',"' . $current_user . '","' .IP. '","' .$role_id. '")';
            $this->_adapter->query($qry, Adapter::QUERY_MODE_EXECUTE);
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('temp_workflow_status');
            $select->join('workflow', 'workflow.workflow_id = temp_workflow_status.workflow_id');
            $select->join('workflow_role', 'workflow.workflow_id = workflow_role.workflow_id');
            $select->where(array('workflow.form_id' => $actionId,'workflow_role.role_id'=>$role_id));
            $newadpater = $this->_adapter;
            $selectString = $sql->prepareStatementForSqlObject($select);
            $results = $selectString->execute();
            $array = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();

            /*
             * deleting from temp table for particular role and IP
             */
            $sql = new Sql($this->_adapter);
            $delete = $sql->delete('temp_workflow_status')
                ->where(
                    array(
                    'ip' => IP,
                    'role_id'  => $role_id
                    )
                );
            $selectString = $sql->prepareStatementForSqlObject($delete);
            $results = $selectString->execute();
            $result=array();
            $result[0]=$workflow_array;
            if ($count > 0) {
                $array = $resultSet->toArray();
                foreach ($array as $workflowinfo) {
                    $array_wf[]=$workflowinfo['workflow_name'];
                }
                $result[1]=$array_wf;
                return $result;
            } else {
                return $result;
            }
        } else {
            return 0;
        }
    }
    public function getworkflowrule($trackerId)
    {
        $connection = $this->_adapter->getDriver()->getConnection();
        $qry = 'call sp_workflowrule("' . $trackerId . '")';
        $result = $connection->execute($qry);
        $statement = $result->getResource();
        $resultSet = $statement->fetchAll(\PDO::FETCH_OBJ);
        return $resultSet;
    }
    public function saveRule($data)
    {
        try {
            $newadpater = $this->_adapter;
            $sql = new Sql($this->_adapter);
            $newData = array();
            $depth = 0;
            $rule_id = $data['rule_id'];
            if ($rule_id > 0) {
                $sql = new Sql($this->_adapter);
                $delete = $sql->delete('rule_action')
                    ->where(
                        array(
                        'rule_id' => $rule_id
                        )
                    );
                $selectString = $sql->prepareStatementForSqlObject($delete);
                //$results = $this->_adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
                $results = $selectString->execute();
                $query = "Delete From rule_condition_loop Where condition_id in (select condition_id from rule_condition "
                    . "where rule_id=?)";
                $statements = $this->_adapter->createStatement($query, array($rule_id));
                $statements->prepare();
                $results = $statements->execute();

                $delete = $sql->delete('rule_condition')
                    ->where(
                        array(
                        'rule_id' => $rule_id
                        )
                    );
                $selectString = $sql->prepareStatementForSqlObject($delete);
                //$results = $this->_adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
                $results = $selectString->execute();

                /* update  rule table */

                $updatedata = array(
                    'form_id' => $data['form_id']
                );
                $update = $sql->update();
                $update->table('rule');
                $update->set($updatedata);
                $update->where(array('rule_id' => $rule_id));
                $statement = $sql->prepareStatementForSqlObject($update);
                $statement->execute($update);

                //print_r($data);die;
                if (count($data['condition_on_field']) > 1) {
                    $depth = 1;
                }

                /* insertion into rule_condition table */
                $newData = array();
                $newData = array(
                    'rule_id' => $rule_id,
                    'operator' => $data['r_cond'],
                    'depth' => $depth
                );
                $insert = $sql->insert('rule_condition');
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
                $results = $statement->execute();
                $lastInserted_contionID = $this->_adapter->getDriver()->getLastGeneratedValue();

                /* insertion into rule_condition_loop table */
                foreach ($data['condition_on_field'] as $key => $val) {
                    $master_condition = 0;
                    if ($key == 0) {
                        $master_condition = 1;
                    }
                    $newData = array();
                    $newData = array(
                        'condition_id' => $lastInserted_contionID,
                        'master_condition' => $master_condition,
                        'field_id' => $val,
                        'comparision_op' => $data['condition_operand'][$key],
                        'value' => $data['value'][$key],
                    );
                    $insert = $sql->insert('rule_condition_loop');
                    $insert->values($newData);
                    $statement = $sql->prepareStatementForSqlObject($insert);
                    $results = $statement->execute();
                    $lastInsert_condloopID = $this->_adapter->getDriver()->getLastGeneratedValue();
                }
                /* insertion into rule_action table */

                foreach ($data['action_value'] as $key => $val) {
                    $newData = array();
                    $newData = array(
                        'rule_id' => $rule_id,
                        'action' => $data['action_name'][$key],
                        'value' => $val
                    );
                    $insert = $sql->insert('rule_action');
                    $insert->values($newData);
                    $statement = $sql->prepareStatementForSqlObject($insert);
                    $results = $statement->execute();
                }
            } else {
                /* insertion into rule table */
                $newData = array(
                    'form_id' => $data['form_id']
                );
                $insert = $sql->insert('rule');
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
                $results = $statement->execute();
                $lastInserted_ruleID = $this->_adapter->getDriver()->getLastGeneratedValue();

                if (count($data['condition_on_field']) > 1) {
                    $depth = 1;
                }

                /* insertion into rule_condition table */
                $newData = array();
                $newData = array(
                    'rule_id' => $lastInserted_ruleID,
                    'operator' => $data['r_cond'],
                    'depth' => $depth
                );
                $insert = $sql->insert('rule_condition');
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
                $results = $statement->execute();
                $lastInserted_contionID = $this->_adapter->getDriver()->getLastGeneratedValue();

                /* insertion into rule_condition_loop table */
                foreach ($data['condition_on_field'] as $key => $val) {
                    $master_condition = 0;
                    if ($key == 0) {
                        $master_condition = 1;
                    }
                    $newData = array();
                    $newData = array(
                        'condition_id' => $lastInserted_contionID,
                        'master_condition' => $master_condition,
                        'field_id' => $val,
                        'comparision_op' => $data['condition_operand'][$key],
                        'value' => $data['value'][$key],
                    );
                    $insert = $sql->insert('rule_condition_loop');
                    $insert->values($newData);
                    $statement = $sql->prepareStatementForSqlObject($insert);
                    $results = $statement->execute();
                    $lastInsert_condloopID = $this->_adapter->getDriver()->getLastGeneratedValue();
                }

                /* insertion into rule_action table */

                foreach ($data['action_value'] as $key => $val) {
                    $newData = array();
                    $newData = array(
                        'rule_id' => $lastInserted_ruleID,
                        'action' => $data['action_name'][$key],
                        'value' => $val
                    );
                    $insert = $sql->insert('rule_action');
                    $insert->values($newData);
                    $statement = $sql->prepareStatementForSqlObject($insert);
                    $results = $statement->execute();
                }
            }
            return true;
        } catch (\Exception $e) {
            return "fail";
        }
    }
    public function getfields($actionId)
    {
        $queryFormFields = "SELECT field.*, workflow.workflow_name
        FROM field
        LEFT JOIN workflow ON field.workflow_id = workflow.workflow_id
        WHERE workflow.form_id = ? ORDER BY workflow.sort_order ASC, field.sort_order ASC";
        $statements = $this->_adapter->createStatement($queryFormFields, array($actionId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $fieldsArr = array();
        if ($count > 0) {
            $fieldsArr = $resultSet->toArray();
        }
        $inserted[0]['field_id'] = '';
        $inserted[0]['field_name'] = 'Select Field';
        array_splice($fieldsArr, 0, 0, $inserted);
        return $fieldsArr;
    }
    public function getruleinfo($data)
    {
        $connection = $this->_adapter->getDriver()->getConnection();
        $qry = 'call sp_getruleinfo("' . (int)$data['rule_id'] . '")';
        $result = $connection->execute($qry);
        $statement = $result->getResource();
        $resultSet1 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[0] = $resultSet1;
        $statement->nextRowSet(); // Advance to the second result set
        $resultSet2 = $statement->fetchAll(\PDO::FETCH_OBJ);
        $res[1] = $resultSet2;
        return $res;
    }
    public function deleteRule($ruleId)
    {
        $sql = new Sql($this->_adapter);
        try {
            $newData = array(
                'archive' => 1,
            );
            $update = $sql->update('rule')
                ->set($newData)
                ->where(array('rule_id' => $ruleId));
            $statement = $sql->prepareStatementForSqlObject($update);
            $results = $statement->execute();
            return true;
        } catch (\Exception $e) {
            return "fail";
        }
    }
    public function getWorkFlowsForWorkflowRule($trackerId, $actionId)
    {
        $queryClients = "SELECT * FROM workflow where form_id=?";
        $actionId = htmlspecialchars($actionId, ENT_QUOTES);
        $statements = $this->_adapter->createStatement($queryClients, array($actionId));
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $resultsArr = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $inserted[0]['workflow_id'] = '';
        $inserted[0]['workflow_name'] = 'Select Workflow';
        array_splice($arr, 0, 0, $inserted);
        return $arr;
    }
    public function geteditwfruleinfo($ruleID)
    {
        $queryClients = "SELECT r.rule_id,r.form_id, rc.operator, rcl.comparision_op, rcl.field_id, rcl.value as val FROM rule as r
            INNER JOIN rule_condition as rc on r.rule_id=rc.rule_id
            INNER JOIN rule_condition_loop as rcl ON rc.condition_id=rcl.condition_id where r.rule_id=?";
        $statements = $this->_adapter->createStatement($queryClients, array($ruleID));
        $statements->prepare();
        $results = $statements->execute();

        $arr[0] = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr[0] = $resultSet->toArray();
        }
        $queryClients = "SELECT r.rule_id,r.form_id, rc.operator, ra.action, ra.value FROM rule as r
            INNER JOIN rule_condition as rc on r.rule_id=rc.rule_id INNER JOIN rule_action as ra ON r.rule_id=ra.rule_id
            where r.rule_id=?";
        $statements = $this->_adapter->createStatement($queryClients, array($ruleID));
        $statements->prepare();
        $results = $statements->execute();

        $arr[1] = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        if ($count > 0) {
            $arr[1] = $resultSet->toArray();
        }
        return $arr;
    }

    public function getValidationRule($fieldtype)
    {
        try {
            $queryClients = "SELECT * FROM field_validations_rules where datatype=?";
            $statements = $this->_adapter->createStatement($queryClients, array($fieldtype));
            $statements->prepare();
            $results = $statements->execute();
            $arr = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = @$resultSet->count();
            $resultsArr = array();
            if ($count > 0) {
                $arr = $resultSet->toArray();
            }
            return $arr;
        } catch (\Exception $e) {
            return 'dberror';
        }
    }



    public function getValidationRules($formId, $role_id)
    {
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
        
        //$select->join('workflow_role', 'workflow.workflow_id = workflow_role.workflow_id', array());
        $select->where(array('workflow.form_id' => $formId));
        $select->order(array('field.field_id'));
        
        $newadpater = $this->_adapter;
        $selectString = $sql->getSqlStringForSqlObject($select);
        //echo"<pre>";print_r($selectString);die;
        $results = $newadpater->query($selectString, $newadpater::QUERY_MODE_EXECUTE);
        $array = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $array = $resultSet->toArray();
            return $array;
        } else {
            return $array;
        }
    }


    public function getTrackerDateSetting($finalData, $trackerId)
    {
        $trackerId = htmlspecialchars($trackerId, ENT_QUOTES);

        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('tracker_settings');
        $select->where(array('tracker_id' => $trackerId));
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $count;
        if ($count==0) {
            $insert = $sql->insert('tracker_settings');
            $newData = array(
                        'tracker_id' => $trackerId,
                        'date_format' => $finalData[0],
                        'php_date_format' => $finalData[1],
                        'jquery_date_format' =>$finalData[2],
                        'pregmatch_date'=>$finalData[3],
                        'date_time_format' => $finalData[4],
                        'php_date_time_format' => $finalData[5],
                        'jquery_date_time_format' => $finalData[6],
                        'pregmatch_date_time' => $finalData[7]
                        );
            $insert->values($newData);
            $selectString = $sql->getSqlStringForSqlObject($insert);
            $results = $this->_adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
        } else {
            $sql = new Sql($this->_adapter);
            $newData1 = array(
                        'tracker_id' => $trackerId,
                        'date_format' => $finalData[0],
                        'php_date_format' => $finalData[1],
                        'jquery_date_format' =>$finalData[2],
                        'pregmatch_date'=>$finalData[3],
                        'date_time_format' => $finalData[4],
                        'php_date_time_format' => $finalData[5],
                        'jquery_date_time_format' => $finalData[6],
                        'pregmatch_date_time' => $finalData[7]
                        );

            $update = $sql->update('tracker_settings')
                ->set($newData1)
                ->where(array('tracker_id' => $trackerId));
            $selectString = $sql->getSqlStringForSqlObject($update);
            $results = $this->_adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
            $responseCode = 1;
        }
    }

    public function getDateFormat($trackerId)
    {
        $trackerId = htmlspecialchars($trackerId, ENT_QUOTES);
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('date_time_format');
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $resArr = array();
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

    public function getSelectedDate($trackerId)
    {
        $sql = new Sql($this->_adapter);

        $select = $sql->select();

        $select->from('tracker_settings');
        $select->where(array('tracker_id' => $trackerId));

        $statement = $sql->prepareStatementForSqlObject($select);
        //echo"<pre>"; print_r($statement);die;
        $results = $statement->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = @$resultSet->count();
        $resFieldsArr = array();
        $resArr = array();
        if ($count > 0) {
            $resArr = $resultSet->toArray();
        }
        return $resArr;
    }
    public function getDateTimeFormats($trackerId)
    {
        $trackerId = htmlspecialchars($trackerId, ENT_QUOTES);
        $trackerId=filter_var($trackerId, FILTER_SANITIZE_STRING);
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('tracker_settings');
        $select->where(array('tracker_id' => $trackerId));
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            $arr = $arr[0];
        } else {
            $arr = array(
                'tracker_id' => $trackerId,
                'date_format' => "yyyy-mm-dd",
                'date_time_format' => "yyyy-mm-dd hh:ii",
                'php_date_format' => "Y-m-d",
                'php_date_time_format' => "Y-m-d H:i",
                'jquery_date_format' => "YYYY-MM-DD",
                'jquery_date_time_format' => "YYYY-MM-DD HH:mm",
                //'pregmatch_date_time' => "/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/",
                'pregmatch_date_time' => "/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9])$/",
                'pregmatch_date' => "/^(\d{4})-(\d{2})-(\d{2})$/"
            );
        }
        return $arr;
    }
    public function removefile($data)
    {
        $sql = new Sql($this->_adapter);
        try {
            $newData = array(
                $data['file'] => '',
            );
            $update = $sql->update('form_'.$data['tracker_id'].'_'.$data['form_id']);
            $update->set($newData);
            $update->where(array('id' => $data['id']));
            $selectString = $sql->prepareStatementForSqlObject($update);
            $result=$selectString->execute();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    public function getTrackerAccess($u_id, $trackerId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('user')
            ->join('user_role_tracker', 'user.u_id = user_role_tracker.u_id')
            ->join('group', 'user_role_tracker.group_id = group.group_id');
        $select->where(array('user.u_id' => $u_id,'group.tracker_id' => $trackerId));
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
    
    
    /*for search setting:to add fields for searching*/
    
    public function updatefields($fields,$form)
    {
        try {
            $query = "UPDATE field f
            SET 
                f.search_field = 0
            WHERE
                f.field_id IN (SELECT 
            f2.field_id from (select * from field) AS f2
                LEFT JOIN
            workflow ON workflow.workflow_id = f2.workflow_id
                LEFT JOIN
            form ON form.form_id=workflow.form_id where form.form_id=?)";
            $statements = $this->_adapter->createStatement($query, array($form));
            $statements->prepare();
            $results = $statements->execute();
            $statements->getResource()->closeCursor();

            $sql = new Sql($this->_adapter);
            $newData = array(
                'search_field' => 1,
            );
            $select = $sql->update('field')->set($newData);
            $select->where->in('field_id', $fields);
            $selectString = $sql->prepareStatementForSqlObject($select);
            $selectString->execute();
            return true;
        } catch (\Exception $e) {
            return fail;
        }
    }

    public function fetchAllData($tableName)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from($tableName);
        //$select->columns(array('source_field_name','db_field_name','isRequiredForDuplicateSearch'));
        $select->where(array('is_deleted' => 'No'));
        $select->order('id DESC');
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
    public function fetchAllColumnName($formId)
    {
        $query="SELECT field_name,label from field where workflow_id in (select workflow_id from workflow where form_id=".$formId.") and show_in_dashboard ='yes'";
        //$query="SHOW COLUMNS FROM ".$tableName;
        $statements = $this->_adapter->createStatement($query);
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
    public function allFieldsOfForm($trackerId,$formId)
    {
        $queryFields = "SELECT f.field_id,f.field_name, f.label as fieldlabel,f.field_type,f.formula,f.code_list_id, f.validation_required, f.formula_dependent,f.workflow_id, clo.value as optionValue, clo.label as optionLabel,clo.kpi,clo.archived,w.workflow_name
        FROM field as f 
        LEFT JOIN code_list_option as clo ON f.code_list_id = clo.code_list_id
        LEFT JOIN workflow as w ON w.workflow_id = f.workflow_id
        
        WHERE w.form_id = ? and (clo.archived IS NULL OR clo.archived = 0) ORDER BY f.sort_order ASC";
        $statements = $this->_adapter->createStatement($queryFields, array($formId));
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

    public function checkHolidayList($trackerId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from(array('c'=>'calendar_event'));
        $select->columns(array());
        $select->join(array('u'=>'user_event'), 'c.id = u.event_id', array('start_date','end_date'), $select::JOIN_INNER);
        $select->where(array('c.customer_id' => $trackerId,'c.event_type' =>'Global','u.is_archived'=>'0'));
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
