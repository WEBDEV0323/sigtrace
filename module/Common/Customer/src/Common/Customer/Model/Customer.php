<?php

namespace Common\Customer\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Session\Container\SessionContainer;

class Customer
{
    protected $_adapter;

    /**
     * Make the _adapter object available as local protected variable
     *
     * @param _adapter $_adapter - DB PDO PgSQL conn
     */
    public function __construct(Adapter $_adapter)
    {
        $this->_adapter = $_adapter;
    }

    /*
     */

    public function getAllClients()
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('client');
        $select->where(array('archived' => 0));
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
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

    /*
     * Function to add new client and save values in database
     */

    public function saveCustomers($data, $customerId = 0)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession("user");
        $connection = null;
        $resultsArr = array();
        $action = 'adding';
        try { 
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            $sql = new Sql($this->_adapter);
            if ($customerId > 0) {
                $action = 'updating';
                $newData = array(
                    'client_name' => trim(addslashes($data['name'])),
                    'client_email' => trim(addslashes($data['email'])),
                    'project_manager_name' => trim(addslashes($data['pmName'])),
                    'description' => trim(addslashes($data['description'])),
                    'country' =>trim(addslashes($data['country']))
                );
                $update = $sql->update('client')
                    ->set($newData)
                    ->where(
                        array(
                            'client_id' => $customerId
                        )
                    );
                $statement = $sql->prepareStatementForSqlObject($update);
                $statement->execute();
                $responseCode = 2;
                $errMessage = 'Customer updated successfully';
            } else {
                $newData = array(
                    'client_name' =>trim(addslashes($data['name'])),
                    'client_email' =>trim(addslashes($data['email'])),
                    'project_manager_name' => trim(addslashes($data['pmName'])),
                    'description' => trim(addslashes($data['description'])),
                    'country' => trim(addslashes($data['country'])),
                    'created_by' => $userContainer->u_id
                );
                $insert = $sql->insert('client');
                $insert->values($newData);
                $statement = $sql->prepareStatementForSqlObject($insert);
                $statement->execute();
                $customerId = $this->_adapter->getDriver()->getLastGeneratedValue();
                $responseCode = 1;
                $errMessage = 'Customer added successfully';
            }
            $connection->commit();
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While '.$action.' Customer'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While '.$action.' Customer'; 
        }
        $resultsArr['customerId'] = $customerId;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }

    public function getCustomerInfo($customerId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('client');
        if ($customerId > 0) {
            $select->where(array('client_id' => $customerId));
        }
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
        $arr = $data = array();
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

    /*
     * Function to delete client :to make client archive
     */

    public function delete($customerId)
    {
        $connection = null;
        $resultsArr = array();
        try { 
            $connection = $this->_adapter->getDriver()->getConnection();
            $connection->beginTransaction();
            $sql = new Sql($this->_adapter);
            $update = $sql->update('client')
                ->set(array('archived' => 1))
                ->where(
                    array(
                        'client_id' => $customerId
                    )
                );
            $statement = $sql->prepareStatementForSqlObject($update);
            $statement->execute();
            $connection->commit();
            $responseCode = 1;
            $errMessage = "Customer deleted Successfully";
        } catch(\Exception $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While deleting Customer'; 
        } catch(\PDOException $e) {
            if ($connection instanceof ConnectionInterface) {
                $connection->rollback();
            }
            $responseCode = 0;
            $errMessage = 'Error While deleting Customer'; 
        }
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }

    /*
     * function to add tracker for client
     */

    public function saveTrackerForClient($post, $trackerId)
    {
        $sql = new Sql($this->_adapter);
        $trackerId = filter_var($trackerId, FILTER_SANITIZE_STRING);
        $clientId = (isset($post['c_select_client']) ? $post['c_select_client'] : $post['c_hidden']);
        if ($trackerId>0) {
            $SetData = array(
                'name' => addslashes($post['c_tracker'])
            );
            $WhereData = array(
                'tracker_id' => $trackerId
            );
            $update = $sql->update('tracker');
            $update->set($SetData);
            $update->where($WhereData);
            $statement = $sql->prepareStatementForSqlObject($update);
        } else {
            $insert = $sql->insert('tracker');
            $InsertData = array(
                'name' => addslashes($post['c_tracker']),
                'client_id' => addslashes($clientId),
                'created_by' =>  $_SESSION['u_id']
            );
            $insert->values($InsertData);
            $statement = $sql->prepareStatementForSqlObject($insert);
        }
        $results = $statement->execute();
        $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();

        $insert = $sql->insert('group');
        $newData = array(
            'group_name' => 'Administrator',
            'tracker_id' => $lastInsertID,
        );
        $insert->values($newData);
        $statement = $sql->prepareStatementForSqlObject($insert);
        $results = $statement->execute();
        return $lastInsertID;
    }

    /*
     * get all client list with tracker name
     */

    public function getAllClientsWithTracker()
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $qry = 'select ctable.*,ttable.name as tracker_name,ttable.tracker_id as tracker_id  from  client ctable  join tracker ttable on ttable.client_id=ctable.client_id where ctable.archived=0 and ttable.archived=0';
        $statements = $this->_adapter->query($qry);
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

    /*
     * while inserting into clien table check for duplicate
     */

    public function checkDuplicate($post, $customerId)
    {
        if ($customerId > 0) {
            $qry = "select if ((SELECT count(client_id) FROM client where client_name = '".$post['name']."' AND client_email='".$post['email']."' AND client_id <> '".$customerId."' AND archived = 0)>0,3,
            (if ((SELECT count(client_id) FROM client where client_name='".$post['name']."' AND client_id<>'".$customerId."' AND archived = 0)>0,2,
            (if ((SELECT count(client_id) FROM client where  client_email='".$post['email']."' AND client_id <> '".$customerId."' AND archived = 0)>0,1,0))))) as checkduplicate;";
        } else {
            $qry = "select if ((SELECT count(client_id) FROM client where client_name='".$post['name']."' AND client_email='".$post['email']."' AND archived = 0)>0,3,
            (if ((SELECT count(client_id) FROM client where client_name='".$post['name']."' AND archived = 0)>0,2,
            (if ((SELECT count(client_id)FROM client where  client_email='".$post['email']."' AND archived = 0)>0,1,0))))) as checkduplicate;";
        }
        $statements = $this->_adapter->query($qry);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray()[0];
        }
        $statements->getResource()->closeCursor();
        return $arr;
    }
    /*
     * Function to delete tracker :to make tracker archive
     */
    public function deletetracker($trackerId)
    {
        $sql = new Sql($this->_adapter);
        $update = $sql->update('tracker')
            ->set(array('archived' => 1))
            ->where(
                array(
                    'tracker_id' => $trackerId
                )
            );
        $selectString = $sql->getSqlStringForSqlObject($update);
        $results = $this->_adapter->query($selectString, adapter::QUERY_MODE_EXECUTE);
    }

    /*
     * get country name
     */

    public function getCountryName()
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $qry="Select * from code_list_option where code_list_id in (SELECT group_concat(code_list_id) FROM code_list where code_list_name='Country')";
        $statements = $this->_adapter->query($qry);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
        }
        $statements->getResource()->closeCursor();
        return $arr;
    }
    
    public function getCountryNameById($countryId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $qry = "Select label from code_list_option where option_id =".$countryId;
        $statements = $this->_adapter->query($qry);
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray()[0]['label'];
        }
        $statements->getResource()->closeCursor();
        return $arr;
    }

    /*
    * get tracker information
    */

    public function getTrackerInformation($trackerId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('tracker');
        $select->where(array('tracker_id'=>$trackerId));
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();
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
