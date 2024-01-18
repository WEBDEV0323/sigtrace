<?php

namespace ASRTRACE\Tracker\Model;

use Zend\Db\adapter\adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Zend\Mvc\Controller\AbstractActionController;

class Codelist extends AbstractActionController
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
    
    public function getCodeList($trackerId) 
    {
        $query = "Select * from code_list where tracker_id in(0, $trackerId) and archived = 0";
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
    public function getoptionsbycodelist($data)
    {
        $codeListId = $data['code_list_id'];
        $query = "Select * from code_list_option where code_list_id = ? and archived = ?";
        $statements = $this->_adapter->createStatement($query, array($codeListId, 0));
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
    
    public function addNewCodelist($data)
    {
        $trackerId = isset($data['trackerId'])?$data['trackerId']:0;
        $codeListName = isset($data['newCodeList'])?$data['newCodeList']:'';
        $cId = 0;
        if ($trackerId == 0 OR $codeListName == '') {
            $responseCode = 0;
            $errMessage = "Code List Name cannot be empty"; 
        } else {
            $query = "Select * from code_list where code_list_name = ? AND tracker_id IN (?,?) and archived = ?";
            $statements = $this->_adapter->createStatement($query, array($codeListName,0,$trackerId, 0));
            $statements->prepare();
            $resArr = array();
            $results = $statements->execute();
            $resultSet = new ResultSet;
            $resultSet->initialize($results);
            $resultSet->buffer();
            $count = $resultSet->count();
            
            if ($count > 0) {
                $responseCode = 0;
                $errMessage = "Code List Name is already exist";
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
                $cId = $this->_adapter->getDriver()->getLastGeneratedValue();
                $responseCode = 1;
                $errMessage = "Code List Name created successfully";
            }
        }
        $resArr['cId'] = $cId;
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    
    public function getCodelistInfo($codelist_id)
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
            $arrData = $resultSet->toArray();
            $arr     = isset($arrData[0])?$arrData[0]:array();
        }
        return $arr;
    }
    
    public function editCodelist($data)
    {
        $trackerId = $data['trackerId'];
        $codeLlistId = $data['editCodeListId'];
        $codeListName = $data['editCodeList'];
        $query = "Select * from code_list where code_list_name = ? AND tracker_id = ? AND code_list_id NOT IN(?) and archived = ?";
        $statements = $this->_adapter->createStatement($query, array($codeListName,$trackerId,$codeLlistId, 0));
        $statements->prepare();
        $resArr = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $responseCode = 0;
            $errMessage = "Code List Name is already exist";
        } else {
            $sql = new Sql($this->_adapter);
            $newData = array(
                'code_list_name' => $codeListName,
            );
            $update = $sql->update('code_list')
                ->set($newData)
                ->where(array('code_list_id' => $codeLlistId));
            $statement = $sql->prepareStatementForSqlObject($update);
            $results = $statement->execute();
            $responseCode = 1;
            $errMessage = "Code List Name updated successfully";
        }
        $resArr['cId'] = $codeLlistId;
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
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
    
    public function deleteCodelist($post)
    {
        $trackerId = isset($post['trackerId'])?intval($post['trackerId']):0;
        $codeListId = isset($post['codeListID'])?intval($post['codeListID']):0;
        if ($trackerId <= 0 OR $codeListId <= 0) {
            $responseCode = 0;
            $errMessage = "Code List Not available";
        } else {
            $sql = new Sql($this->_adapter);
            $newData = array(
                'archived' => 1,
            );
            $update = $sql->update('code_list')
                ->set($newData)
                ->where(array('code_list_id' => $codeListId));
            $statement = $sql->prepareStatementForSqlObject($update);
            $statement->execute();
            
            $codelistOptionsSql = new Sql($this->_adapter);
            $codelistOptionData = array(
                'archived' => 1,
            );
            $CodelistOptionUpdate = $codelistOptionsSql->update('code_list_option')
                ->set($codelistOptionData)
                ->where(array('code_list_id' => $codeListId));
            $optionsStatement = $codelistOptionsSql->prepareStatementForSqlObject($CodelistOptionUpdate);
            $optionsStatement->execute();
            $responseCode = 1;
            $errMessage = "Code List deleted successfully";
        }
        $resArr['cId'] = $codeListId;
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    
    public function addCodelistOptions($data)
    {
        $trackerId = isset($data['tracker_id'])?intval($data['tracker_id']):0;
        $codeListId = isset($data['code_list_id'])?intval($data['code_list_id']):0;
        $optionNamesArr = isset($data['names'])?$data['names']:array();
        $kpiArr = isset($data['kpi'])?$data['kpi']:array();
        $cloId = array();
        if ($trackerId <= 0 OR $codeListId <= 0 OR empty($optionNamesArr) OR empty($kpiArr)) {
            $responseCode = 0;
            $errMessage = "Codelist Option details are not correct";
        } else {
            try {
                $connection = $this->_adapter->getDriver()->getConnection();
                $connection->beginTransaction();
                $sql = new Sql($this->_adapter);
                $select = $sql->select();
                $select->from('code_list_option');
                $select->where->in('label', $optionNamesArr);
                $select->where(array('code_list_id' => $codeListId, 'archived' => 0));
                $selectString = $sql->prepareStatementForSqlObject($select);
                $results = $selectString->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = $resultSet->count(); //echo $count; die;
                if ($count > 0) {
                    $responseCode = 0;
                    $errMessage = "Duplicate Options are not allowed"; 
                } else if ($count == 0) {
                    foreach ($optionNamesArr as $key => $nameValue) {
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
                        $statement->execute();
                        $cloId[] = $this->_adapter->getDriver()->getLastGeneratedValue();
                    }
                    $connection->commit();
                    $responseCode = 1;
                    $errMessage = "Codelist Options Created successfully";
                }
            } catch(\Exception $e) {
                if ($connection instanceof ConnectionInterface) {
                    $connection->rollback();
                }
                $responseCode = 0;
                $errMessage = 'Error While addting options'; 
            } catch(\PDOException $e) {
                if ($connection instanceof ConnectionInterface) {
                    $connection->rollback();
                }
                $responseCode = 0;
                $errMessage = 'Error While adding options'; 
            }
        }
        $resArr['optionIds'] = !empty($cloId)?implode(",", $cloId):0;
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    
    public function editCodelistOptions($data)
    {
        $trackerId = isset($data['tracker_id'])?intval($data['tracker_id']):0;
        $codeListId = isset($data['code_list_id'])?intval($data['code_list_id']):0;
        $optionNamesArr = isset($data['names'])?$data['names']:array();
        $optionIdsArr = isset($data['option_ids'])?$data['option_ids']:array();
        $kpiArr = isset($data['kpi'])?$data['kpi']:array();
        $oldCodelistIds = array();
        if ($trackerId <= 0 OR $codeListId <= 0) {
            $responseCode = 0;
            $errMessage = "Codelist Option details are not correct";
        } else {
            try {
                $connection = $this->_adapter->getDriver()->getConnection();
                $connection->beginTransaction();
                $dulicate = 0;
                $query = "Select option_id from code_list_option where code_list_id = ? AND archived = ?";
                $statements = $this->_adapter->createStatement($query, array($codeListId, 0));
                $statements->prepare();
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $cnt = $resultSet->count();
                if ($cnt > 0) {
                    $oldCodelistIds = array_column($resultSet->toArray(), 'option_id');;
                } 
                foreach ($optionNamesArr as $key => $nameValue) {
                    $optionId = $optionIdsArr[$key];
                    $query = "Select * from code_list_option where label = ? AND code_list_id = ? AND option_id NOT IN(?) AND archived = ?";
                    $statements = $this->_adapter->createStatement($query, array($nameValue,$codeListId,$optionId, 0));
                    $statements->prepare();
                    $resArr = array();
                    $results = $statements->execute();
                    $resultSet = new ResultSet;
                    $resultSet->initialize($results);
                    $resultSet->buffer();
                    $count = $resultSet->count();
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
                        $statement->execute();
                    } else {
                        $dulicate++;
                    }
                }
                if ($duplicate == 0) {
                    $removeIds = array_diff($oldCodelistIds, $optionIdsArr);
                    if (!empty($removeIds)) { 
                        $query = "UPDATE code_list_option SET archived = ? where option_id IN (";
                        for ($i = 0; count($removeIds) > $i; $i++) {
                            ($i == 0)?$query .= "?":$query .= ",?";
                        }
                        $query .= ")"; 
                        $statements = $this->_adapter->createStatement($query, explode(",", (implode(",", array(1,implode(",", $removeIds))))));
                        $statements->prepare();
                        $statements->execute();
                    }
                    $connection->commit();
                    $responseCode = 1;
                    $errMessage = "Codelist Options Updated successfully";
                } else {
                    if ($connection instanceof ConnectionInterface) {
                        $connection->rollback();
                    }
                    $responseCode = 0;
                    $errMessage = "Duplicate Options are not allowed";
                }
            } catch(\Exception $e) {
                if ($connection instanceof ConnectionInterface) {
                    $connection->rollback();
                }
                $responseCode = 0;
                $errMessage = 'Error While updating options'; 
            } catch(\PDOException $e) {
                if ($connection instanceof ConnectionInterface) {
                    $connection->rollback();
                }
                $responseCode = 0;
                $errMessage = 'Error While updating options'; 
            }
        }
        $resArr['removedIds'] = !empty($removeIds)?implode(",", $removeIds):0;
        $resArr['optionIds'] = !empty($optionIdsArr)?implode(",", $optionIdsArr):0;
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
    
    public function getOptionsByCodelistId($codelistID)
    {
        $query = "Select * from code_list_option where code_list_id = ? AND archived = ?";
        $statements = $this->_adapter->createStatement($query, array($codelistID, 0));
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
    
    public function getOptionsByCodelistOptionId($codeListOptionId)
    {
        $query = "Select * from code_list_option where option_id = ? AND archived = ?";
        $statements = $this->_adapter->createStatement($query, array($codeListOptionId, 0));
        $statements->prepare();
        $arr = array();
        $results = $statements->execute();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $resArr = $resultSet->toArray();
            $arr = $resArr[0];
        }
        return $arr; 
    }
    public function deleteCodelistOption($optionId)
    {
        if ($optionId <= 0) {
            $responseCode = 0;
            $errMessage = "Option Not available";
        } else {
            $sql = new Sql($this->_adapter);
            $newData = array(
                'archived' => 1,
            );
            $update = $sql->update('code_list_option')
                ->set($newData)
                ->where(array('option_id' => $optionId));
            $statement = $sql->prepareStatementForSqlObject($update);
            $statement->execute();
            
            $responseCode = 1;
            $errMessage = "Option deleted successfully";
        }
        $resArr['oId'] = $optionId;
        $resArr['responseCode'] = $responseCode;
        $resArr['errMessage'] = $errMessage;
        return $resArr;
    }
}

