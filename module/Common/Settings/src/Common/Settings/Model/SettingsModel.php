<?php
namespace Common\Settings\Model;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Session\Container\SessionContainer;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;

class SettingsModel extends AbstractActionController
{
    protected $_adapter;
    public function __construct(Adapter $adapter)
    {
        $this->_adapter  = $adapter;
    }
    /**
     * Function used to get instance of DB 
     *
     * @return instance 
     */
    public function dbConnection()
    {
         return $this->getServiceLocator()->get('trace')->getDriver()->getConnection();
    }
    
    public function getFormAccessDetails($trackerId)
    {
        $res[0] = $res[1] = $res[2] = array();
        $query1 = "select distinct fm.form_id, fm.form_name,fm.tracker_id from form fm where tracker_id = ? order by fm.form_id";
        $statements1 = $this->_adapter->createStatement($query1, array($trackerId));
        $statements1->prepare();
        $results1 = $statements1->execute();
        $resultSet1 = new ResultSet;
        $resultSet1->initialize($results1);
        $resultSet1->buffer();
        $count1 = $resultSet1->count();
        if ($count1 > 0) {
            $res[0] = $resultSet1->toArray();
        }
        $query2 = "SELECT * FROM `role` where rid!=1 AND archived=0  AND tracker_id = ? order by rid";
        $statements2 = $this->_adapter->createStatement($query2, array($trackerId));
        $statements2->prepare();
        $results2 = $statements2->execute();
        $resultSet2 = new ResultSet;
        $resultSet2->initialize($results2);
        $resultSet2->buffer();
        $count2 = $resultSet2->count();
        if ($count2 > 0) {
            $res[1] = $resultSet2->toArray();
        }
        $query3 = "SELECT * FROM form_access_setting left join form on form.form_id=form_access_setting.form_id where form.tracker_id=? order by form_access_setting.form_id";
        $statements3 = $this->_adapter->createStatement($query3, array($trackerId));
        $statements3->prepare();
        $results3 = $statements3->execute();
        $resultSet3 = new ResultSet;
        $resultSet3->initialize($results3);
        $resultSet3->buffer();
        $count3 = $resultSet3->count();
        if ($count3 > 0) {
            $res[2] = $resultSet3->toArray();
        }
        return $res;
    }
    
