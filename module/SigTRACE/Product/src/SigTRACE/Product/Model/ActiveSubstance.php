<?php
namespace SigTRACE\Product\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Driver\ConnectionInterface;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Session\Container\SessionContainer;
use Zend\Mvc\Controller\AbstractActionController;

class ActiveSubstance extends AbstractActionController
{
    protected $_adapter;
    
    public function __construct(Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }
    
    public function dbConnection()
    {
         return $this->getServiceLocator()->get('trace')->getDriver()->getConnection();
    }
    
    public function getAllActiveSubstances($condition)
    {
        $qry = 'SELECT * FROM active_substance WHERE '.$condition;
        $statements = $this->_adapter->createStatement($qry, array());
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
        $statements->getResource()->closeCursor();
        return $arr;
    }
    
    public function getAllActiveSubstancesWithProducts()
    {
        $qry = 'select a.as_id as "as_id",a.as_name as "as_name", GROUP_CONCAT(p.product_name) as "product_name" from product p right join active_substance a on p.as_id=a.as_id where a.as_archive=0 group by a.as_id';
        $statements = $this->_adapter->createStatement($qry, array());
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
        $statements->getResource()->closeCursor();
        return $arr;
    }
    
    public function trackerResults($trackerId)
    {
        $session = new SessionContainer();
        $userContainer = $session->getSession('user');
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
    
    public function getProductNames($columnName,$columnCondition,$tracker_id,$andCondition)
    {
        $qry = 'SELECT '.$columnName.' FROM product WHERE product_archive = 0 AND '.$columnCondition.'=? AND '.$andCondition;
        $statements = $this->_adapter->createStatement($qry, array($tracker_id));
        $statements->prepare();
        $results = $statements->execute();
        $arrData = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arrData = $resultSet->toArray();
        }
        //        if (empty($arrData)) {
        //            $arrData = 0;
        //        }
        $statements->getResource()->closeCursor();
        return $arrData;
    }
    
