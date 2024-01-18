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

class Labelevent extends AbstractTableGateway
{

    public $table = 'label_event';
    
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(ResultSet::TYPE_ARRAY);
        $this->initialize();
    }
    
    public function fetchAll($where = array(), $columns = array())
    {
        try {
            $sql = new Sql($this->getAdapter());
            $select = $sql->select()->from(
                array(
                'label_event' => $this->table
                )
            );
            $select->columns(
                array(
                'le_id',
                'le_name',
                'le_created_date',
                'product_id'
                )
            );
            $select->where('le_archive = 0');
            if (count($where) > 0) {
                $select->where($where);
            }
            
            if (count($columns) > 0) {
                $select->columns($columns);
            }
            $select->order('le_id desc');
            $statement = $sql->prepareStatementForSqlObject($select);
            $roles = $this->resultSetPrototype->initialize($statement->execute())->toArray();
            return $roles;
        } catch (\Exception $e) {
            throw new \Exception($e->getPrevious()->getMessage());
        }
    }
    public function add($data)
    {
        try {
            $sql = new Sql($this->getAdapter());
            $insert = $sql->insert($this->table); 
            $insert->values($data);
            $statement = $sql->prepareStatementForSqlObject($insert);
            return $statement->execute()->getGeneratedValue();
        } catch (\Exception $e) {
            throw new \Exception($e->getPrevious()->getMessage());
        }
    }
    public function update($where = array(), $columns = array())
    {
        try {
            $sql = new Sql($this->getAdapter());
            $update = $sql->update($this->table); 
            $update->set($columns);
            $update->where($where);
            $statement = $sql->prepareStatementForSqlObject($update);
            return $statement->execute()->getAffectedRows();
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
}
