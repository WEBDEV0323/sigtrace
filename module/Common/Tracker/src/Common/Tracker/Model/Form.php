<?php

namespace Common\Tracker\Model;

use Zend\Db\adapter\adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Session\Container\SessionContainer;
use Zend\Mvc\Controller\AbstractActionController;

class Form extends AbstractActionController
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
        $roleId=(int)$roleId;
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
    public function checkFormExist($trackerId, $value)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('form');
        $select->where(array('form_name ' => "$value", 'tracker_id' => $trackerId));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        return $count;
    }
    
    public function addNewForm($post)
    {
        $connection = null;
        $resultsArr = array();
        $formId = 0;
        try {
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            $trackerId = isset($post['tracker_id'])?htmlspecialchars($post['tracker_id'], ENT_QUOTES):0;
            $formName = isset($post['form_name'])?htmlspecialchars($post['form_name'], ENT_QUOTES):"";
            $record = isset($post['record'])?$post['record']:"";
            $description = isset($post['description'])?$post['description']:"";
            if ($trackerId != 0 && $formName != "" && $record != "") {
                $queryTracker = "SELECT * FROM form Where tracker_id = ? AND form_name = ? ";
                $statements = $this->_adapter->createStatement($queryTracker, array($trackerId, $formName));
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = $resultSet->count();
                if ($count > 0) {
                    if ($connection instanceof ConnectionInterface) {
                        $connection->rollback();
                    }
                    $responseCode = 0;
                    $errMessage = "Form Name exists already";
                } else {
                    $session = new SessionContainer();
                    $userSession = $session->getSession("user");
                    $uId = $userSession->u_id;
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
                    $statement->execute();
                    $formId = $this->_adapter->getDriver()->getLastGeneratedValue();
                    
                    /* for form creation */
                    $query = "CREATE TABLE IF NOT EXISTS form_".$trackerId."_".$formId." 
                                (
                                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                                    `created_by` INT(11), 
                                    `last_updated_by` INT(11), 
                                    `created_date_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
                                    `is_deleted` ENUM('Yes','No') NOT NULL DEFAULT 'No',
                                     PRIMARY KEY (`id`)
                                ) collate utf8_general_ci";
                    $statements = $this->_adapter->query($query);
                    $statements->execute();
                }
                $connection->commit();
                $responseCode = 1;
                $errMessage = 'Form added Successfully';
            } else {
                if ($connection instanceof ConnectionInterface) {
                    $connection->rollback();
                }
                $responseCode = 0;
                $errMessage = 'Error While adding form';
            }
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While adding form'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While adding form'; 
        }
        $resultsArr['formId'] = $formId;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
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
}

