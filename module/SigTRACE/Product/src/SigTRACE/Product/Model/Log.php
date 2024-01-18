<?php
namespace SigTRACE\Product\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;

class Log extends AbstractTableGateway
{

    public $table = 'log';
    
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(ResultSet::TYPE_ARRAY);
        $this->initialize();
    }
    
    public function fetch($where = array(), $columns = array())
    {
        try {
            $sql = new Sql($this->getAdapter());
            $select = $sql->select()->from(
                array(
                'log' => $this->table
                )
            );
            $select->columns(
                array(
                'log_id',
                'log_date',
                'log_old_value',
                'log_new_value',
                'log_active_status'
                )
            );
            //$select->where('syn_archive = 0');
            if (count($where) > 0) {
                $select->where($where);
            }
            
            if (count($columns) > 0) {
                $select->columns($columns);
            }
            $select->order('log_id desc');
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
}
