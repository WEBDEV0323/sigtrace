<?php
namespace SigTRACE\Product\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Insert;
use Complysight\Service\UserAuthAdapter;
use Zend\Session\Container;
use Complysight\Service\UserPassword;
use Zend\Db\Sql\Update;
use Zend\Validator\Explode;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;

class Product extends AbstractTableGateway
{

    public $table = 'product';
    
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(ResultSet::TYPE_ARRAY);
        $this->initialize();
    }    
    
    public function getProductsList($where = array(), $columns = array())
    {
        try {
            $sql = new Sql($this->getAdapter());
            $select = $sql->select()->from(
                array(
                'product' => $this->table
                )
            );
            
            $select->columns(
                array(
                'product_id',
                'product_name',
                'product_code',
                'product_status',
                'product_created_date'
                )
            );
            $select->where('product_archive != 1');  
            if (count($where) > 0) {
                $select->where($where);
            }
            
            if (count($columns) > 0) {
                $select->columns($columns);
            }
            $select->order('product_id desc');
            $statement = $sql->prepareStatementForSqlObject($select);//echo ($sql->getSqlStringForSqlObject ($select));die;        
            $roles = $this->resultSetPrototype->initialize($statement->execute())->toArray();
            return $roles;
        } catch (\Exception $e) {
            throw new \Exception($e->getPrevious()->getMessage());
        }
    }    
    
    public function getProductActiveSubstanceList()
    {
        $qry = 'select p.product_id as "product_id",p.product_name as "product_name",p.product_status,p.product_created_date, GROUP_CONCAT(a.as_name) as "as_name" from product p left join active_substance a on p.as_id=a.as_id where p.product_archive=0 group by p.product_id';
        $statements = $this->getAdapter()->createStatement($qry, array());
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


    public function getDuplicateProduct($productId)
    {
        $qry = 'SELECT product_name FROM active_substance WHERE product_archive=0 AND product_id!=?';
        $statements = $this->getAdapter()->createStatement($qry, array($productId));
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

    public function addProduct($trackerId, $productName, $product)
    {
        try {
            $res = $this->getProductsList(array('product_name'=>$productName), array('product_name'));
            if (!empty($res)) {
                $responseCode = 0;
                $errMessage = 'Product already exists with the same name. Enter new product name';
            } else {
                $sql = new Sql($this->getAdapter());
                $insert = $sql->insert($this->table); 
                $insert->values($product);
                $statement = $sql->prepareStatementForSqlObject($insert);
                $statement->execute()->getGeneratedValue();
                $lastInsertID = $this->adapter->getDriver()->getLastGeneratedValue();
                if (!empty($lastInsertID)) {
                    $responseCode = 1;
                    $errMessage = 'Product added successfully';
                } else {
                    $responseCode = 0;
                    $errMessage = 'Error While inserting product';
                }               
            }            
        } catch(\Exception $e) {            
            $responseCode = 0;
            $errMessage = 'Error While inserting product'; 
        }
        $clientName = $this->getClientNameByTrackerId($trackerId);
        $resultsArr['actionId'] = $lastInsertID;
        $resultsArr['clientName'] = $clientName['0']['client_name'];
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    
    public function updateProduct($trackerId, $oldProd, $where = array(), $columns = array())
    {
        try {
            $sql = new Sql($this->getAdapter());
            $update = $sql->update($this->table); 
            $update->set($columns);
            $update->where($where);
            $statement = $sql->prepareStatementForSqlObject($update);//echo ($sql->getSqlStringForSqlObject($update));die;
            $result = $statement->execute();
            
                        
            $formData = $this->getFormDataByTrackerId('form_id', 'tracker_id='.$trackerId);
            $newProdName = isset($columns['product_name'])?$columns['product_name']:'';
            $newProdCode = isset($columns['product_code'])?$columns['product_code']:'';            
            foreach ($formData as $key => $value) {
                    $configVal = $this->getQuantitativeSettingsConfigs($value['form_id']);
                if (!empty($configVal)) {
                    $productValue = isset($configVal[0]['product_name']) ? $configVal[0]['product_name'] : '';
                    $productCodeValue = isset($configVal[0]['product_code']) ? $configVal[0]['product_code'] : '';
                    $sql = new Sql($this->getAdapter());
                    $update=$sql->update();
                    $update->table('form_'.$trackerId.'_'.$value['form_id']);
                    $prodAs = array($productValue=>$newProdName);
                    $update->set($prodAs);
                    $update->where(array($productValue=>$oldProd));        
                    $prepStatement = $sql->prepareStatementForSqlObject($update);//echo ($sql->getSqlStringForSqlObject($update));
                    $result = $prepStatement->execute();
                }
            }
            
            
            
            
            $prodConfigVal = $this->getQuantitativeAnalysisSettingsConfigs();
            $prodValue = isset($prodConfigVal[0]['product_name']) ? $prodConfigVal[0]['product_name'] : 'product_name';
            
            $sql = new Sql($this->getAdapter());
            $update=$sql->update();
            $update->table('quantitative_analysis');
            $prodAs = array($prodValue=>$columns['product_name']);
            $update->set($prodAs);
            $update->where(array($prodValue=>$oldProd));        
            $prepStatement = $sql->prepareStatementForSqlObject($update);//echo ($sql->getSqlStringForSqlObject($update));die;
            $result = $prepStatement->execute();

            $responseCode = 1;
            $errMessage = 'Product updated successfully';
        } catch (\Exception $e) {
            $responseCode = 0;
            $errMessage = 'Error While edit or delete product'; 
        }
        $clientName = $this->getClientNameByTrackerId($trackerId);
        $resultsArr['clientName'] = $clientName['0']['client_name'];
        $resultsArr['responseCode'] = $responseCode;
        $resultsArr['errMessage'] = $errMessage;
        return $resultsArr;
    }
    
    public function getFormDataByTrackerId($columns, $whereCondition) 
    {
        $qry = "SELECT $columns FROM form  
                     WHERE $whereCondition";
        $statements = $this->adapter->createStatement($qry);//echo $qry;die;
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
    
    public function getClientNameByTrackerId($trackerId)
    {
        $qry = 'SELECT client_name FROM client LEFT JOIN tracker on tracker.client_id=client.client_id WHERE tracker_id=?';
        $statements = $this->adapter->createStatement($qry, array($trackerId));
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
    
    public function getProductById($where = array(), $columns = array())
    {
        try {
            $sql = new Sql($this->getAdapter());
            $select = $sql->select()->from(
                array(
                'product' => $this->table
                )
            );
            $select->columns(
                array(
                'product_id',
                'product_name',
                'tracker_id'
                )
            );
            if (count($where) > 0) {
                $select->where($where);
            }
            $statement = $sql->prepareStatementForSqlObject($select);
            $roles = $this->resultSetPrototype->initialize($statement->execute())->toArray();
            return $roles;
        } catch (\Exception $e) {
            throw new \Exception($e->getPrevious()->getMessage());
        }
    }
    public function updateQuantitativeAnalysis($trackerId,$formId,$productId)
    {
        $query = 'CALL sp_updateQuantitativeAnalysis('.$trackerId.','.$formId.','.$productId.')';
        $connection = $this->dbConnection();
        $statement = $connection->execute($query)->getResource();
        $statement->closeCursor(); 
    }
    
    public function getQuantitativeSettingsConfigs($formId)
    {
        $qry = "SELECT config_value FROM config  
                     WHERE scope_level = 'Form' AND scope_id = ? AND config_key = 'qualitative_settings'";
        $statements = $this->adapter->createStatement($qry, array($formId));
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
        $configValue = array();
        if (count($arrData) > 0) {
            $configValue = json_decode($arrData[0]['config_value'], true);
        }
        return $configValue;
    }
    
    public function getQuantitativeAnalysisSettingsConfigs()
    {        
        $qry = "SELECT config_value FROM config  
                     WHERE scope_level = 'Global' AND config_key = 'qualitative_analysis_settings'";
        $statements = $this->adapter->createStatement($qry, array());
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
        $configValue = array();
        if (count($arrData) > 0) {
            $configValue = json_decode($arrData[0]['config_value'], true);
        }
        return $configValue;
    }
}
