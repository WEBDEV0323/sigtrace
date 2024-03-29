<?php

namespace Common\Authorization\Model;

use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;

class RolePermissionTable extends AbstractTableGateway
{

    public $table = 'role_permission';

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->resultSetPrototype = new ResultSet(ResultSet::TYPE_ARRAY);
        $this->initialize();
    }

    public function getRolePermissions($trackerId)
    {
        $sql = new Sql($this->getAdapter());
        
        $select = $sql->select()
            ->from(array('t1' => 'role'))
            ->columns(array('role_name'))
            ->join(array('t2' => $this->table), 't1.rid = t2.role_id', array('tracker_id'), 'left')
            ->join(array('t3' => 'action'), 't3.action_id = t2.permission_id', array('action_name'), 'left')
            ->join(array('t4' => 'controller'), 't4.controller_id = t3.controller_id', array('controller_name'), 'left')
            ->where('t3.action_name is not null and t4.controller_name is not null and t2.tracker_id = '.$trackerId)
            ->order('t1.rid');
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $this->resultSetPrototype->initialize($statement->execute())->toArray(); //echo "<pre>"; print_r($result); die;
        return $result;
    }
}
