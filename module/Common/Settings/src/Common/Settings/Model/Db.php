<?php
namespace Common\Settings\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;

class Db extends AbstractTableGateway
{

    public $table = 'resource';
    
    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(ResultSet::TYPE_ARRAY);
        $this->initialize();
    }
    
    public function fetch($from = array(), $where = array(), $columns = array(), $order = array(), $group = array(),$joins = array())
    {
        try {
            $sql = new Sql($this->getAdapter());
            if (count($from) > 0) {
                $select = $sql->select()->from($from);
            } else {
                $select = $sql->select()->from(
                    array(
                    't' => $this->table
                    )
                );
            }
            $select->columns($columns);
            if (count($where) > 0) {
                $select->where($where);
            }
            
            if (count($columns) > 0) {
                $select->columns($columns);
            }
            
            if (count($order) > 0) {
                $select->order($order);
            }
            if (count($group) > 0) {
                $select->group($group);
            }
            if (count($joins) > 0) {
                foreach ($joins as $statement) {
                    
                    $select->join($statement[0], $statement[1], $statement[2], $statement[3]);
                }   
            }
            $statement = $sql->prepareStatementForSqlObject($select);
            $result = $this->resultSetPrototype->initialize($statement->execute())->toArray();
            return $result;
        } catch (\Exception $e) {
            throw new \Exception($e->getPrevious()->getMessage());
        }
    }
    public function insert($from = '', $data = array())
    {
        try {
            $sql = new Sql($this->getAdapter());
            if ($from != '') {
                $insert = $sql->insert($from);  
            } else {
                $insert = $sql->insert($this->table); 
            }
            $insert->values($data);
            $statement = $sql->prepareStatementForSqlObject($insert);
            return $statement->execute()->getGeneratedValue();
        } catch (\Exception $e) {
            throw new \Exception($e->getPrevious()->getMessage());
        }
    }
    public function update($from = '', $where = array(), $columns = array())
    {
        try {
            $sql = new Sql($this->getAdapter());
            if ($from != '') {
                $update = $sql->update($from);
            } else {
                $update = $sql->update($this->table); 
            }
             
            $update->set($columns);
            $update->where($where);
            $statement = $sql->prepareStatementForSqlObject($update);
            return $statement->execute()->getAffectedRows();
        } catch (\Exception $e) {
            throw new \Exception($e->getPrevious()->getMessage());
        } 
    }
    public function delete($from, $where = array())
    {
        try {
            $sql = new Sql($this->getAdapter());
            if (count($from) > 0) {
                $delete = $sql->delete($from);
            } else {
                $delete = $sql->delete($this->table); 
            }
             
            $delete->where($where);
            $statement = $sql->prepareStatementForSqlObject($delete);
            return $statement->execute()->getAffectedRows();
        } catch (\Exception $e) {
            throw new \Exception($e->getPrevious()->getMessage());
        } 
    }
}
