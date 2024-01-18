<?php

namespace ASRTRACE\Tracker\Model;

use Zend\Db\adapter\adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Session\Container;
use Session\Container\SessionContainer;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Db\Sql\Expression;

class Workflow extends AbstractActionController
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
    
    public function trackerCheckWorkFlows($formId)
    {
        $queryClients = "SELECT * FROM workflow Where form_id = ? ORDER BY sort_order";
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
        $newdata =  array(
            'group_id' => 'CurrentUser',
            'group_name' => 'CurrentUser',
            'tracker_id' => $tracker_id
        );
        $arr[count($arr)]=$newdata;
        return $arr;
    }
    public function takeBackup($table) 
    {
        set_time_limit(0);
        $config = $this->getServiceLocator()->get('Config');
        $dsn = $config['db']['dsn'];
        $dbuser = $config['db']['username'];
        $dbpass = $config['db']['password'];
        $dbhost = explode("=", explode(";", $dsn)[1])[1] ;
        $dbname = explode("=", explode(";", $dsn)[0])[1] ;
        ob_start();
        $backup_file = dirname(dirname(dirname(dirname(dirname(__DIR__)))))."/public/backup/deletedWorkflowBackup/".$table."_".date("Y-m-d_H-i-s").'_history.sql';
        if (!file_exists(dirname(dirname(dirname(dirname(dirname(__DIR__)))))."/public/backup/deletedWorkflowBackup")) {
            mkdir(dirname(dirname(dirname(dirname(dirname(__DIR__)))))."/public/backup/deletedWorkflowBackup", 0777, true);
        }
        chmod(dirname(dirname(dirname(dirname(dirname(__DIR__)))))."/public/backup/deletedWorkflowBackup", 0777);
        $handle = fopen($backup_file, 'w+');
        fclose($handle);
        chmod($backup_file, 0777);
        $command = "mysqldump --default-character-set=utf8 --comments --host=".escapeshellarg($dbhost)." --user=".escapeshellarg($dbuser)." ";
        if ($dbpass) {
            $command .= " --password=".escapeshellarg($dbpass)." ";
        }
        $command .= $dbname." ".$table." > ".escapeshellarg($backup_file);
        passthru($command, $ret);
        ob_end_clean();
        return true;
    }
    public function deleteWorkflow($dataArr)
    {
        $connection = null;
        $resultsArr = array();
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            
            $sql = new Sql($this->_adapter);
            $workflowId = htmlspecialchars(filter_var($dataArr['workflowID'], FILTER_SANITIZE_STRING), ENT_QUOTES);
            $trackerId = htmlspecialchars(filter_var($dataArr['trackerId'], FILTER_SANITIZE_STRING), ENT_QUOTES);
            $formId = htmlspecialchars(filter_var($dataArr['formId'], FILTER_SANITIZE_STRING), ENT_QUOTES);
        
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
            $removedFields = array();
            $this->takeBackup('form_'.$trackerId.'_'.$formId);
            if ($count > 0) {
                $resFieldsArr = $resultSet->toArray();
                if (array_key_exists(0, $resFieldsArr)) {
                    $alterArr = array();
                    $alterQuery = "ALTER TABLE form_$trackerId" . "_$formId ";
                    foreach ($resFieldsArr as $key => $value) {
                        $fieldName = $removedFields[] = $value['field_name'];
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
            }
            $tables = array('field', 'workflow_role', 'workflow');
            foreach ($tables as $k => $table) {
                $query = "Delete From $table Where workflow_id=?";
                $statements = $this->_adapter->createStatement($query, array($workflowId));
                $statements->execute();
            }
            $this->workflowSort($formId);
  
            $connection->commit();
            $responseCode = 1;
            $errMessage = 'Workflow Deleted Successfully';
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While deleting Workflow'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While deleting Workflow'; 
        }
        $resultsArr['fields'] = $removedFields;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
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
            for ($j=0; $j < count($resFieldsArr); $j++) {
                $res = $resFieldsArr[$j];
                for ($i=1;$i <= count($res);$i++) {
                    if ($res['sort_order'] != ($j+1)) {
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
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        return $arr;
    }
    public function getsortlargest($actionId)
    {
        $query = "Select MAX(sort_order) as max_sort_num from workflow where form_id=?";
        $statements = $this->_adapter->createStatement($query, array($actionId));
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
    
    public function checkWorkflowExist($formId, $value, $workflowid, $action)
    {
        $sql = new Sql($this->_adapter);

        $select = $sql->select();
        if ($action == 'add') {
            $select->from('workflow');
            $select->where(array('workflow_name ' => $value, 'form_id' => $formId));
        } else {
            $select->from('workflow');
            $select->where(array('workflow_name ' => $value, 'form_id' => $formId));
            $select->where->notEqualTo('workflow_id', $workflowid);
        }

        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        return $count;
    }
    
    public function saveWorkflow($dataArr)
    {
        $connection = null;
        $session = new SessionContainer();
        $workflowSession = $session->getSession('wfs');
        $resultsArr = $Ids = array();
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();

            $wfNames = $dataArr['wfNames'];
            $formId = $dataArr['formId'];
            $subType = isset($dataArr['subType'])?$dataArr['subType']:'';
            $wfSortOrder = isset($dataArr['wfSortOrder'])?$dataArr['wfSortOrder']:array();
            $workflowId = isset($dataArr['workflowId'])?$dataArr['workflowId']:0;
            $wfsArr = array();
            if ($workflowId == 0) {
                foreach ($wfNames as $key => $value) {
                    $sql = new Sql($this->_adapter);
                    $select = $sql->select();
                    $select->from('master_workflow');
                    $select->where(array('workflow_name ' => $value));
                    $selectString = $sql->prepareStatementForSqlObject($select);
                    $results = $selectString->execute();
                    $arr = array();
                    $resultSet = new ResultSet;
                    $resultSet->initialize($results);
                    $resultSet->buffer();
                    $count = $resultSet->count();
                    $mWfId = 0;
                    if ($count > 0) {
                        $arr = $resultSet->toArray();
                        $mWfId = $arr[0]['master_workflow_id'];
                    }

                    $sql1 = new Sql($this->_adapter);
                    $select1 = $sql1->select();
                    $select1->from('workflow');
                    $select1->where(array('workflow_name ' => $value, 'form_id' => $formId));
                    $selectString1 = $sql1->prepareStatementForSqlObject($select1);
                    $results1 = $selectString1->execute();
                    $resultSet1 = new ResultSet;
                    $resultSet1->initialize($results1);
                    $resultSet1->buffer();
                    $count1 = $resultSet1->count();
                    if ($count1 == 0) {
                        $sql = new Sql($this->_adapter);
                        $sort_order = isset($wfSortOrder[$key])?$wfSortOrder[$key]:0;
                        $insert = $sql->insert('workflow');
                        $newData = array(
                            'workflow_name' => $value,
                            'form_id' => $formId,
                            'master_workflow_id' => $mWfId,
                            'sort_order' => $sort_order,
                        );
                        $insert->values($newData);
                        $statement = $sql->prepareStatementForSqlObject($insert);
                        $results = $statement->execute();
                        $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
                        $wf = array();
                        $wf['wf_id'] = $Ids[] = $lastInsertID;
                        $wf['wf_name'] = $value;
                        $wf['master_workflow_id'] = $mWfId;
                        $wfsArr[] = $wf;
                    }
                }
                $workflowId = implode(",", $Ids);
                if ($subType == 'next') {
                    $workflowSession->wfs = $wfsArr;
                }
                $connection->commit();
                $responseCode = 1;
                $errMessage = "Workflow created successfully"; 
            } else if ($workflowId > 0) {
                foreach ($wfNames as $key => $value) {
                    $sql = new Sql($this->_adapter);
                    $select = $sql->select();
                    $select->from('master_workflow');
                    $select->where(array('workflow_name ' => $value));
                    $selectString = $sql->prepareStatementForSqlObject($select);
                    $results = $selectString->execute();
                    $arr = array();
                    $resultSet = new ResultSet;
                    $resultSet->initialize($results);
                    $resultSet->buffer();
                    $count = $resultSet->count();
                    $mWfId = 0;
                    if ($count > 0) {
                        $arr = $resultSet->toArray();
                        $mWfId = $arr[0]['master_workflow_id'];
                    }
                    $sql1 = new Sql($this->_adapter);
                    $update = $sql1->update('workflow')
                        ->set(array('workflow_name' => $value, 'master_workflow_id' => $mWfId))
                        ->where(array('workflow_id' => $workflowId));
                    $selectString1 = $sql1->prepareStatementForSqlObject($update);
                    $selectString1->execute(); 
                }
                $connection->commit();
                $responseCode = 2;
                $errMessage = "Workflow updated successfully";
            }
            
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While creating Workflow'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While creating Workflow'; 
        }
        $resultsArr['ids'] = $workflowId;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    
    public function updateWorkflowSorting($dataArr)
    {
        $connection = null;
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            $wfNames = isset($dataArr['wfNames'])?$dataArr['wfNames']:array();
            $wfIdForSort = isset($dataArr['wf_id_for_sort'])?$dataArr['wf_id_for_sort']:array();
            $wfSortOrder = isset($dataArr['wf_sort_order'])?$dataArr['wf_sort_order']:array();
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
                    ->where(array('workflow_id' => $workflowId));
                $selectString = $sql->prepareStatementForSqlObject($update);
                $selectString->execute();
            }
            $connection->commit();
            $responseCode = 1;
            $errMessage = "SuccessFully updated";
            
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While sorting Workflow'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While sorting Workflow'; 
        }
        
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
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
    
    /*
    * get tracker information
    */

    public function getWorkflowInformation($workflowId) 
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('workflow');
        $select->where(array('workflow_id' => $workflowId));
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
    
    public function getFieldsByWorkflowId($data)
    {
        try {
            if (isset($data['workflowId'])) {
                $workflowId = htmlspecialchars(filter_var($data['workflowId'], FILTER_SANITIZE_STRING), ENT_QUOTES);
            } else {
                $workflowId = htmlspecialchars(filter_var($data['workflow_id'], FILTER_SANITIZE_STRING), ENT_QUOTES);
            }
            
            $query = "Select * from field where binary(workflow_id) = ? ORDER BY sort_order";
            $statements = $this->_adapter->createStatement($query, array($workflowId));
            $resArr = array();
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
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
    
    public function getMaxFieldSortNumber($dataArray)
    {
        $workflowId = htmlspecialchars(filter_var($dataArray['workflowId'], FILTER_SANITIZE_STRING), ENT_QUOTES);
        $query = "Select MAX(sort_order) as max_sort_num from field where workflow_id=?";
        $statements = $this->_adapter->createStatement($query, array($workflowId));
        $statements->prepare();
        $resArr = array();
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
        $responseCode = 1;
        $resArr['responseCode'] = $responseCode;
        $resArr['maxfieldSortNumber'] = $maxSortNumber;
        return $resArr;
    }
    
    public function getWorkflowRule($trackerId)
    {
        $connection = $this->_adapter->getDriver()->getConnection();
        $qry = 'call sp_getWorkflowRules("'.$trackerId.'")';
        $result = $connection->execute($qry);
        $statement = $result->getResource();
        $resultSet = $statement->fetchAll(\PDO::FETCH_OBJ);
        return $resultSet;
    }
    
    public function deleteRule($ruleId, $formId)
    { 
        $connection = null;
        $ruleData = $this->getOldRulesForAudit($ruleId);
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            $query = "UPDATE rule SET archive = ? WHERE rule_id = ?";
            $ruleDeleteQuery = $this->_adapter->createStatement($query, array("1", $ruleId));
            $ruleDeleteQuery->prepare();
            $result = $ruleDeleteQuery->execute()->getAffectedRows();
            if ($result > 0) {
                $responseCode = 1;
                $errMessage = "Rule deleted successfully";
            } else {
                $responseCode = 0;
                $errMessage = "Error While deleting Rule";
                
            }
            $connection->commit();
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While deleting Rule'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While deleting Rule'; 
        }
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        $resArr['affectedRows'] = $result;
        $resArr['actual'] = $ruleData;
        return $resArr;
    }
    
    public function getFields($formId)
    {
        $queryFormFields = "SELECT field.*, workflow.workflow_name
        FROM field
        LEFT JOIN workflow ON field.workflow_id = workflow.workflow_id
        WHERE workflow.form_id = ? ORDER BY workflow.sort_order ASC, field.sort_order ASC";
        $statements = $this->_adapter->createStatement($queryFormFields, array($formId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $fieldsArr = $inserted = array();
        if ($count > 0) {
            $fieldsArr = $resultSet->toArray();
        }
        return $fieldsArr;
    }
    public function saveRule($data)
    {
        $connection = null;
        $ruleId = isset($data['ruleId'])?intval($data['ruleId']):0;
        $formId = isset($data['formId'])?intval($data['formId']):0;
        $condition = isset($data['rCond'])?$data['rCond']:'';
        $action = 'adding'; $responseCode = 0; $errMessage = $oldRuleData = $actionFields = "";
        $when = $actions = array();
        if ($ruleId > 0) {
            $action = 'updating';
        }
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            $sql = new Sql($this->_adapter);
            $newData = array();
            $depth = 0;
            switch ($action) {
            case 'adding': 
                $newData = array(
                    'form_id' => $formId
                );
                $insert = $sql->insert('rule');
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
                $statement->execute();
                $ruleId = $lastInserted_ruleID = $this->_adapter->getDriver()->getLastGeneratedValue();

                if (count($data['condition_on_field']) > 1) {
                    $depth = 1;
                }
                $newData = array(
                    'rule_id' => $ruleId,
                    'operator' => $data['rCond'],
                    'depth' => $depth
                );
                $insert = $sql->insert('rule_condition');
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
                $statement->execute();
                $lastInserted_contionID = $this->_adapter->getDriver()->getLastGeneratedValue();

                /* insertion into rule_condition_loop table */
                foreach ($data['condition_on_field'] as $key => $val) {
                    $master_condition = 0;
                    if ($key == 0) {
                        $master_condition = 1;
                    }
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
                    $statement->execute();
                    $lastInsert_condloopID = $this->_adapter->getDriver()->getLastGeneratedValue();
                    $when[] = "{field:'$val', condition:'".$data['condition_operand'][$key]."', value:'".$data['value'][$key]."'}";
                }

                /* insertion into rule_action table */
                foreach ($data['action_value'] as $key => $val) {
                    $actionFields = "";
                    $newData = array(
                        'rule_id' => $ruleId,
                        'action' => $data['action_name'][$key],
                        'action_workflow_id' => $val
                        
                    );
                    if (isset($data['actionFields'][$key])) {
                        $newData['action_fields'] = implode(",", $data['actionFields'][$key]);
                        $actionFields = implode("','", $data['actionFields'][$key]);
                    }
                    $insert = $sql->insert('rule_action');
                    $insert->values($newData);
                    $statement = $sql->prepareStatementForSqlObject($insert);
                    $statement->execute();
                    $actions[] = "{action_name:'".$data['action_name'][$key]."', workflow_id:'".$val."', action_fields:{'".$actionFields."'}}";
                }
                $connection->commit();
                $responseCode = 1;
                $errMessage = "Rule added successfully";
                break;
            case 'updating': 
                $oldRuleData = $this->getOldRulesForAudit($ruleId);
                $sql = new Sql($this->_adapter);              
                $delete = $sql->delete('rule_action')
                    ->where(array('rule_id' => $ruleId));
                $ruleActionDeleteQuery = $sql->prepareStatementForSqlObject($delete);
                $ruleActionDeleteQuery->execute();
                
                $query = "Delete From rule_condition_loop Where condition_id in (select condition_id from rule_condition where rule_id=?)";
                $ruleConditionLoopDeleteQuery = $this->_adapter->createStatement($query, array($ruleId));
                $ruleConditionLoopDeleteQuery->prepare();
                $ruleConditionLoopDeleteQuery->execute();

                $delete = $sql->delete('rule_condition')
                    ->where(array('rule_id' => $ruleId));
                $ruleConditionDeleteQuery = $sql->prepareStatementForSqlObject($delete);
                $ruleConditionDeleteQuery->execute();

                /* update  rule table */

                $updatedata = array('form_id' => $formId);
                $update = $sql->update();
                $update->table('rule');
                $update->set($updatedata);
                $update->where(array('rule_id' => $ruleId));
                $updateRuleQuery = $sql->prepareStatementForSqlObject($update);
                $updateRuleQuery->execute();

                if (count($data['condition_on_field']) > 1) {
                    $depth = 1;
                }

                /* insertion into rule_condition table */
                $newData = array(
                    'rule_id' => $ruleId,
                    'operator' => $condition,
                    'depth' => $depth
                );
                $insert = $sql->insert('rule_condition');
                $insert->values($newData);
                $updateRuleConditionQuery = $sql->prepareStatementForSqlObject($insert);
                $updateRuleConditionQuery->execute();
                $lastInserted_contionID = $this->_adapter->getDriver()->getLastGeneratedValue();

                /* insertion into rule_condition_loop table */
                foreach ($data['condition_on_field'] as $key => $val) {
                    $master_condition = 0;
                    if ($key == 0) {
                        $master_condition = 1;
                    }
                    $newData = array(
                        'condition_id' => $lastInserted_contionID,
                        'master_condition' => $master_condition,
                        'field_id' => $val,
                        'comparision_op' => $data['condition_operand'][$key],
                        'value' => $data['value'][$key],
                    );
                    $insert = $sql->insert('rule_condition_loop');
                    $insert->values($newData);
                    $insertConditionLoopQuery = $sql->prepareStatementForSqlObject($insert);
                    $insertConditionLoopQuery->execute();
                    $when[] = "{field:'$val', condition:'".$data['condition_operand'][$key]."', value:'".$data['value'][$key]."'}";
                }
                /* insertion into rule_action table */

                foreach ($data['action_value'] as $key => $val) {
                    $newData = array(
                        'rule_id' => $ruleId,
                        'action' => $data['action_name'][$key],
                        'action_workflow_id' => $val
                    );
                    $newData['action_fields'] = null;
                    if (isset($data['actionFields'][$key]) && strtolower($data['action_name'][$key]) == "hide fields") {
                        $newData['action_fields'] = implode(",", $data['actionFields'][$key]);
                        $actionFields = implode("','", $data['actionFields'][$key]);
                    }
                    $actionTable = $sql->insert('rule_action');
                    $actionTable->values($newData);
                    $insertActionQuery = $sql->prepareStatementForSqlObject($actionTable);
                    $insertActionQuery->execute();
                    $actions[] = "{action_name:'".$data['action_name'][$key]."', workflow_id:'".$val."', action_fields:{'".$actionFields."'}}";
                }
                $connection->commit();
                $responseCode = 1;
                $errMessage = "Rule updated successfully";
                break;
            default:
                $responseCode = 0;
                $errMessage = 'Error in Rule action'; 
                break;
            }
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While '.$action.' Rule'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While '.$action.' Rule'; 
        }
        $resArr['action'] = $action;
        $resArr['ruleId'] = $ruleId;
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        $resArr['new'] = "{form_id='$formId', condition='$condition', when:{".implode(",", $when)."}, action:{".implode(",", $actions)."}}";
        $resArr['old'] = $oldRuleData; 
        return $resArr;
    }
    public function getRuleInfo($data)
    {
        $ruleId = isset($data['rule_id'])?intval($data['rule_id']):0;
        $conditions = $actions = array();
        $qry1 = "select distinct rule_condition_loop.loop_id,rule_condition.operator,rule.form_id,field.field_id,rule_condition_loop.comparision_op,rule_condition_loop.`value` as condition_val
                    from rule_condition_loop
                    left join rule_condition on rule_condition_loop.condition_id=rule_condition.condition_id
                    left join rule on rule.rule_id=rule_condition.rule_id
                    left join field on field.field_id=rule_condition_loop.field_id
                    where rule_condition.rule_id= ?;";
        $statements = $this->_adapter->createStatement($qry1, array($ruleId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        
        if ($count > 0) {
            $conditions = $resultSet->toArray();
        }
        
        $qry2 = "select distinct rule_id, action, rule_action_id, action_workflow_id as action_val, action_fields
                 from rule_action where rule_id= ?;";
        
        $statements = $this->_adapter->createStatement($qry2, array($ruleId));
        $statements->prepare();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $actions = $resultSet->toArray();
        }
        
        return array(0=>$conditions,1=>$actions);
    }
    
    public function getOldRulesForAudit($ruleId)
    {
        $sql = new Sql($this->_adapter);       
        $select = $sql->select()->columns(array("form_id"));
        $select->from('rule')->join('rule_condition', 'rule.rule_id = rule_condition.rule_id', array('operator','condition_id'));
        $select->where(array('rule.rule_id' => $ruleId));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute(); 
        $rule = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $rule = $resultSet->toArray();
            foreach ($rule as $r) {
                $oldFormId = $r['form_id'];
                $oldCondition = $r['operator'];
                $conditions = $sql->select()
                    ->columns(array("field_id", "comparision_op", "value"))
                    ->from("rule_condition_loop")
                    ->where(array('condition_id' => $r['condition_id']));
                $selectString = $sql->prepareStatementForSqlObject($conditions);
                $results = $selectString->execute(); 
                $when = array();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = $resultSet->count();
                if ($count > 0) {
                    $when = $resultSet->toArray(); 
                    $oldWhen = array();
                    foreach ($when as $w) {
                        $oldWhen[] = "{field:'".$w["field_id"]."', condition:'".$w["comparision_op"]."', value:'".$w["value"]."'}"; 
                    }
                }  
            }
            $actionQuery = $sql->select()
                ->columns(array("action", "action_workflow_id", "action_fields"))
                ->from("rule_action")
                ->where(array('rule_id' => $ruleId));
            $selectString = $sql->prepareStatementForSqlObject($actionQuery);
            $results = $selectString->execute(); 
            $actions = array();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $actions = $resultSet->toArray();
                $oldActions = array();
                foreach ($actions as $a) {
                    $oldActions[] = "{action_name:'".$a["action"]."', workflow_id:'".$a["action_workflow_id"]."', action_fields:{'".str_replace(",", "','", $a["action_fields"])."'}}"; 
                }  
            }
        }
        return "{form_id='$oldFormId', condition='$oldCondition', when:{".implode(",", $oldWhen)."}, action:{".implode(",", $oldActions)."}}"; ;
    }
    
    public function saveFields($post)
    {
        $session = new SessionContainer();
        $formulaFieldsContainer = $session->getSession('formula_fields');
        $trackerId = isset($post['tracker_id'])?intval($post['tracker_id']):0;
        $formId = isset($post['form_id'])?intval($post['form_id']):0;
        $workflowId = isset($post['workflow_id'])?intval($post['workflow_id']):0;
        $labelArr = isset($post['names'])?$post['names']:array();
        $namesArr = isset($post['label_names'])?$post['label_names']:array();
        $types = isset($post['types'])?$post['types']:array();
        $formulas = isset($post['formula_id'])?$post['formula_id']:array();
        $expected = isset($post['expected'])?$post['expected']:array();
        $kpivalues = isset($post['kpivalues'])?$post['kpivalues']:array();
        $fieldSortOrder = isset($post['field_sort_order'])?$post['field_sort_order']:array();
        $fieldIdArr = isset($post['field_id_arr'])?$post['field_id_arr']:array();
        $formulaArray = array();
        $tableArr = array();
        $oldFieldsData = $newFieldsData = $ids = $resArr = array();
        $connection = null;
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            if (is_array($namesArr)) {
                if (!empty($fieldIdArr)) {
                    $action = 'updating';
                    $oldFieldsData = $this->getFieldsAllInfo($fieldIdArr);
                    $ids[] = implode(",", $fieldIdArr);
                    foreach ($labelArr as $key => $value) {
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
                            $fieldType = $types[$key];
                            // $options = $expected[$key];
                            $kpi = $kpivalues[$key];
                            $sortOrder = $fieldSortOrder[$key];
                            $sql = new Sql($this->_adapter);
                            $newFieldsData[] = "{Order: ".$sortOrder.", 'Field Label Name': '".$label."','Type':'".$fieldType."', 'KPI':'".$kpi."'}";
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
                            $statement->execute();
                        }
                    }
                    $responseCode = 1;
                    $errMessage = "Fields updated successFully";
                } else {
                    $i = 0;
                    $action = 'adding';
                    foreach ($namesArr as $key => $fieldName) {
                        $query = "Select field.* From field INNER JOIN workflow On field.workflow_id = workflow.workflow_id
                         INNER JOIN form ON workflow.form_id = form.form_id Where form.form_id=? AND field.field_name = ?";
                        $statements = $this->_adapter->createStatement($query, array($formId,$fieldName));
                        $statements->prepare();
                        $results = $statements->execute();
                        $resultSet = new ResultSet;
                        $resultSet->initialize($results);
                        $resultSet->buffer();
                        $count = $resultSet->count();
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
                            if ($fieldType == "User Role" && isset($formulas[$key])) {
                                $newData['formula'] = $formulas[$key];
                                $options = $formulas[$key];
                            }
                            $newFieldsData[] = "{Order: ".$sortOrder.", 'Field Label Name': '".$label."','Field Name':'".$fieldName."','Type':'".$fieldType."', 'KPI':'".$kpi."', 'Options':'".$options."'}";
                            $insert->values($newData);
                            $statement = $sql->prepareStatementForSqlObject($insert);
                            $results = $statement->execute();

                            $ids[] = $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
                            $tableArrVal = array();
                            $tableArrVal['field_id'] = $lastInsertID;
                            $tableArrVal['field_name'] = $fieldName;
                            $tableArrVal['type'] = $fieldType;
                            $tableArrVal['label'] = $label;
                            if ($fieldType == 'Formula') {
                                $formulaFieldsContainer->formula_fields[] = $tableArrVal;
                                $formula_sess = 1;
                            }
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
                            if ($fieldType == "User Role" && isset($formulas[$key])) {
                                $newData['formula'] = $formulas[$key];
                                $options = $formulas[$key];
                            }
                            $newFieldsData[] = "{Order: ".$sortOrder.", 'Field Label Name': '".$label."','Field Name':'".$fieldName."','Type':'".$fieldType."', 'KPI':'".$kpi."', 'Options':'".$options."'}";
                            $insert->values($newData);
                            $statement = $sql->prepareStatementForSqlObject($insert);
                            $results = $statement->execute();
                            $ids[] = $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
                            $tableArrVal = array();
                            $tableArrVal['field_id'] = $lastInsertID;
                            $tableArrVal['field_name'] = $fieldName;
                            $tableArrVal['type'] = $fieldType;
                            $tableArrVal['label'] = $label;
                            if ($fieldType == 'Formula') {
                                $formulaFieldsContainer->formula_fields[] = $tableArrVal;
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
                        $table_name = "form_$trackerId" . "_$formId";
                        $queryAlter = "ALTER TABLE $table_name $alter";

                        $statements = $this->_adapter->createStatement($queryAlter);
                        $statements->prepare();
                        $results = $statements->execute();
                    }
                    $formulaSess = 0;
                    if (count($formulaArray) > 0) {
                        $formulaFieldsContainer->formula_fields = $formulaArray;
                        $formulaSess = 1;
                    }
                    $responseCode = 1;
                    $errMessage = "Fields added successFully";
                }
            }
            $connection->commit();
            
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While '.$action.' fields'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While '.$action.' fields'; 
        }
        $resArr['action'] = $action;
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        $resArr['new'] = !empty($newFieldsData)?"{".implode(",", $newFieldsData)."}":"";
        $resArr['old'] = !empty($oldFieldsData)?"{".implode(",", $oldFieldsData)."}":""; 
        $resArr['fieldId'] = implode(",", $ids);
        return $resArr;
    }
    public function getFieldsAllInfo($field_id_arr)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('field');
        $select->where->in('field_id', $field_id_arr);
        $ids_string = implode(',', $field_id_arr);
        $select->order(array(new Expression('FIELD (field_id, ' . $ids_string . ')')));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr1 = $resultSet->toArray();
            foreach ($arr1 as $value) {
                $arr[] = "{Order: ".$value['sort_order'].", 'Field Label Name': '".$value['label']."','Type':'".$value['field_type']."', 'KPI':'".$value['kpi']."'}"; 
            }
        }
        return $arr;
    }
}