    public function getTrackerFormsByTrackerId($trackerId)
    {
        $resArr['forms'] = array();
        $query = "SELECT tracker . tracker_id,  form.form_id, form.form_name
            FROM tracker
            JOIN form ON tracker.tracker_id = form.tracker_id
            WHERE tracker.tracker_id = ?";
        $statements = $this->_adapter->createStatement($query, array($trackerId));
        $statements->prepare();
        $resArr['forms'] = $resArr['tracker_details'] = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $resArr['forms'] = $resultSet->toArray();
        }
    }
    public function trackerResults($trackerId)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $trackerContainer = $session->getSession('tracker');
        
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
        $resArr['forms'] = $resArr['tracker_details'] = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $resArr['forms'] = $resultSet->toArray();
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
            $resArr['tracker_details'] = $resultSet->toArray()[0];
        }
        return $resArr;
    }
    public function getTrackerRoles($trackerId)
    {
        $queryClients = "SELECT user.*, user_role_tracker.group_id, role.role_name
            FROM user
            LEFT JOIN user_role_tracker ON user.u_id = user_role_tracker.u_id
            LEFT JOIN `role` ON user_role_tracker.group_id = role.rid
            WHERE user_role_tracker.tracker_id = '".$trackerId."'";
        $statements = $this->_adapter->query($queryClients);
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
    
    public function getFormAccessSetting($dataArray)
    {
        $formId = $dataArray['form_id'];
        $roleId = $dataArray['role_id'];
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('form_access_setting');
        $select->columns(array('form_id','role_id','can_read'));
        $select->where->in('form_id', $formId);
        $select->where(array('role_id' => $roleId));
        $formIds = implode(',', $formId);
        $select->order(array(new Expression('FIELD (`form_id`, ' . $formIds . ')')));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select); 
       
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
    
    public function saveFormSetting($dataArray)
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
    
    public function getRecordAccessSettings($dataArray)
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
        $selectString = $sql->prepareStatementForSqlObject($select);
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
    
    public function saveRecordAccessSetting($dataArray)
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
            $statement->execute();
        }
        $responseCode = 1;
        $errMessage = "Success";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    
    public function getWorkflowRoleForForms($trackerId)
    {
        $res = array();
        $connection = $this->_adapter->getDriver()->getConnection();
        $qry = 'call sp_getWorkflowRoleForForms("' . $trackerId . '")';
        $result = $connection->execute($qry);
        try {
            $statement = $result->getResource();
            $resultSet1 = $statement->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Exception $e) {
            $e->getMessage();
            $resultSet1 =array();
        }
        $res[0] = $resultSet1;
        try {
            $statement->nextRowSet(); // Advance to the second result set
            $resultSet2 = $statement->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Exception $e) {
            $e->getMessage();
            $resultSet2 =array();
        }
        $res[1] = $resultSet2;
        return $res;
    }
    
    public function getReadAccessSetting($dataArray)
    {
        $formId = $dataArray['form_id'];
        $roleId = $dataArray['role_id'];
        $workflowid = $dataArray['workflow_id'];
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('workflow_role');
        $select->columns(array(`workflow_role` . 'workflow_id',`workflow_role` . 'can_read', `workflow_role` . 'role_id'));
        $select->join('workflow', 'workflow.workflow_id = workflow_role.workflow_id', array(`workflow` . 'form_id'), 'left');
        $select->where(array('form_id' => $formId, 'role_id ' => $roleId));
        $workflowid = implode(',', $workflowid);
        $select->order(array(new Expression('FIELD (`workflow_role`.workflow_id, ' . $workflowid . ')')));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);
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
    
    public function saveReadSetting($dataArray)
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
            $statement->execute();
            $i++;
        }

        $responseCode = 1;
        $errMessage = "Success";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    
    public function saveUpdateSetting($dataArray)
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
            $selectString = $sql->prepareStatementForSqlObject($select);
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
            $statement->execute();
            $i++;
        }
        $resArr['responseCode'] = 1;
        $resArr['errMessage'] = "Success";
        return $resArr;
    }
    
    public function getUpdateAccessSetting($dataArray)
    {
        $formId = $dataArray['form_id'];
        $roleId = $dataArray['role_id'];
        $workflowid = $dataArray['workflow_id'];
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('workflow_role');
        $select->columns(array(`workflow_role` . 'workflow_id',`workflow_role` . 'can_update', `workflow_role` . 'role_id'));
        $select->join('workflow', 'workflow.workflow_id = workflow_role.workflow_id', array(`workflow` . 'form_id'), 'left');
        $select->where(array('form_id' => $formId, 'role_id ' => $roleId));
        $workflowid = implode(',', $workflowid);
        $select->order(array(new Expression('FIELD (`workflow_role`.workflow_id, ' . $workflowid . ')')));
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select); 
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
    
    public function getReportAccessSetting($trackerId)
    {
        $res[0] = $res[1] = $res[2] = $res[3] = array();
        $query1 = "select distinct fm.form_id, 
                fm.form_name, fm.tracker_id, report.report_id, report.report_name from form fm  
                left join report on report.form_id = fm.form_id
                where fm.tracker_id = ? and LOWER(report.archived) = 'no' order by fm.form_id";
        $statements1 = $this->_adapter->createStatement($query1, array($trackerId));
        $statements1->prepare();
        $results1 = $statements1->execute();
        $resultSet1 = new ResultSet;
        $resultSet1->initialize($results1);
        $resultSet1->buffer();
        $count1 = $resultSet1->count();
        if ($count1 > 0) {
            $res[0] = $resultSet1->toArray();
        }
        
    
        $query2 = "SELECT * FROM `role` where rid != 1 AND archived = 0  AND tracker_id = ? order by rid";
        $statements2 = $this->_adapter->createStatement($query2, array($trackerId));
        $statements2->prepare();
        $results2 = $statements2->execute();
        $resultSet2 = new ResultSet;
        $resultSet2->initialize($results2);
        $resultSet2->buffer();
        $count2 = $resultSet2->count();
        if ($count2 > 0) {
            $res[1] = $resultSet2->toArray();
        }
        
        $query3 = "SELECT * FROM report_access_setting left join form on form.form_id=report_access_setting.form_id
                   where form.tracker_id=? order by report_access_setting.form_id";
        $statements3 = $this->_adapter->createStatement($query3, array($trackerId));
        $statements3->prepare();
        $results3 = $statements3->execute();
        $resultSet3 = new ResultSet;
        $resultSet3->initialize($results3);
        $resultSet3->buffer();
        $count3 = $resultSet3->count();
        if ($count3 > 0) {
            $res[2] = $resultSet3->toArray();
        }
        
        $query4 = "select distinct fm.form_id,fm.form_name,fm.tracker_id,al.report_id,al.report_name from form fm  
                   join report al on al.form_id=fm.form_id where tracker_id=? 
                   order by fm.form_id,al.report_name";
        $statements4 = $this->_adapter->createStatement($query4, array($trackerId));
        $statements4->prepare();
        $results4 = $statements4->execute();
        $resultSet4 = new ResultSet;
        $resultSet4->initialize($results4);
        $resultSet4->buffer();
        $count4 = $resultSet4->count();
        if ($count4 > 0) {
            $res[3] = $resultSet4->toArray();
        }
        return $res;
    }
    public function saveReportAccessSetting($dataArray)
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
            $selectString = $sql->prepareStatementForSqlObject($select);
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
            }
            $statement->execute();
            $i++;
        }

        $responseCode = 1;
        $errMessage = "Success";
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    
    public function getReportSetting($dataArray)
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
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $array = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $array = $resultSet->toArray();
        }
        return $array;
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
    
    public function deleteRule($ruleId, $formId)
    { 
        $connection = null;
        $ruleData = $this->getOldRulesForAudit($ruleId);
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            $query = "UPDATE rule SET status = ?, archive = ? WHERE rule_id = ?";
            $ruleDeleteQuery = $this->_adapter->createStatement($query, array("Inactive", "1", $ruleId));
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
    
    public function saveAttachmentInfo($path, $uid, $trackerId)
    {
        $sql = new Sql($this->_adapter);
        $newData = array(
            'attachment_active' => 0
        );
        $update = $sql->update('attachment');
        $update->set($newData);
        $update->where(
            array(
            'attachment_active' => 1,
            )
        );

        $selectString = $sql->getSqlStringForSqlObject($update);
        $this->_adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);

        $newData = array(
            'attachment_url' => $path,
            'attachment_uid' => $uid, 'attachment_active' => 1,
            'attachment_tracker_id' => $trackerId);
        $insert = $sql->insert('attachment');
        $insert->values($newData);
        $selectString = $sql->getSqlStringForSqlObject($insert);
        $results = $this->_adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
        return $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();
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
}
