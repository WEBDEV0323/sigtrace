<?php

namespace ASRTRACE\Dashboard\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Mvc\Controller\AbstractActionController;

class Dashboard extends AbstractActionController
{

    /**
     * Make the Adapter object available as local protected variable
     *
     * @param Adapter $adapter - DB PDO PgSQL conn
     */
    public function __construct(Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }
    public function trackerCheckForms($trackerId, $formId)
    {
        $queryTracker = "SELECT * FROM form WHERE tracker_id =?  AND form_id =? ";
        $statements = $this->_adapter->createStatement($queryTracker, array($trackerId,$formId));
        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $responseCode = 0;
        $errMessage = "";
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            $table_details = $arr[0];
            $responseCode = 1;
            $resArr['responseCode'] = $responseCode;
        } else {
            $errMessage = "Form Not Found";
            $table_details = array();
        }
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        $resArr['form_details'] = array();
        return $resArr;
    }
    
    public function getConfigs()
    {
        $query="SELECT config_key,config_value FROM `config`";
        $statements = $this->_adapter->createStatement($query);
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $res = array();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            $res = array_column($arr, 'config_value', 'config_key');       
        }
        return $res;
    }
    
    public function getDashboardFilters($trackerId, $formId, $typeOfFilter = '')
    {
        $arr = array();
        try {
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->columns(array('filter_name', 'filter_label', 'condition'));
            $select->from('dashboard_filters');
            if ($typeOfFilter != '') {
                $select->where(array('trackerId' => $trackerId, 'formId'=>$formId, 'archived'=>0, 'filter_name'=>$typeOfFilter)); 
            } else {
                $select->where(array('trackerId' => $trackerId, 'formId'=>$formId, 'archived'=>0));
            }
            $selectString = $sql->prepareStatementForSqlObject($select);
            $results = $selectString->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arr = $resultSet->toArray();
            }
            return $arr;
        } catch(\Exception $e) {
            return $arr;
        } catch(\PDOException $e) {
            return $arr;
        } 
    }
    
    public function getDashboardQueries($trackerId, $formId, $typeOfReport = '')
    {
        $arr = array();
        try {
            $sql = new Sql($this->_adapter);
            $select = $sql->select();
            $select->from('dashboard');
            if ($typeOfReport != '') {
                $select->where(array('trackerId' => $trackerId, 'formId'=>$formId, 'archived'=>0, 'typeOfReport'=>$typeOfReport)); 
            } else {
                $select->where(array('trackerId' => $trackerId, 'formId'=>$formId, 'archived'=>0));
            }
            $selectString = $sql->prepareStatementForSqlObject($select);
            $results = $selectString->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            if ($count > 0) {
                $arr = $resultSet->toArray();
            }
            return $arr;
        } catch(\Exception $e) {
            return $arr;
        } catch(\PDOException $e) {
            return $arr;
        } 
    }
    
    public function getDashboardCountReports($trackerId, $formId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->columns(array('filter_name', 'filter_label', 'condition', 'countReportQuery'));
        $select->from('dashboard_filters');
        $select->where(array('trackerId' => $trackerId, 'formId'=>$formId, 'archived'=>0, 'isUsedInCountReports' => 0));
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
    public function getDashboardReports($dashboardQueries, $trackerId, $formId, $countReportQueries = array(), $filter="")
    {
        if ($trackerId == 0 OR $formId == 0) {
            return array();
        }
        $query = "SELECT '_id'";
        $types = array();
        $filterQueryArray = $this->getDashboardFilters($trackerId, $formId, $filter);
        $filterCondition = (!empty($filterQueryArray) && array_column($filterQueryArray, 'condition')[0] != '')?" AND ".array_column($filterQueryArray, 'condition')[0]:"";
        foreach ($dashboardQueries as $dashboard) {
            $table = $dashboard['countQuery'];
            $where = ($dashboard['where'] != '')?" WHERE ".$dashboard['where']:"";
            $types[$dashboard['typeOfReport']] = $dashboard['label'];
            $query .= ",(".$table." ".$where." ".$filterCondition.") as '".$dashboard['typeOfReport']."'";
        } 
        if (!empty($countReportQueries)) {
            foreach ($countReportQueries as $countReport) {
                $countQuery = ($countReport['condition'] != '')?" AND ".$countReport['condition']:" WHERE 1";
                $types[$countReport['filter_name']] = $countReport['filter_label'];
                $query .= ",(".$countReport['countReportQuery'].$countQuery."".$filterCondition.") as '".$countReport['filter_name']."'"; 
            }
        }
        $statements = $this->_adapter->createStatement("SELECT * FROM (" . $query . ") as T");
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        $arr = $result = array();
        if ($count > 0) {
            $arr = $resultSet->toArray(); 
            array_shift($arr[0]); 
            foreach ($arr[0] as $k=>$v) {
                $result[] = array("name"=>$types[$k], "type"=>$k, "count"=>$v);
            }
        }
        return $result;
    }
    
    public function fetchAllData($trackerId, $formId, $type, $filter)
    {
        $listsQueryData = $this->getDashboardQueries($trackerId, $formId, $type);
        $filterQueryData = $this->getDashboardFilters($trackerId, $formId, $filter);
        $arr = array();
        try{
            if (!empty($listsQueryData)) {
                foreach ($listsQueryData as $key=>$list) {
                    $dbQuery = ($list['listQuery'] != '')?$list['listQuery']:"";
                    $where = ($list['where'] != '')?" WHERE ".$list['where']:"";
                    $filter = ($filterQueryData[$key]['condition'] != '')?($where != '')?" AND ".$filterQueryData[$key]['condition']:" WHERE ".$filterQueryData[$key]['condition']:"";
                    $groupBy = isset($list["groupBy"])?" GROUP BY ".$list["groupBy"]:"";
                    $orderBy = isset($list["orderBy"])?" ORDER BY ".$list["orderBy"]:"";
                    $query = $dbQuery." ".$where." ".$filter." ".$groupBy." ".$orderBy;
                    $arr['labels'] = $list['listQueryLabels'];
                } 
                $statements = $this->_adapter->createStatement($query);
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = $resultSet->count();
                $arr['data'] = array();
                if ($count > 0) {
                    $arr['data'] = $resultSet->toArray();
                    $arr['names'] = array_keys($arr['data'][0]);
                }
            } else {
                $arr['labels'] = $arr['data'] = $arr['names'] = $names = array();
                $query = "SELECT field_name,label from field where workflow_id = (select workflow_id from workflow where form_id=".$formId." limit 1) AND lower(field_type) != 'heading'";
                $statements = $this->_adapter->createStatement($query);
                $statements->prepare();
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = $resultSet->count();
                if ($count > 0) {
                    $arr['labels'] = $resultSet->toArray();
                    $arr['names'] = array_column($arr['labels'], 'field_name');
                    $arr['labels'] = implode(",", array_column($arr['labels'], 'label'));
                }
                array_unshift($arr['names'], "id");
                $sql = new Sql($this->_adapter);
                $select = $sql->select();
                $select->from("form_".$trackerId."_".$formId);
                $select->columns($arr['names']);
                $select->where(array('is_deleted' => 'No'));
                $select->order('id DESC');
                $selectString = $sql->prepareStatementForSqlObject($select);
                $dRresults = $selectString->execute();
                $data = array();
                $dResultSet = new ResultSet;
                $dResultSet->initialize($dRresults);
                $dResultSet->buffer();
                $dCount = $dResultSet->count();
                if ($dCount > 0) {
                    $data = $dResultSet->toArray();
                    foreach ($data as $key => $value) {
                        $data[$key]['action'] = $value['id'];
                    }
                }
                $arr['data'] =  $data; 
            }
            return $arr;
        } catch(\Exception $e) {
            return $arr;
        } catch(\PDOException $e) {
            return $arr;
        } 
        
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
    public function showWorkflowLink($formId, $roleId, $roleName)
    {
        $arr = array();
        try {
            if ($roleName=='SuperAdmin'||$roleName=='Administrator') {
                $queryFields = "SELECT workflow_id, workflow_name, 'Yes' AS can_update FROM workflow WHERE form_id = ? AND `status`=? AND show_link = ?";
                $statements = $this->_adapter->createStatement($queryFields, array($formId,'Active','Yes'));
            } else {
                $queryFields = "SELECT w.workflow_id,w.workflow_name,wr.can_update FROM workflow AS w JOIN workflow_role AS wr  WHERE w.workflow_id = wr.workflow_id AND w.form_id = ? AND w.status = ? AND wr.role_id = ? AND w.show_link = ?";
                $statements = $this->_adapter->createStatement($queryFields, array($formId,'Active',$roleId,'Yes'));
            }
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
        } catch(\Exception $e) {
            return $arr;
        } catch(\PDOException $e) {
            return $arr;
        } 
    }

    public function formIdCheck($trackerId, $roleName, $roleId)
    {
        if ($roleName != 'SuperAdmin' && $roleName != 'Administrator') {
            $queryTracker = "SELECT form.form_id, form.form_name
            FROM tracker
            JOIN form ON tracker.tracker_id = form.tracker_id
            JOIN form_access_setting on form_access_setting.form_id=form.form_id
            AND form_access_setting.role_id=$roleId
            AND form_access_setting.can_read='Yes'
            WHERE tracker.tracker_id = ?";
        } else {
            $queryTracker = "SELECT form.form_id, form.form_name
            FROM tracker
            JOIN form ON tracker.tracker_id = form.tracker_id
            WHERE tracker.tracker_id = ?";
        }
        
        $resArr = array();
        $statements = $this->_adapter->createStatement($queryTracker, array($trackerId));
        $statements->prepare();
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
}