    public function addActiveSubstance($dataArr,$userContainer,$actSubId)
    {
        $actSubName = isset($dataArr['actSubName'])?$dataArr['actSubName']:'';
        $actSubName = ucwords(strtolower($actSubName));
        $trackerId = isset($dataArr['trackerId'])?$dataArr['trackerId']:'';
        $productIds = isset($dataArr['productIds'])?$dataArr['productIds']:'';
        $actSubNames = $this->getAllActiveSubstances('as_archive=0');
        $match = 0;
        $responseCode = 0;        
        foreach ($actSubNames as $ind => $val) {
            if ($val['as_name'] === $actSubName) {
                $match++;
            }
        }
        if ($match > 0) {
            $responseCode = 0;
            $errMessage = 'Active substance already exists with same name. Enter new active substance name.'; 
        } else {
            try {
                    $sql = new Sql($this->_adapter);
                    $insert = $sql->insert('active_substance');
                    $newData = array('as_name'=>$actSubName, 'as_create_date'=> date("Y-m-d H:i:s"),'as_archive' =>0,'as_last_modified_by'=>$userContainer->email,'as_last_modified_date_time'=>date("Y-m-d H:i:s"));
                    $insert -> values($newData);
                    $statement = $sql->prepareStatementForSqlObject($insert);
                    $results = $statement->execute();
                    $lastInsertID = $this->_adapter->getDriver()->getLastGeneratedValue();

                    $sql = new Sql($this->_adapter);
                    $update=$sql->update();
                    $update->table('product');
                    $prodAs = array('as_id'=>$lastInsertID);
                    $update->set($prodAs);
                    $update->where(array("product_id "=>$productIds,"as_id"=>0));        
                    $prepStatement = $sql->prepareStatementForSqlObject($update);
                    $result = $prepStatement->execute();
                if ($lastInsertID > 0) {
                    $responseCode = 1;
                    $errMessage = 'Active substance created successfully';
                } else {
                    $responseCode = 0;
                    $errMessage = 'Error while inserting active substance';
                }                
            } catch(\Exception $e) {
                $responseCode = 0;
                $errMessage = 'Error while inserting active substance'; 
            } catch(\PDOException $e) {                    
                $responseCode = 0;
                $errMessage = 'Error while inserting active substance';
            }
        }
        if (empty($lastInsertID)) {
            $lastInsertID = "";
        }
        $resultsArr['actionId'] = $lastInsertID;
        $resultsArr['trackerId'] = $trackerId;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    
    public function saveActiveSubstance($dataArr,$userContainer,$actSubId, $previousActSubName, $configVal)
    {
        $actSubName = isset($dataArr['actSubName'])?$dataArr['actSubName']:'';
        $actSubName = ucwords(strtolower($actSubName));
        $trackerId = isset($dataArr['trackerId'])?$dataArr['trackerId']:'';
        $productIds = isset($dataArr['productIds'])?$dataArr['productIds']:'';
        $formData = $this->getFormDataByTrackerId('form_id', 'tracker_id='.$trackerId);
        $responseCode = 0;
        $dupActSub = $this->checkDuplicateActiveSubstance($actSubId);
        if (in_array($actSubName, $dupActSub)) {
            $responseCode = 0;
            $errMessage = 'Active substance already exists with same name. Enter new active substance name.'; 
        } else {
            try {
                $sql = new Sql($this->_adapter);
                $update=$sql->update();
                $update->table('active_substance');
                $prodAs = array('as_name'=>$actSubName,'as_last_modified_by'=>$userContainer->email,'as_last_modified_date_time'=>date("Y-m-d H:i:s"));
                $update->set($prodAs);
                $update->where(array("as_id "=>$actSubId));        
                $prepStatement = $sql->prepareStatementForSqlObject($update);
                $result = $prepStatement->execute();
                
                $sql = new Sql($this->_adapter);
                $update=$sql->update();
                $update->table('product');
                $prodAs = array('as_id'=>0);
                $update->set($prodAs);
                $update->where(array("as_id "=>$actSubId));        
                $prepStatement = $sql->prepareStatementForSqlObject($update);
                $result = $prepStatement->execute();
                
                $sql = new Sql($this->_adapter);
                $update=$sql->update();
                $update->table('product');
                $prodAs = array('as_id'=>$actSubId);
                $update->set($prodAs);
                $update->where(array("product_id "=>$productIds));        
                $prepStatement = $sql->prepareStatementForSqlObject($update);
                $result = $prepStatement->execute();
                
                foreach ($formData as $key => $value) {
                    $configVal = $this->getQuantitativeSettingsConfigs($value['form_id']);
                    if (!empty($configVal)) {
                        $aiValue = isset($configVal[0]['active_ingredient']) ? $configVal[0]['active_ingredient'] : 'active_ingredient';
                        $sql = new Sql($this->_adapter);
                        $update=$sql->update();
                        $update->table('form_'.$trackerId.'_'.$value['form_id']);
                        $prodAs = array($aiValue=>$actSubName);
                        $update->set($prodAs);
                        $update->where(array($aiValue=>$previousActSubName));        
                        $prepStatement = $sql->prepareStatementForSqlObject($update);
                        $result = $prepStatement->execute();
                    }
                }
                
                $qaConfigVal = $this->getQuantitativeAnalysisSettingsConfigs();
                $asValue = isset($qaConfigVal[0]['as_name']) ? $qaConfigVal[0]['as_name'] : 'as_name';
                $sql = new Sql($this->_adapter);
                $update=$sql->update();
                $update->table('quantitative_analysis');
                $prodAs = array($asValue=>$actSubName);
                $update->set($prodAs);
                $update->where(array($asValue=>$previousActSubName));        
                $prepStatement = $sql->prepareStatementForSqlObject($update);//echo ($sql->getSqlStringForSqlObject($update));die;
                $result = $prepStatement->execute();
                                
                $responseCode = 1;
                $errMessage = 'Active substance updated successfully';                                    
                
            } catch(\Exception $e) {
                $responseCode = 0;
                $errMessage = 'Error while updating active substance'; 
            } catch(\PDOException $e) {                    
                $responseCode = 0;
                $errMessage = 'Error while updating active substance';
            }
        }
        $resultsArr['actionId'] = $actSubId;
        $resultsArr['trackerId'] = $trackerId;
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    
    public function getFormDataByTrackerId($columns, $whereCondition) 
    {
        $socQuery = "SELECT $columns FROM form  
                     WHERE $whereCondition";
        $connection = $this->dbConnection();
        $statement = $connection->execute($socQuery)->getResource();
        $configArr = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor();
        return $configArr;        
    }

    public function checkDuplicateActiveSubstance($actSubId)
    {
        $qry = 'SELECT as_name FROM active_substance WHERE as_archive=0 AND as_id!=?';
        $statements = $this->_adapter->createStatement($qry, array($actSubId));
        $statements->prepare();
        $results = $statements->execute();
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arr = $resultSet->toArray();
            foreach ($arr as $value) {
                $resArr[]=$value['as_name'];
            }
        }
        $statements->getResource()->closeCursor();
        return $resArr;
    }

    public function getSelectedActiveSubstance($activeSubstancesId)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('active_substance');
        $select->where(array('as_id' => $activeSubstancesId));
        $selectString = $sql->prepareStatementForSqlObject($select);
        $results = $selectString->execute(); 
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arrData = $resultSet->toArray();            
        }
        return $arrData;
    }    

    public function getClientNameByTrackerId($trackerId)
    {
        $qry = 'SELECT client_name FROM client LEFT JOIN tracker on tracker.client_id=client.client_id WHERE tracker_id=?';
        $statements = $this->_adapter->createStatement($qry, array($trackerId));
        $statements->prepare();
        $results = $statements->execute();        
        $arr = array();
        $resultSet = new ResultSet;
        $resultSet->initialize($results);
        $resultSet->buffer();
        $count = $resultSet->count();
        if ($count > 0) {
            $arrData = $resultSet->toArray();
        }
        $statements->getResource()->closeCursor();
        return $arrData;
    }
    
    public function deleteActiveSubstance($trackerId,$activeSubstancesId,$userContainer)
    {
        try {        
            $qry = 'UPDATE active_substance SET as_name = CONCAT(as_name,"_deleted_'.time().'"), as_archive=1, as_last_modified_by="'.$userContainer->email.'", as_last_modified_date_time="'.date("Y-m-d H:i:s").'" WHERE as_id=?';
            $statements = $this->_adapter->createStatement($qry, array($activeSubstancesId));
            $statements->prepare();
            $results = $statements->execute();
            
            $sql = new Sql($this->_adapter);
            $update=$sql->update();
            $update->table('product');
            $prodAs = array('as_id'=>0);
            $update->set($prodAs);
            $update->where(array("as_id "=>$activeSubstancesId,"tracker_id"=>$trackerId));
            $prepStatement = $sql->prepareStatementForSqlObject($update);
            $result = $prepStatement->execute();
            
            $responseCode = 1;
            $errMessage = 'Active substance deleted successfully';  
        } catch(\Exception $e) {
            $responseCode = 0;
            $errMessage = 'Error while updating active substance'; 
        } catch(\PDOException $e) {
            $responseCode = 0;
            $errMessage = 'Error while updating active substance';
        }
        $clientName = $this->getClientNameByTrackerId($trackerId);
        $resultsArr['clientName'] = $clientName['0']['client_name'];
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    
    public function getQuantitativeSettingsConfigs($formId)
    {
        $socQuery = "SELECT config_value FROM config  
                     WHERE scope_level = 'Form' AND scope_id = $formId AND config_key = 'qualitative_settings'";
        $connection = $this->dbConnection();
        $statement = $connection->execute($socQuery)->getResource();
        $configArr = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor(); 
        $configValue = array();
        if (count($configArr) > 0) {
            $configValue = json_decode($configArr[0]['config_value'], true);
        }
        
        return $configValue;
    }
    
    public function getQuantitativeAnalysisSettingsConfigs()
    {
        $socQuery = "SELECT config_value FROM config  
                     WHERE scope_level = 'Global' AND config_key = 'qualitative_analysis_settings'";
        $connection = $this->dbConnection();
        $statement = $connection->execute($socQuery)->getResource();
        $configArr = $statement->fetchAll(\PDO::FETCH_ASSOC);
        $statement->closeCursor(); 
        $configValue = array();
        if (count($configArr) > 0) {
            $configValue = json_decode($configArr[0]['config_value'], true);
        }
        
        return $configValue;
    }
}
