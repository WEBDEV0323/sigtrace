<?php

namespace Common\Trigger\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Mvc\Controller\AbstractActionController;

class Trigger extends AbstractActionController
{
    protected $_adapter;

    /**
     * Make the Adapter object available as local protected variable
     *
     * @param Adapter $adapter - DB PDO PgSQL conn
     */
    public function __construct(Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }
    
    public function checkTrigger($action = "", $trackerId = 0, $formId = 0, $oldData = array(), $newData= array(), $recordId = 0, $userId = 0 )
    { 
        
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('trigger');
        $select->where(array('archived' => '0', 'tracker_id' => $trackerId, 'source' => $formId));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            foreach ($arr as $trigger) {
                switch ($action) {
                case "added":
                    break;
                case "modified":
                    if ($trigger['trigger_when'] == 'modified' && $recordId != 0) {
                            
                        $whenCondition = json_decode($trigger['when_conditions'], true);
                        $singleQuery = "SELECT IF ((SELECT count(id) FROM form_".$trackerId."_".$formId." WHERE id='".$recordId."'";
                        foreach ($whenCondition['single'] as $k=>$v) {
                            $singleQuery .= " AND ".$k."="."'".$v."'";
                        }
                        $singleQuery .=") > 0, 1, 0) as 'result'";  
                        $statementSingle = $this->_adapter->createStatement($singleQuery, array());
                        $results = $statementSingle->execute();
                        $resultSet = new ResultSet;
                        $resultSet->initialize($results);
                        $proceedTrigger = 'No';
                        if ($resultSet->toArray()[0]['result'] == '1') {
                            $proceedTrigger = 'Yes';
                            $groupQuery = isset($whenCondition['query'])?$whenCondition['query']: "";
                            if ($groupQuery != "") {
                                if (isset($oldData['ptname']) && $oldData['ptname'] != '') {   
                                    $groupQuery = str_replace("{{ptname}}", "'".$oldData['ptname']."'", $groupQuery);
                                }
                                if (isset($oldData['nvl']) && $oldData['nvl'] != '') {   
                                    $groupQuery = str_replace("{{active_ingredient}}", "'".$oldData['active_ingredient']."'", $groupQuery);
                                }
                                    
                                    $statementQuery = $this->_adapter->createStatement($groupQuery, array());
                                    $results = $statementQuery->execute();
                                    $resultSet = new ResultSet;
                                    $resultSet->initialize($results);
                                if ($resultSet->toArray()[0]['result'] == '0') {
                                    $proceedTrigger = 'No';
                                }
                            } 
                        }

                        if ($proceedTrigger == 'Yes') {
                            $duplicateCheckFields = array();
                            if ($trigger['trigger_then'] == 'add record') {
                                $fields = json_decode($trigger['fields_to_copy'], true);
                                $triggerToQuery = $triggerFromQuery = $triggerFromQuerySingle = "";
                                if (!empty($fields)) {
                                    $connection = null;
                                    try {
                                        $connection = $this->_adapter->getDriver()->getConnection();
                                        $connection->beginTransaction();
                                        $triggerToQuery .= "INSERT into form_".$trackerId."_".$trigger['destination']."(";
                                        $triggerFromQueryHead = "SELECT ";
                                        $triggerFromQuery = "";
                                        $duplicateCheck = isset($fields['duplicateCheck']['status'])?strtolower($fields['duplicateCheck']['status']):'off';
                                        $duplicateCheckQuery = isset($fields['duplicateCheck']['query'])?$fields['duplicateCheck']['query']:'';
                                        $duplicateCheckQueryWhere = array();
                                        if (!empty($fields['single'])) {
                                             $triggerFromQuery .= "(SELECT ";
                                            if (!empty(array_column($fields['single']['fields'], 'from'))) {
                                                   $fromArray = array_column($fields['single']['fields'], 'from');
                                                $triggerFromQuery .= implode(
                                                    ", ", array_map(
                                                        function ($v, $k) {
                                                            return $v." as '".$v."_".$k."'";
                                                        }, $fromArray, array_keys($fromArray)
                                                    )
                                                );
                                                   $triggerFromQueryHead .= "S.*";
                                            }
                                            if (!empty(array_column($fields['single']['fields'], 'to'))) {  
                                                   $triggerToQuery .= implode(",", array_column($fields['single']['fields'], 'to'));
                                            }
                                                    $triggerFromQuery .= " FROM form_".$trackerId."_".$trigger['source'];
                                            if (!empty($fields['single']['query'])) {
                                                $triggerFromQuery .= str_replace("{{id}}", "'".$recordId."'", " WHERE ".$fields['single']['query']);
                                            }
                                                    $triggerFromQuery .= ") AS S";
                                                    $i = 0;
                                            foreach ($fields['single']['fields'] as $field) {
                                                if (isset($field['duplicate check']) &&  strtolower($field['duplicate check']) == 'yes') {
                                                    $duplicateCheckFields[$field['from']."_".$i] = $field['from']."_".$i;
                                                    $duplicateCheckQueryWhere[] = $field['to']."= ?";
                                                }
                                                $i++;
                                            }
                                        }
                                        if (!empty($fields['single']) && !empty($fields['group'])) {
                                            $triggerFromQuery .= ",";
                                            $triggerToQuery   .= ",";
                                        }
                                        if (!empty($fields['group'])) {
                                             $triggerFromQuery .= "(SELECT ";
                                             $i = 0; $triggerFromQueryHead .= ", G.*";
                                            foreach ($fields['group']['fields'] as $field) {
                                                   $fieldWhere = isset($field['query'])&&!empty($field['query']) ? $field['query'] : "";
                                                   $fieldDestionation = isset($field['destination'])&&!empty($field['destination']) ? $field['destination'] : "";
                                                   $groupWhere = !empty($fields['group']['query']) ? $fields['group']['query'] : "";
                                                   $where = "";
                                                if ($fieldDestionation != "") {
                                                    $where = " FROM ".$fieldDestionation." WHERE ". $fieldWhere ;
                                                } else if ($fieldWhere != "" || $groupWhere != "") {
                                                    $where = ($fieldWhere != "") ? " WHERE ".$fieldWhere." AND ".$groupWhere : " WHERE ".$groupWhere;
                                                }
                                                if (isset($oldData['ptname']) && $oldData['ptname'] != '') {   
                                                    $where = str_replace("{{ptname}}", "'".$oldData['ptname']."'", $where);
                                                }
                                                if (isset($oldData['active_ingredient']) && $oldData['active_ingredient'] != '') {   
                                                    $where = str_replace("{{active_ingredient}}", "'".$oldData['active_ingredient']."'", $where);
                                                }
                                                if ($i > 0) {
                                                    $triggerFromQuery .= ",";
                                                    $triggerToQuery .= ",";
                                                }
                                                    $triggerFromQuery .= "(SELECT DISTINCT ";
                                                if (isset($field['function'])) { 
                                                    $triggerFromQuery .= $field['function']."(";
                                                    if (strtolower($field['function']) == 'group_concat') {
                                                        $triggerFromQuery .= "DISTINCT ";
                                                    }
                                                }
                                                    $triggerFromQuery .= $field['from'];
                                                if (isset($field['function'])) { 
                                                    $triggerFromQuery .= ")";
                                                }
                                                if ($fieldDestionation != "") {
                                                    $triggerFromQuery .= $where.")";
                                                } else { 
                                                    $triggerFromQuery .= " FROM form_".$trackerId."_".$trigger['source'].$where.")"; 
                                                }
                                                    $triggerFromQuery .= " as `".$field['from']."`";
                                                    $triggerToQuery .= $field['to'];
                                                    $i++;
                                            }
                                             $triggerFromQuery .= ") AS G";
                                            foreach ($fields['group']['fields'] as $field) {
                                                if (isset($field['duplicate check']) &&  strtolower($field['duplicate check']) == 'yes') {
                                                    $duplicateCheckFields[$field['from']] = $field['from'];
                                                    $duplicateCheckQueryWhere[] = $field['to']."= ?";
                                                }
                                                    $i++;
                                            }
                                        }
                                        $triggerToQuery .= ")";
                                        $triggerFromQuery .= ")";
                                        $insertQ = $triggerToQuery;
                                        $selectQ = $triggerFromQueryHead." FROM (".$triggerFromQuery;
                                        if ($duplicateCheck == 'on') {
                                            $statementQuery = $this->_adapter->createStatement($selectQ, array());
                                            $results = $statementQuery->execute();
                                            $resultSet = new ResultSet;
                                            $resultSet->initialize($results);
                                            $resultSet->buffer();
                                            $count = $resultSet->count();
                                            if ($count > 0) { 
                                                $valArry = array();
                                                foreach ($resultSet->toArray()[0] as $k=>$v) {
                                                    if (in_array($k, $duplicateCheckFields)) { 
                                                        $valArry[] = $v;
                                                    }
                                                }
                                                $duplicateCheckQuery .= " WHERE ".implode(" AND ", $duplicateCheckQueryWhere); 
                                                $statementQuery = $this->_adapter->createStatement($duplicateCheckQuery, $valArry);
                                                $results = $statementQuery->execute();
                                                $resultSet = new ResultSet;
                                                $resultSet->initialize($results);
                                                $resultSet->buffer();
                                                $count = $resultSet->count();
                                                if ($count > 0) {
                                                    if ($resultSet->toArray()[0]['result'] == 0) {
                                                        $statementSingle = $this->_adapter->createStatement($insertQ." ".$selectQ, array());
                                                        $statementSingle->execute(); 
                                                        $recId = $this->_adapter->getDriver()->getLastGeneratedValue();
                                                        if (isset($fields['other']) && !empty($fields['other'])) {
                                                            $insertQ = "INSERT INTO ".$fields['other']['source']."(";
                                                            if (!empty(array_column($fields['other']['fields'], 'from'))) {
                                                                $insertQ .= implode(", ", array_column($fields['other']['fields'], 'from'));
                                                            }
                                                            $insertQ .= ")";
                                                            $selQ = "SELECT DISTINCT";
                                                            if (!empty(array_column($fields['other']['fields'], 'to'))) {  
                                                                $selQ .= implode(",", array_column($fields['other']['fields'], 'to'));
                                                            }
                                                            $selQ .= " FROM ".$fields['other']['destination']." WHERE ".$fields['other']['query'];
                                                            if ($recId > 0) {   
                                                                $selQ = str_replace("{{record_id}}", "'".$recId."'", $selQ);
                                                            }
                                                            if (isset($trigger['destination']) && intval($trigger['destination']) > 0) {   
                                                                $selQ = str_replace("{{form_id}}", "'".$trigger['destination']."'", $selQ);
                                                            }
                                                            if ($userId > 0) {   
                                                                $selQ = str_replace("{{user_id}}", "'".$userId."'", $selQ);
                                                            }

                                                            if (isset($oldData['ptname']) && $oldData['ptname'] != '') {   
                                                                $selQ = str_replace("{{ptname}}", "'".$oldData['ptname']."'", $selQ);
                                                            }

                                                            if (isset($oldData['active_ingredient']) && $oldData['active_ingredient'] != '') {   
                                                                $selQ = str_replace("{{active_ingredient}}", "'".$oldData['active_ingredient']."'", $selQ);
                                                            }
                                                            $statementSingle = $this->_adapter->createStatement($insertQ." ".$selQ, array());
                                                            $statementSingle->execute();

                                                        }
                                                    }
                                                }
                                            }
                                        } else {
                                            $statementSingle = $this->_adapter->createStatement($insertQ." ".$selectQ, array());
                                            $statementSingle->execute();
                                            $recId = $this->_adapter->getDriver()->getLastGeneratedValue();
                                            if (isset($fields['other']) && !empty($fields['other'])) {
                                                $insertQ = "INSERT INTO ".$fields['other']['source']."(";
                                                if (!empty(array_column($fields['other']['fields'], 'from'))) {
                                                    $insertQ .= implode(", ", array_column($fields['other']['fields'], 'from'));
                                                }
                                                $insertQ .= ")";
                                                $selQ = "SELECT DISTINCT";
                                                if (!empty(array_column($fields['other']['fields'], 'to'))) {  
                                                    $selQ .= implode(",", array_column($fields['other']['fields'], 'to'));
                                                }
                                                $selQ .= " FROM ".$fields['other']['destination']." WHERE ".$fields['other']['query'];
                                                if ($recId > 0) {   
                                                    $selQ = str_replace("{{record_id}}", "'".$recId."'", $selQ);
                                                }
                                                if (isset($trigger['destination']) && intval($trigger['destination']) > 0) {   
                                                    $selQ = str_replace("{{form_id}}", "'".$trigger['destination']."'", $selQ);
                                                }
                                                if ($userId > 0) {   
                                                    $selQ = str_replace("{{user_id}}", "'".$userId."'", $selQ);
                                                }

                                                if (isset($oldData['ptname']) && $oldData['ptname'] != '') {   
                                                    $selQ = str_replace("{{ptname}}", "'".$oldData['ptname']."'", $selQ);
                                                }

                                                if (isset($oldData['active_ingredient']) && $oldData['active_ingredient'] != '') {   
                                                    $selQ = str_replace("{{active_ingredient}}", "'".$oldData['active_ingredient']."'", $selQ);
                                                }
                                                $statementSingle = $this->_adapter->createStatement($insertQ." ".$selQ, array());
                                                $statementSingle->execute();


                                            }
                                        }  
                                        $connection->commit();
                                    } catch(\Exception $e) {
                                        if ($connection instanceof ConnectionInterface) {
                                            $connection->rollback();
                                        } 
                                    } catch(\PDOException $e) {
                                        if ($connection instanceof ConnectionInterface) {
                                            $connection->rollback();
                                        } 
                                    }
                                }
                            }
                        }
                    }
                    break;
                case "deleted":
                    break;
                default:
                    break;
                }
            }
        } 
    }
}

