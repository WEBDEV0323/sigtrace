<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ASRTRACE\Tracker\Model;

use Zend\Db\adapter\adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Session\Container;
use Zend\Db\Sql\Expression;
use Session\Container\SessionContainer;
use Zend\Mvc\Controller\AbstractActionController;

class AccessModule extends AbstractActionController
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
    
    
    public function getupdateaccesssetting($dataArray)
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
    
    public function getreadaccesssetting($dataArray)
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
        //$select->limit(1);
        $newadpater = $this->_adapter;
        $selectString = $sql->prepareStatementForSqlObject($select);// echo $sql->getSqlStringForSqlObject($select);die;
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
    
    public function getformaccessdetail($trackerId)
    {
        $res = array();
        $connection = $this->_adapter->getDriver()->getConnection();
        $qry = 'call sp_getformdefaultdetail("' . $trackerId . '")';
        $result = $connection->execute($qry);
        try{
            $statement = $result->getResource();
            $resultSet1 = $statement->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Exception $e) {
            $resultSet1 =array();
        }
        $res[0] = $resultSet1;
        try{
            $statement->nextRowSet(); // Advance to the second result set
            $resultSet2 = $statement->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Exception $e) {
            $resultSet2 =array();
        }
        $res[1] = $resultSet2;
        try{
            $statement->nextRowSet(); // Advance to the second result set
            $resultSet3 = $statement->fetchAll(\PDO::FETCH_OBJ);
        } catch (\Exception $e) {
            $resultSet3 =array();
        }
        $res[2] = $resultSet3;
        return $res;
    }
    
    public function getformaccesssetting($dataArray)
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
}
