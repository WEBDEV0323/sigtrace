<?php
namespace SigTRACE\Casedata\Helper;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Predicate;
use Session\Container\SessionContainer;

class CasedataHelper
{

    /**
     * Make the Adapter object avilable as local prtected variable
     *
     * @param Adapter $adapter - DB PDO PgSQL conn
     */
    public function __construct(Adapter $adapter)
    {
        $this->_adapter = $adapter;
    }
    /**
     * @return result object
     */
    public function insertDataToTable()
    {
        

    }

    /**
     * @return result object
     */
    
    /**
     * @return result object
     */
    public function getImportFieldMappingData($tableName)
    {
        $sql = new Sql($this->_adapter);
        $select = $sql->select();
        $select->from('import_field_mapping');
        $select->columns(array('source_field_name','db_field_name','isRequiredForDuplicateSearch','isDate', 'isEditable', 'isRequired', 'isUnique', 'isDisplay'));
        $select->where(array('form_name' => $tableName));
        $select->order('displayOrder ASC');
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
    public function insertCsvData($dataArray,$tableName)
    {
        $sql = new Sql($this->_adapter);
        $insert = $sql->insert($tableName);
        $results='';
        foreach ($dataArray as $data) {
            if (!empty($data)) {
                $data = $this->qualitativeAutopopulate($data, $tableName);
                $insert->values($data);
                $selectString = $sql->prepareStatementForSqlObject($insert);
                try {
                    $results = $selectString->execute();
                } catch (\Exception $e) {
                    $results = $e->getMessage(); 
                }
            }
        }
        return $results;
    }     
    public function getDupDataFromDb($dupArraydata,$tableName,$columnName)
    {
        if (empty($columnName)) {
            return $array=[];
        }
        $where=''; $j=0;
        foreach ($dupArraydata as $data) {
            if (!empty($data)) {
                $i=1; 
                $where=$where."(";
            
                foreach ($data as $key => $value) {
                    
                    if ($i < count($data)) {
                        $where=$where.$key." = '".$value."' AND ";
                    } else {
                        $where=$where.$key." = '".$value."'";
                    }
                    $i++;
                } 
                $j++;
                $where=$where.")"; 
            
                if ($j<count($dupArraydata)) {
                    $where=$where." OR ";
                }
            }
        }
        
        if ($where =='') {
            $query="Select ". implode(',', $columnName)." From ".$tableName;
        } else {
            $query="Select ". implode(',', $columnName)." From ".$tableName." Where ".$where;
        }
        $statements = $this->_adapter->createStatement($query);
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

    public function qualitativeAutopopulate($data, $tableName)
    {
        $session = new SessionContainer();
        $configContainer = $session->getSession("config");
        try {
            $configData = isset($configContainer->auto_populate_qualitative_analysis) && $configContainer->auto_populate_qualitative_analysis != ''?json_decode($configContainer->auto_populate_qualitative_analysis, true):array();
            if (!empty($configData) && isset($configData['condition']) && isset($configData['fields'])) {
                $arr = array();
                $outCondition = $configData['condition'];
                array_walk(
                    $data, function ($value, $key) use (&$outCondition) {
                        $outCondition  = str_replace("{{".$key."}}", "'".$value."'", $outCondition);
                    }
                );
                $query = "SELECT ".implode(", ", $configData['fields'])." FROM ".$tableName." WHERE ".$outCondition;
                $statements = $this->_adapter->createStatement($query, array());
                $statements->prepare();
                $results = $statements->execute();
                $resultSet = new ResultSet;
                $resultSet->initialize($results);
                $resultSet->buffer();
                $count = $resultSet->count();
                if ($count > 0) {
                    $arr = $resultSet->toArray()[0];
                }
                $data = array_merge($data, $arr);
            }
            return $data; 
        } catch(\Exception $e) {
            return $data;
        } catch(\PDOException $e) {
            return $data;
        }
        
    }
}
